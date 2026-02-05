<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php'; 
require __DIR__ . '/../backend/db.php';

if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

$role = $_SESSION['urole'] ?? 'viewer';
$uname = $_SESSION['uname'] ?? null;
$canEdit = in_array($role, ['admin','editor'], true);

$msg = '';
$err = '';

$DS_LOAI = [
    'Sample text',
    'Kien thuc co so nganh',
    'Kien thuc chuyen nganh',
    'Thuc tap & Khoa luan',
    'Other'
];

$DS_TRANG_THAI = ['Active', 'Sample text'];

if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    $maKKT = trim($_POST['maKKT'] ?? '');
    $tenKKT = trim($_POST['tenKKT'] ?? '');
    $tinChiMin = (int)($_POST['tinChiMin'] ?? 0);
    $tinChiMax = (int)($_POST['tinChiMax'] ?? 0);
    $loai = $_POST['loai'] ?? 'Other';
    $trangThai = $_POST['trangThai'] ?? 'Active';
    $khoa_id = (int)($_POST['khoa_id'] ?? 0); 

    if ($maKKT === '' || $tenKKT === '') {
        $err = 'Sample text';
    } elseif ($khoa_id <= 0) {
        $err = 'Please chon Department quan ly.';
    } elseif ($tinChiMin < 0 || $tinChiMax < 0) {
        $err = 'Sample text';
    } elseif ($tinChiMin > $tinChiMax) {
        $err = 'Sample text';
    }

    if ($err === '') {
        if ($action === 'add') {
            $chk = $conn->query("SELECT id FROM khoikienthuc WHERE maKKT = '$maKKT'");
            if ($chk && $chk->num_rows > 0) {
                $err = 'Sample text';
            } else {
                $sql = "INSERT INTO khoikienthuc (maKKT, tenKKT, tinChiMin, tinChiMax, loai, trangThai, khoa_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $st = $conn->prepare($sql);
                $st->bind_param('ssiissi', $maKKT, $tenKKT, $tinChiMin, $tinChiMax, $loai, $trangThai, $khoa_id);
                if ($st->execute()) {
                    $msg = 'Sample text';
                    $_POST = [];
                } else {
                    $err = 'Sample text' . $st->error;
                }
                $st->close();
            }
        }

        if ($action === 'update') {
            $sql = "UPDATE khoikienthuc SET maKKT=?, tenKKT=?, tinChiMin=?, tinChiMax=?, loai=?, trangThai=?, khoa_id=? WHERE id=?";
            $st = $conn->prepare($sql);
            $st->bind_param('ssiissii', $maKKT, $tenKKT, $tinChiMin, $tinChiMax, $loai, $trangThai, $khoa_id, $id);
            if ($st->execute()) {
                $msg = 'Sample text';
            } else {
                $err = 'Error cap nhat: ' . $st->error;
            }
            $st->close();
        }
    }
}

if ($canEdit && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $conn->query("DELETE FROM khoikienthuc WHERE id=$id");
        $msg = 'Sample text';
    }
}

$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $rs = $conn->query("SELECT * FROM khoikienthuc WHERE id=$id");
    if ($rs) $edit = $rs->fetch_assoc();
}

$listKKT = [];
$sql = "SELECT kkt.*, k.tenKhoa 
        FROM khoikienthuc kkt 
        LEFT JOIN khoa k ON kkt.khoa_id = k.id 
        ORDER BY kkt.id DESC";
$res = $conn->query($sql);
if ($res) $listKKT = $res->fetch_all(MYSQLI_ASSOC);

$listKhoa = [];
$rk = $conn->query("SELECT id, tenKhoa FROM khoa ORDER BY tenKhoa");
if ($rk) $listKhoa = $rk->fetch_all(MYSQLI_ASSOC);

$vMa = $edit['maKKT'] ?? $_POST['maKKT'] ?? '';
$vTen = $edit['tenKKT'] ?? $_POST['tenKKT'] ?? '';
$vMin = $edit['tinChiMin'] ?? $_POST['tinChiMin'] ?? 0;
$vMax = $edit['tinChiMax'] ?? $_POST['tinChiMax'] ?? 0;
$vLoai = $edit['loai'] ?? $_POST['loai'] ?? $DS_LOAI[0];
$vTrangThai = $edit['trangThai'] ?? $_POST['trangThai'] ?? 'Active';
$vKhoa = $edit['khoa_id'] ?? $_POST['khoa_id'] ?? 0;

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>N/A</title>
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
      <?php if ($uname): ?>
        <div><i class="fa-solid fa-user-shield"></i> <?= h($role ?? 'user') ?>: <b><?= h($uname) ?></b></div>
        <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php" style="color:#e5e7eb;text-decoration:underline">Logout</a></div>
      <?php endif; ?>
    </div>
</div>

<div class="main-content">
  <h2>N/A</h2>

  <?php if ($msg): ?>
    <div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div>
  <?php endif; ?>

  <?php if ($canEdit): ?>
    <div style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px">
      <h3><?= $edit ? 'Sample text' : 'Sample text' ?>N/A</h3>
      <form method="post" action="khoikienthuc.php" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
        <input type="hidden" name="action" value="<?= $edit ? 'update' : 'add' ?>">
        <input type="hidden" name="id" value="<?= h($edit['id'] ?? 0) ?>">

        <div>
            <label style="font-size:12px;color:#666">N/A</label>
            <input name="maKKT" value="<?= h($vMa) ?>" placeholder="VD: GDDC" required>
        </div>

        <div>
            <label style="font-size:12px;color:#666">N/A</label>
            <input name="tenKKT" value="<?= h($vTen) ?>" placeholder="Enter value" required>
        </div>

        <div>
            <label style="font-size:12px;color:#666">N/A</label>
            <input type="number" name="tinChiMin" value="<?= h($vMin) ?>" min="0">
        </div>

        <div>
            <label style="font-size:12px;color:#666">N/A</label>
            <input type="number" name="tinChiMax" value="<?= h($vMax) ?>" min="0">
        </div>

        <div>
            <label style="font-size:12px;color:#666">N/A</label>
            <select name="loai">
                <?php foreach ($DS_LOAI as $l): ?>
                    <option value="<?= h($l) ?>" <?= $vLoai == $l ? 'selected' : '' ?>><?= h($l) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size:12px;color:#666">Status</label>
            <select name="trangThai">
                <?php foreach ($DS_TRANG_THAI as $tt): ?>
                    <option value="<?= h($tt) ?>" <?= $vTrangThai == $tt ? 'selected' : '' ?>><?= h($tt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="grid-column: span 2">
            <label style="font-size:12px;color:#666">N/A<span style="color:red">*</span></label>
            <select name="khoa_id" required>
                <option value="">N/A</option>
                <?php foreach ($listKhoa as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $vKhoa == $k['id'] ? 'selected' : '' ?>><?= h($k['tenKhoa']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="grid-column:1/-1; margin-top:10px">
          <button class="tab"><?= $edit ? 'Luu Cap Nhat' : 'Sample text' ?></button>
          <?php if ($edit): ?>
            <a href="khoikienthuc.php" class="tab" style="text-decoration:none;background:#eee;color:#333">N/A</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <table>
    <thead>
      <tr>
        <th>N/A</th>
        <th>N/A</th>
        <th>N/A</th>
        <th>N/A</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($listKKT) > 0): ?>
        <?php foreach ($listKKT as $item): ?>
          <tr>
            <td><?= h($item['maKKT']) ?></td>
            <td>
                <?= h($item['tenKKT']) ?>
                <?php if($item['tenKhoa']): ?>
                    <br><span style="font-size:11px;color:#888">(<?= h($item['tenKhoa']) ?>)</span>
                <?php endif; ?>
            </td>
            <td><?= h($item['tinChiMin']) ?> - <?= h($item['tinChiMax']) ?></td>
            <td><?= h($item['loai']) ?></td>
            <td>
                <?php if($item['trangThai'] == 'Active'): ?>
                    <span style="color:green;font-weight:500">Active</span>
                <?php else: ?>
                    <span style="color:red">N/A</span>
                <?php endif; ?>
            </td>
            <td>
              <?php if ($canEdit): ?>
                <a class="tab" href="khoikienthuc.php?edit=<?= $item['id'] ?>">Edit</a>
                <a class="tab" style="background:#ef4444" href="khoikienthuc.php?delete=<?= $item['id'] ?>" onclick="return confirm('Sample text')">Delete</a>
              <?php else: ?>
                <span style="color:#999">N/A</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">N/A</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
</body>
</html>