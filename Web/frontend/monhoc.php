<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = '';
$err = '';


$role    = $_SESSION['urole'] ?? 'viewer';
$canEdit = in_array($role, ['admin','editor'], true);


$donvi = [];
$r = $conn->query("SELECT id, tenKhoa FROM khoa ORDER BY tenKhoa ASC");
if ($r) while ($row = $r->fetch_assoc()) $donvi[] = $row;


$allMon = [];
$r = $conn->query("SELECT id, maMon, tenMon FROM monhoc ORDER BY tenMon ASC");
if ($r) while ($row = $r->fetch_assoc()) $allMon[] = $row;


if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']    ?? '';
    $id        = (int)($_POST['id']    ?? 0);
    $maMon     = trim($_POST['maMon'] ?? '');
    $tenMon    = trim($_POST['tenMon']?? '');
    $soTinChi  = (int)($_POST['soTinChi'] ?? 0);
    $donvi_id  = (int)($_POST['donvi_id'] ?? 0);
    $moTa      = trim($_POST['moTa'] ?? '');
    $tienQuyet = (int)($_POST['tienQuyet'] ?? 0);
    $phuThuoc  = (int)($_POST['phuThuoc']  ?? 0);

    if ($maMon==='' || $tenMon==='' || $soTinChi<=0 || $donvi_id<=0) {
        $err = 'Please nhap u Ma mon, Ten mon, So tin chi, on vi quan ly.';
    } elseif ($soTinChi < 1 || $soTinChi > 10) {
        $err = 'Sample text';
    } else {
        $chk = $conn->query("SELECT 1 FROM khoa WHERE id=$donvi_id");
        if (!$chk || !$chk->num_rows) $err = 'on vi quan ly khong hop le.';
    }

    if (!$err && $action === 'update') {
        if ($tienQuyet === $id || $phuThuoc === $id) {
            $err = 'Mon tien quyet/phu thuoc khong uoc la chinh mon ang sua.';
        }
    }

    if (!$err && $action === 'add') {
        $st = $conn->prepare("SELECT id FROM monhoc WHERE maMon=? LIMIT 1");
        $st->bind_param('s', $maMon);
        $st->execute(); $st->store_result();
        if ($st->num_rows > 0) $err = 'Ma mon already exists.';
        $st->close();

        if (!$err) {
            $conn->begin_transaction();
            try {
                $st = $conn->prepare("INSERT INTO monhoc(maMon,tenMon,soTinChi,donvi_id,moTa) VALUES(?,?,?,?,?)");
                $st->bind_param('ssiss', $maMon,$tenMon,$soTinChi,$donvi_id,$moTa);
                $st->execute();
                $newId = $st->insert_id;
                $st->close();

                if ($tienQuyet > 0) {
                    $st = $conn->prepare("INSERT IGNORE INTO monhoc_quanhe(mon_id,loai,lien_quan_id) VALUES(?,?,?)");
                    $loai = 'TIEN_QUYET';
                    $st->bind_param('isi', $newId, $loai, $tienQuyet);
                    $st->execute(); $st->close();
                }
                if ($phuThuoc > 0) {
                    $st = $conn->prepare("INSERT IGNORE INTO monhoc_quanhe(mon_id,loai,lien_quan_id) VALUES(?,?,?)");
                    $loai = 'PHU_THUOC';
                    $st->bind_param('isi', $newId, $loai, $phuThuoc);
                    $st->execute(); $st->close();
                }

                $st = $conn->prepare("INSERT INTO audit_log(table_name,record_id,action,message) VALUES('monhoc',?,'CREATE',?)");
                $msgLog = "Create mon {$maMon} - {$tenMon}";
                $st->bind_param('is', $newId, $msgLog);
                $st->execute(); $st->close();

                $conn->commit();
                $msg = 'Add mon hoc thanh cong!';
                $_POST = [];
            } catch (Throwable $e) {
                $conn->rollback();
                $err = 'No the them mon hoc: '.$e->getMessage();
            }
        }
    }

    if (!$err && $action === 'update') {
        if ($id <= 0) {
            $err = 'Thieu ID e cap nhat.';
        } else {
            $st = $conn->prepare("SELECT id FROM monhoc WHERE maMon=? AND id<>? LIMIT 1");
            $st->bind_param('si', $maMon, $id);
            $st->execute(); $st->store_result();
            if ($st->num_rows > 0) $err = 'Ma mon trung voi mon khac.';
            $st->close();

            if (!$err) {
                try {
                    $conn->begin_transaction();

                    $st = $conn->prepare("UPDATE monhoc SET maMon=?, tenMon=?, soTinChi=?, donvi_id=?, moTa=? WHERE id=?");
                    $st->bind_param('ssissi', $maMon,$tenMon,$soTinChi,$donvi_id,$moTa,$id);
                    $st->execute();
                    $st->close();

                    $conn->query("DELETE FROM monhoc_quanhe WHERE mon_id=$id");
                    if ($tienQuyet > 0) {
                        $st = $conn->prepare("INSERT IGNORE INTO monhoc_quanhe(mon_id,loai,lien_quan_id) VALUES(?,?,?)");
                        $loai = 'TIEN_QUYET';
                        $st->bind_param('isi', $id,$loai,$tienQuyet);
                        $st->execute(); $st->close();
                    }
                    if ($phuThuoc > 0) {
                        $st = $conn->prepare("INSERT IGNORE INTO monhoc_quanhe(mon_id,loai,lien_quan_id) VALUES(?,?,?)");
                        $loai = 'PHU_THUOC';
                        $st->bind_param('isi', $id,$loai,$phuThuoc);
                        $st->execute(); $st->close();
                    }

                    $st = $conn->prepare("INSERT INTO audit_log(table_name,record_id,action,message) VALUES('monhoc',?,'UPDATE',?)");
                    $msgLog = "Update mon {$maMon} - {$tenMon}";
                    $st->bind_param('is', $id, $msgLog);
                    $st->execute(); $st->close();

                    $conn->commit();
                    $msg = 'Update mon hoc thanh cong!';
                } catch (Throwable $e) {
                    $conn->rollback();
                    $err = 'No the cap nhat mon hoc: '.$e->getMessage();
                }
            }
        }
    }
}


if ($canEdit && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id>0) {
        try {
            $conn->begin_transaction();
            $conn->query("DELETE FROM monhoc_quanhe WHERE mon_id=$id OR lien_quan_id=$id");
            $ok = $conn->query("DELETE FROM monhoc WHERE id=$id");

            if ($ok) {
                $st = $conn->prepare("INSERT INTO audit_log(table_name,record_id,action,message) VALUES('monhoc',?,'DELETE','Delete mon')");
                $st->bind_param('i',$id);
                $st->execute(); $st->close();
                $conn->commit();
                $msg = 'a xoa mon hoc.';
            } else {
                $conn->rollback();
                $err = 'No the xoa mon hoc.';
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $err = 'No the xoa mon hoc: '.$e->getMessage();
        }
    }
}


$q = trim($_GET['q'] ?? '');
$where = $q!=='' ? "WHERE (m.maMon LIKE '%".$conn->real_escape_string($q)."%' OR m.tenMon LIKE '%".$conn->real_escape_string($q)."%')" : '';

$sql = "
SELECT
  m.id, m.maMon, m.tenMon, m.soTinChi, m.moTa, k.tenKhoa AS donvi,
  COALESCE( (SELECT GROUP_CONCAT(CONCAT(mm.maMon,' - ',mm.tenMon) SEPARATOR ', ')
             FROM monhoc_quanhe qh
             JOIN monhoc mm ON mm.id=qh.lien_quan_id
            WHERE qh.mon_id=m.id AND qh.loai='TIEN_QUYET'), '' ) AS dsTienQuyet,
  COALESCE( (SELECT GROUP_CONCAT(CONCAT(mm.maMon,' - ',mm.tenMon) SEPARATOR ', ')
             FROM monhoc_quanhe qh
             JOIN monhoc mm ON mm.id=qh.lien_quan_id
            WHERE qh.mon_id=m.id AND qh.loai='PHU_THUOC'), '' ) AS dsPhuThuoc
FROM monhoc m
JOIN khoa k ON k.id=m.donvi_id
$where
ORDER BY m.id DESC";
$rows = $conn->query($sql);


$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r = $conn->query("SELECT * FROM monhoc WHERE id=$eid");
    if ($r && $r->num_rows) $edit = $r->fetch_assoc();

    if ($edit) {
        $rq = $conn->query("SELECT loai, lien_quan_id FROM monhoc_quanhe WHERE mon_id=$eid");
        $edit['tienQuyet'] = 0; $edit['phuThuoc'] = 0;
        if ($rq) while ($qrow = $rq->fetch_assoc()) {
            if ($qrow['loai']==='TIEN_QUYET') $edit['tienQuyet'] = (int)$qrow['lien_quan_id'];
            if ($qrow['loai']==='PHU_THUOC') $edit['phuThuoc'] = (int)$qrow['lien_quan_id'];
        }
    }
}


$maMonPost    = $_POST['maMon']    ?? '';
$tenMonPost   = $_POST['tenMon']   ?? '';
$soTCPost     = $_POST['soTinChi'] ?? '';
$donviPost    = (int)($_POST['donvi_id'] ?? 0);
$moTaPost     = $_POST['moTa']     ?? '';
$tqPost       = (int)($_POST['tienQuyet'] ?? 0);
$ptPost       = (int)($_POST['phuThuoc']  ?? 0);


$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Management Courses</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span></div>
<ul>
  <li>
      <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-house"></i><span>Home</span>
      </a>
  </li>
  <li>
      <a href="giangvien.php" class="<?= basename($_SERVER['PHP_SELF']) === 'giangvien.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-chalkboard-user"></i><span>Lecturers</span>
      </a>
  </li>
  <li>
      <a href="khoa.php" class="<?= basename($_SERVER['PHP_SELF']) === 'khoa.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-building-columns"></i><span>Khoa</span>
      </a>
  </li>
  <li>
      <a href="monhoc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'monhoc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-book"></i><span>Courses</span>
      </a>
  </li>
  <li>
      <a href="nganh.php" class="<?= basename($_SERVER['PHP_SELF']) === 'nganh.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-layer-group"></i><span>Majors</span>
      </a>
  </li>
  
  <li>
      <a href="lophoc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'lophoc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-chalkboard"></i><span>Management Khoa hoc</span>
      </a>
  </li>
  <li>
      <a href="khoikienthuc.php" class="<?= basename($_SERVER['PHP_SELF']) === 'khoikienthuc.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-book"></i><span>Knowledge Blocks</span>
      </a>
  </li>
  <li>
      <a href="ctdt_manager.php" class="<?= basename($_SERVER['PHP_SELF']) === 'ctdt_manager.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-project-diagram"></i><span>Curriculum Management</span>
      </a>
  </li>
  <li>
      <a href="news.php" class="<?= basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : '' ?>">
          <i class="fa-regular fa-newspaper"></i><span>News</span>
      </a>
  </li>
  <li>
      <a href="thanhtoan.php" class="<?= basename($_SERVER['PHP_SELF']) === 'thanhtoan.php' ? 'active' : '' ?>">
          <i class="fa-solid fa-credit-card"></i><span>Payments</span>
      </a>
  </li>
</ul>


  <div style="margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px">
    <div><i class="fa-solid fa-user-shield"></i> <?= h($role) ?>:
      <b><?= h($_SESSION['uname'] ?? '') ?></b>
    </div>
    <div style="margin-top:8px">
      <a href="/phenikaa_manager/auth_php_pack/logout.php" style="color:#e5e7eb;text-decoration:underline">Logout</a>
    </div>
  </div>
</div>

<div class="main-content">
  <h2>Management Courses</h2>

  <?php if ($msg): ?><div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div><?php endif; ?>

  <form method="get" style="display:flex;gap:8px;margin-bottom:12px">
    <input name="q" value="<?= h($q) ?>" placeholder="Search by ma/ten mon...">
    <button class="tab">Search</button>
    <a class="tab" href="monhoc.php" style="text-decoration:none">N/A</a>
  </form>

  <div style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px">
    <?php if ($canEdit): ?>
      <?php if ($edit): ?>
        <h3>Edit Courses #<?= h($edit['id']) ?></h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= h($edit['id']) ?>">

          <input name="maMon" value="<?= h($edit['maMon']) ?>" placeholder="Ma mon" required>
          <input name="tenMon" value="<?= h($edit['tenMon']) ?>" placeholder="Ten mon" required>
          <input type="number" name="soTinChi" min="1" max="10" value="<?= h($edit['soTinChi']) ?>" placeholder="Enter value" required>

          <select name="donvi_id" required>
            <option value="">-- on vi quan ly --</option>
            <?php foreach ($donvi as $dv): ?>
              <option value="<?= h($dv['id']) ?>" <?= ((int)$edit['donvi_id']===(int)$dv['id'])?'selected':'' ?>>
                <?= h($dv['tenKhoa']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="tienQuyet">
            <option value="0">-- Mon tien quyet (tuy chon) --</option>
            <?php foreach ($allMon as $m): if ((int)$m['id']===(int)$edit['id']) continue; ?>
              <option value="<?= h($m['id']) ?>" <?= ((int)($edit['tienQuyet']??0)===(int)$m['id'])?'selected':'' ?>>
                <?= h($m['maMon'].' - '.$m['tenMon']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="phuThuoc">
            <option value="0">-- Mon phu thuoc (tuy chon) --</option>
            <?php foreach ($allMon as $m): if ((int)$m['id']===(int)$edit['id']) continue; ?>
              <option value="<?= h($m['id']) ?>" <?= ((int)($edit['phuThuoc']??0)===(int)$m['id'])?'selected':'' ?>>
                <?= h($m['maMon'].' - '.$m['tenMon']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <textarea name="moTa" rows="3" style="grid-column:1/-1" placeholder="Enter value"><?= h($edit['moTa'] ?? '') ?></textarea>

          <div style="grid-column:1/-1;display:flex;gap:8px">
            <button class="tab">N/A</button>
            <a class="tab" href="monhoc.php" style="text-decoration:none">N/A</a>
          </div>
        </form>
      <?php else: ?>
        <h3>Add Courses</h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
          <input type="hidden" name="action" value="add">

          <input name="maMon" value="<?= h($maMonPost) ?>" placeholder="Ma mon" required>
          <input name="tenMon" value="<?= h($tenMonPost) ?>" placeholder="Ten mon" required>
          <input type="number" name="soTinChi" min="1" max="10" value="<?= h($soTCPost) ?>" placeholder="Enter value" required>

          <select name="donvi_id" required>
            <option value="">-- on vi quan ly --</option>
            <?php foreach ($donvi as $dv): ?>
              <option value="<?= h($dv['id']) ?>" <?= ((int)$donviPost===(int)$dv['id'])?'selected':'' ?>>
                <?= h($dv['tenKhoa']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="tienQuyet">
            <option value="0">-- Mon tien quyet (tuy chon) --</option>
            <?php foreach ($allMon as $m): ?>
              <option value="<?= h($m['id']) ?>" <?= ((int)$tqPost===(int)$m['id'])?'selected':'' ?>>
                <?= h($m['maMon'].' - '.$m['tenMon']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select name="phuThuoc">
            <option value="0">-- Mon phu thuoc (tuy chon) --</option>
            <?php foreach ($allMon as $m): ?>
              <option value="<?= h($m['id']) ?>" <?= ((int)$ptPost===(int)$m['id'])?'selected':'' ?>>
                <?= h($m['maMon'].' - '.$m['tenMon']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <textarea name="moTa" rows="3" style="grid-column:1/-1" placeholder="Enter value"><?= h($moTaPost) ?></textarea>

          <div style="grid-column:1/-1">
            <button class="tab">Add</button>
          </div>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <div style="opacity:.9">You are in <b>view-only</b> mode. You don't have permission to add/edit/delete.</div>
    <?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>Ma</th>
        <th>Ten mon</th>
        <th>Tin chi</th>
        <th>on vi quan ly</th>
        <th>Tien quyet</th>
        <th>Phu thuoc</th>
        <th>Mo ta</th>
        <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($rows && $rows->num_rows) {
        while ($r = $rows->fetch_assoc()) {
          echo '<tr>'.
            '<td>'.h($r['maMon']).'</td>'.
            '<td>'.h($r['tenMon']).'</td>'.
            '<td>'.h($r['soTinChi']).'</td>'.
            '<td>'.h($r['donvi']).'</td>'.
            '<td>'.h($r['dsTienQuyet']).'</td>'.
            '<td>'.h($r['dsPhuThuoc']).'</td>'.
            '<td>'.h($r['moTa']).'</td>';
          if ($canEdit) {
            echo '<td style="display:flex;gap:8px">'.
              '<a class="tab" href="monhoc.php?edit='.h($r['id']).'">Edit</a>'.
              '<a class="tab" style="background:#ef4444" href="monhoc.php?delete='.h($r['id']).'" onclick="return confirm(\'Delete mon nay?\\\')">Delete</a>'.
            '</td>';
          }
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="'.($canEdit?8:7).'">No data.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>
</body>
</html>