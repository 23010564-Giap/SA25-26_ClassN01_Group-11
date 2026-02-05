<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = '';
$err = '';


$role    = $_SESSION['urole'] ?? 'viewer';
$canEdit = in_array($role, ['admin','editor'], true);


$BAC_DAO_TAO = ['Sample text','ai hoc','Sample text','Sample text','Other'];


function existsMajorByName($conn, string $ten, int $khoaId, int $excludeId = 0): bool {
    $sql = "SELECT id FROM nganh WHERE tenNganh=? AND khoa_id=?" . ($excludeId ? " AND id<>?" : "") . " LIMIT 1";
    $st  = $conn->prepare($sql);
    if ($excludeId) $st->bind_param('sii', $ten, $khoaId, $excludeId);
    else            $st->bind_param('si',  $ten, $khoaId);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $ok;
}
function existsMajorByCode($conn, string $ma, int $khoaId, int $excludeId = 0): bool {
    $sql = "SELECT id FROM nganh WHERE maNganh=? AND khoa_id=?" . ($excludeId ? " AND id<>?" : "") . " LIMIT 1";
    $st  = $conn->prepare($sql);
    if ($excludeId) $st->bind_param('sii', $ma, $khoaId, $excludeId);
    else            $st->bind_param('si',  $ma, $khoaId);
    $st->execute();
    $ok = (bool)$st->get_result()->fetch_row();
    $st->close();
    return $ok;
}


$khoaList = [];
if ($rk = $conn->query("SELECT id, maKhoa, tenKhoa FROM khoa ORDER BY tenKhoa ASC")) {
  while ($row = $rk->fetch_assoc()) $khoaList[] = $row;
}


if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $id        = (int)($_POST['id']    ?? 0);
    $maNganh   = trim($_POST['maNganh']   ?? '');
    $tenNganh  = trim($_POST['tenNganh']  ?? '');
    $khoa_id   = (int)($_POST['khoa_id']  ?? 0);
    $bacDaoTao = trim($_POST['bacDaoTao'] ?? '');

    if ($maNganh === '' || $tenNganh === '' || $khoa_id <= 0 || $bacDaoTao === '') {
        $err = 'Please nhap ay u: Ma nganh, Ten nganh, Department phu trach va Bac ao tao.';
    } elseif (!in_array($bacDaoTao, $BAC_DAO_TAO, true)) {
        $err = 'Bac ao tao khong hop le.';
    } elseif (!preg_match('/^[A-Za-z0-9\-_.]{2,50}$/', $maNganh)) {
        $err = 'Ma nganh chi gom chu/so va - _ . (250 ky tu).';
    }

    if ($err === '') {
        if ($action === 'add') {
            if (existsMajorByCode($conn, $maNganh, $khoa_id)) {
                $err = "Ma nganh '".h($maNganh)."' already exists trong khoa nay.";
            } elseif (existsMajorByName($conn, $tenNganh, $khoa_id)) {
                $err = "Majors '".h($tenNganh)."' already exists trong khoa nay.";
            } else {
                $st = $conn->prepare("INSERT INTO nganh(maNganh, tenNganh, khoa_id, bacDaoTao) VALUES(?,?,?,?)");
                $st->bind_param('ssis', $maNganh, $tenNganh, $khoa_id, $bacDaoTao);
                $ok = $st->execute();
                $st->close();
                if ($ok) { $msg = 'Add nganh thanh cong!'; $_POST = []; }
                else     { $err = 'Error adding nganh: ' . $conn->error; }
            }
        }

        if ($action === 'update') {
            if ($id <= 0) {
                $err = 'Thieu ID e cap nhat.';
            } elseif (existsMajorByCode($conn, $maNganh, $khoa_id, $id)) {
                $err = "Ma nganh '".h($maNganh)."' already exists trong khoa nay.";
            } elseif (existsMajorByName($conn, $tenNganh, $khoa_id, $id)) {
                $err = "Majors '".h($tenNganh)."' already exists trong khoa nay.";
            } else {
                $st = $conn->prepare("UPDATE nganh SET maNganh=?, tenNganh=?, khoa_id=?, bacDaoTao=? WHERE id=?");
                $st->bind_param('ssisi', $maNganh, $tenNganh, $khoa_id, $bacDaoTao, $id);
                $ok = $st->execute();
                $st->close();
                $msg = $ok ? 'Update nganh thanh cong!' : ('Error khi cap nhat: ' . $conn->error);
            }
        }
    }
}


if ($canEdit && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        if ($err === '') {
            if ($conn->query("DELETE FROM nganh WHERE id = $id")) $msg = 'a xoa nganh.';
            else $err = 'No the xoa nganh (co the ang uoc tham chieu).';
        }
    }
}


$q = trim($_GET['q'] ?? '');
$where = $q !== '' ? "WHERE n.tenNganh LIKE '%" . $conn->real_escape_string($q) . "%'
                      OR n.maNganh LIKE '%" . $conn->real_escape_string($q) . "%'" : '';
$sql = "
  SELECT n.id, n.maNganh, n.tenNganh, n.khoa_id, n.bacDaoTao, k.tenKhoa, k.maKhoa
  FROM nganh n
  JOIN khoa k ON k.id = n.khoa_id
  $where
  ORDER BY n.id DESC
";
$rows = $conn->query($sql);


$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $conn->query("
        SELECT n.*, k.tenKhoa, k.maKhoa
        FROM nganh n
        LEFT JOIN khoa k ON k.id = n.khoa_id
        WHERE n.id=$eid
    ");
    if ($r && $r->num_rows) $edit = $r->fetch_assoc();
}


$maPost   = $_POST['maNganh']   ?? '';
$tenPost  = $_POST['tenNganh']  ?? '';
$khoaPost = (int)($_POST['khoa_id'] ?? 0);
$bacPost  = $_POST['bacDaoTao'] ?? '';


$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Management Majors</title>
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


  <!-- User + Logout -->
  <div style="margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px">
    <div><i class="fa-solid fa-user-shield"></i> <?= h($role) ?>: <b><?= h($_SESSION['uname'] ?? '') ?></b></div>
    <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php" style="color:#e5e7eb;text-decoration:underline">Logout</a></div>
  </div>
</div>

<div class="main-content">
  <h2>Management Majors</h2>

  <?php if ($msg): ?><div class="alert success"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert error"><?= h($err) ?></div><?php endif; ?>

  <!-- Search -->
  <form method="get" style="display:flex;gap:8px;margin-bottom:12px">
    <input name="q" value="<?= h($q) ?>" placeholder="Search by ma nganh / ten nganh...">
    <button class="tab">Search</button>
    <a class="tab" href="nganh.php" style="text-decoration:none">N/A</a>
  </form>

  <!-- Sample text -->
  <div class="card">
    <?php if ($canEdit): ?>
      <?php if ($edit): ?>
        <h3>Edit Majors #<?= h($edit['id']) ?></h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:10px;align-items:center">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= h($edit['id']) ?>">

          <input name="maNganh"  value="<?= h($edit['maNganh']) ?>"  placeholder="Ma nganh"  required>
          <input name="tenNganh" value="<?= h($edit['tenNganh']) ?>" placeholder="Ten nganh" required>

          <select name="khoa_id" required>
            <option value="">-- Thuoc khoa --</option>
            <?php foreach($khoaList as $k): ?>
              <option value="<?= (int)$k['id'] ?>" <?= ((int)$edit['khoa_id']===(int)$k['id'])?'selected':'' ?>>
                <?= h($k['tenKhoa']) ?> (<?= h($k['maKhoa']) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <select name="bacDaoTao" required>
            <?php $bdtCur = $edit['bacDaoTao'] ?? 'ai hoc'; ?>
            <?php foreach($BAC_DAO_TAO as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($bdtCur===$opt)?'selected':'' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>

          <button class="tab">N/A</button>
          <a class="tab" href="nganh.php" style="text-decoration:none">N/A</a>
        </form>
      <?php else: ?>
        <h3>Add Majors</h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(4,1fr) auto;gap:10px;align-items:center">
          <input type="hidden" name="action" value="add">

          <input name="maNganh"  value="<?= h($maPost)  ?>" placeholder="Ma nganh"  required>
          <input name="tenNganh" value="<?= h($tenPost) ?>" placeholder="Ten nganh" required>

          <select name="khoa_id" required>
            <option value="">-- Thuoc khoa --</option>
            <?php foreach($khoaList as $k): ?>
              <option value="<?= (int)$k['id'] ?>" <?= ($khoaPost===(int)$k['id'])?'selected':'' ?>>
                <?= h($k['tenKhoa']) ?> (<?= h($k['maKhoa']) ?>)
              </option>
            <?php endforeach; ?>
          </select>

          <select name="bacDaoTao" required>
            <?php $defaultBDT = $bacPost ?: 'ai hoc'; ?>
            <?php foreach($BAC_DAO_TAO as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($defaultBDT===$opt)?'selected':'' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>

          <button class="tab">Add</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <div style="opacity:.9">You are in <b>view-only</b> mode. You don't have permission to add/edit/delete.</div>
    <?php endif; ?>
  </div>

  <!-- Bang danh sach -->
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Ma nganh</th>
        <th>Ten nganh</th>
        <th>Thuoc khoa</th>
        <th>Bac ao tao</th>
        <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($rows && $rows->num_rows) {
        while ($r = $rows->fetch_assoc()) {
          echo '<tr>'.
                 '<td>'.h($r['id']).'</td>'.
                 '<td>'.h($r['maNganh']).'</td>'.
                 '<td>'.h($r['tenNganh']).'</td>'.
                 '<td>'.h($r['tenKhoa']).' ('.h($r['maKhoa']).')</td>'.
                 '<td>'.h($r['bacDaoTao']).'</td>';
          if ($canEdit) {
            echo '<td style="display:flex;gap:8px">'.
                   '<a class="tab" href="nganh.php?edit='.h($r['id']).'">Edit</a>'.
                   '<a class="tab" style="background:#ef4444" href="nganh.php?delete='.h($r['id']).'" onclick="return confirm(\'Delete nganh nay?\\\')">Delete</a>'.
                 '</td>';
          }
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="'.($canEdit?6:5).'">No data.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<style>
  .card{background:#fff;border-radius:10px;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px}
  .alert{padding:10px;border-radius:8px;margin-bottom:10px}
  .alert.success{background:#ecfdf5;color:#065f46}
  .alert.error{background:#fee2e2;color:#991b1b}
</style>
</body>
</html>