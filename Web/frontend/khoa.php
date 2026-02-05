<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = '';
$err = '';


$role    = $_SESSION['urole'] ?? 'viewer';
$canEdit = in_array($role, ['admin','editor'], true);


function existsBy($conn, string $col, string $val, int $excludeId = 0): bool {
    $sql = "SELECT id FROM khoa WHERE $col = ?" . ($excludeId ? " AND id <> ?" : "") . " LIMIT 1";
    $st  = $conn->prepare($sql);
    if ($excludeId) $st->bind_param('si', $val, $excludeId);
    else            $st->bind_param('s',  $val);
    $st->execute();
    $res = $st->get_result();
    $ok  = (bool)$res->fetch_row();
    $st->close();
    return $ok;
}


if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action        = $_POST['action']       ?? '';
    $id            = (int)($_POST['id']      ?? 0);
    $maKhoa        = trim($_POST['maKhoa']   ?? '');
    $tenKhoa       = trim($_POST['tenKhoa']  ?? '');
    $ngayThanhLap  = trim($_POST['ngayThanhLap'] ?? '');
    $trangThai     = $_POST['trangThai']     ?? 'Active';

    if ($maKhoa === '' || $tenKhoa === '' || $ngayThanhLap === '') {
        $err = 'Please enter Department Code, Department Name, and Established Date.';
    } else {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ngayThanhLap)) {
            $err = 'Invalid established date (format yyyy-mm-dd).';
        }
    }

    if ($err === '') {
        if ($action === 'add') {
            if (existsBy($conn, 'maKhoa',  $maKhoa))  $err = "Department code '".h($maKhoa)."' already exists.";
            elseif (existsBy($conn, 'tenKhoa', $tenKhoa)) $err = "Department name '".h($tenKhoa)."' already exists.";

            if ($err === '') {
                $st = $conn->prepare('INSERT INTO khoa (maKhoa, tenKhoa, ngayThanhLap, trangThai) VALUES (?,?,?,?)');
                $st->bind_param('ssss', $maKhoa, $tenKhoa, $ngayThanhLap, $trangThai);
                $ok = $st->execute();
                $st->close();

                if ($ok) {
                    $msg  = 'Department added successfully!';
                    $_POST = [];
                } else {
                    $err = 'Error adding department: ' . $conn->error;
                }
            }
        }

        if ($action === 'update') {
            if ($id <= 0) {
                $err = 'Thieu ID e cap nhat.';
            } else {
                if (existsBy($conn, 'maKhoa',  $maKhoa,  $id))      $err = "Department code '".h($maKhoa)."Sample text";
                elseif (existsBy($conn, 'tenKhoa', $tenKhoa, $id)) $err = "Department name '".h($tenKhoa)."Sample text";

                if ($err === '') {
                    $st = $conn->prepare('UPDATE khoa SET maKhoa=?, tenKhoa=?, ngayThanhLap=?, trangThai=? WHERE id=?');
                    $st->bind_param('ssssi', $maKhoa, $tenKhoa, $ngayThanhLap, $trangThai, $id);
                    $ok = $st->execute();
                    $st->close();

                    $msg = $ok ? 'a cap nhat thong tin khoa thanh cong!' : ('Error khi cap nhat: ' . $conn->error);
                }
            }
        }
    }
}


if ($canEdit && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $count_gv    = (int)$conn->query("SELECT COUNT(*) FROM giangvien WHERE khoa_id = $id")->fetch_row()[0];
        $count_nganh = (int)$conn->query("SELECT COUNT(*) FROM nganh    WHERE khoa_id = $id")->fetch_row()[0];

        if ($count_gv > 0 || $count_nganh > 0) {
            $err = "No the xoa khoa nay vi co du lieu lien quan trong Lecturers hoac Majors.";
        } else {
            try {
                if ($conn->query("DELETE FROM khoa WHERE id=$id")) {
                    $msg = 'a xoa khoa thanh cong.';
                } else {
                    $err = 'No the xoa khoa nay.';
                }
            } catch (Throwable $e) {
                $err = 'No the xoa khoa: '.$e->getMessage();
            }
        }
    }
}


$q      = trim($_GET['q'] ?? '');
$where  = $q !== '' ? "WHERE tenKhoa LIKE '%" . $conn->real_escape_string($q) . "%'" : '';
$rows   = $conn->query("SELECT * FROM khoa $where ORDER BY id DESC");

$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $conn->query("SELECT * FROM khoa WHERE id=$eid");
    if ($r && $r->num_rows) $edit = $r->fetch_assoc();
}


$maKhoaPost        = $_POST['maKhoa']       ?? '';
$tenKhoaPost       = $_POST['tenKhoa']      ?? '';
$ngayThanhLapPost  = $_POST['ngayThanhLap'] ?? '';
$trangThaiPost     = $_POST['trangThai']    ?? 'Active';

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Department Management</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="sidebar">
  <div class="logo">
    <i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span>
  </div>
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
  <h2>Department Management</h2>

  <?php if ($msg): ?>
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:8px;margin-bottom:12px">
    <input name="q" value="<?= h($q) ?>" placeholder="Search by department name...">
    <button class="tab">Search</button>
    <a class="tab" href="khoa.php" style="text-decoration:none">N/A</a>
  </form>

  <div style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px">
  <?php if ($canEdit): ?>
    <?php if ($edit): ?>
      <h3>Edit Khoa #<?= h($edit['id']) ?></h3>
      <form method="post" style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;align-items:center">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= h($edit['id']) ?>">

        <input name="maKhoa"       value="<?= h($edit['maKhoa']       ?? '') ?>" placeholder="Department code" required>
        <input name="tenKhoa"      value="<?= h($edit['tenKhoa']      ?? '') ?>" placeholder="Department name" required>
        <input type="date" name="ngayThanhLap" value="<?= h($edit['ngayThanhLap'] ?? '') ?>" required>

        <select name="trangThai" required>
          <?php $editTrangThai = $edit['trangThai'] ?? 'Active'; ?>
          <option value="Active"       <?= ($editTrangThai === 'Active') ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= ($editTrangThai === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button class="tab">N/A</button>
        <a class="tab" href="khoa.php" style="text-decoration:none">N/A</a>
      </form>
    <?php else: ?>
      <h3>Add Department</h3>
      <form method="post" style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;align-items:center">
        <input type="hidden" name="action" value="add">

        <input name="maKhoa"        value="<?= h($maKhoaPost) ?>"       placeholder="Department code" required>
        <input name="tenKhoa"       value="<?= h($tenKhoaPost) ?>"      placeholder="Department name" required>
        <input type="date" name="ngayThanhLap" value="<?= h($ngayThanhLapPost) ?>" required>

        <select name="trangThai" required>
          <option value="Active"       <?= ($trangThaiPost === 'Active') ? 'selected' : '' ?>>Active</option>
          <option value="Inactive" <?= ($trangThaiPost === 'Inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button class="tab">Add Department</button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <div style="opacity:.9">You are in <b>view-only</b> mode. You don't have permission to add/edit/delete.</div>
  <?php endif; ?>
  </div>

  <table>
    <thead>
      <tr>
        <th>Department Code</th>
        <th>Department Name</th>
        <th>Established Date</th>
        <th>Status</th>
        <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($rows && $rows->num_rows) {
        while ($r = $rows->fetch_assoc()) {
          echo '<tr>'.
                 '<td>'.h($r['maKhoa']).'</td>'.
                 '<td>'.h($r['tenKhoa']).'</td>'.
                 '<td>'.h($r['ngayThanhLap']).'</td>'.
                 '<td>';
if ($r['trangThai'] === 'Active') {
    echo '<span style="color:green;font-weight:500">Active</span>';
} else {
    echo '<span style="color:red">'.h($r['trangThai']).'</span>';
}
echo '</td>';
          if ($canEdit) {
            echo '<td style="display:flex;gap:8px">'.
                   '<a class="tab" href="khoa.php?edit='.h($r['id']).'">Edit</a>'.
                   '<a class="tab" style="background:#ef4444" href="khoa.php?delete='.h($r['id']).'" onclick="return confirm(\'Delete this department?\')">Delete</a>'.
                 '</td>';
          }
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="'.($canEdit?5:4).'">No data.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>
</body>
</html>