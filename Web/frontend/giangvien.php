<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require __DIR__ . '/../auth_php_pack/auth_guard.php';
require __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }


function normalize_phone($tel) {
    $digits = preg_replace('/\D+/', '', $tel ?? '');
    if ($digits === '') return '';
    if ($digits[0] === '0') {
        $digits = '+84' . substr($digits, 1);
    } elseif ($digits[0] !== '+' && str_starts_with($digits, '84')) {
        $digits = '+'.$digits;
    }
    return $digits;
}

$msg = '';
$err = '';


$role      = $_SESSION['urole'] ?? 'viewer';
$canEdit   = in_array($role, ['admin','editor'], true);
$canDelete = ($role === 'admin');


$khoa = [];
$kr = $conn->query("SELECT id, maKhoa, tenKhoa FROM khoa ORDER BY tenKhoa ASC");
if ($kr) while ($row = $kr->fetch_assoc()) $khoa[] = $row;


if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    
    if ($action === 'import') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $err = 'Please choose a valid CSV file.';
        } else {
            $tmp  = $_FILES['csv_file']['tmp_name'];
            $fh   = fopen($tmp, 'r');
            if (!$fh) {
                $err = 'Unable to read the CSV file.';
            } else {
                $header = fgetcsv($fh, 0, ',');
                $okCount = 0;
                $failCount = 0;

                while (($row = fgetcsv($fh, 0, ',')) !== false) {
                    $maGV      = trim($row[0] ?? '');
                    $hoTen     = trim($row[1] ?? '');
                    $ngaySinh  = trim($row[2] ?? '');
                    $gioiTinh  = trim($row[3] ?? 'Other');
                    $hocHam    = trim($row[4] ?? '');
                    $hocVi     = trim($row[5] ?? '');
                    $khoa_id   = (int)($row[6] ?? 0);
                    $email     = trim($row[7] ?? '');
                    $telRaw    = trim($row[8] ?? '');
                    $dienThoai = normalize_phone($telRaw);
                    $diaChi    = trim($row[9] ?? '');
                    $trangThai = trim($row[10] ?? 'Active');

                    if ($maGV === '' || $hoTen === '' || $email === '' || $khoa_id <= 0) {
                        $failCount++; continue;
                    }
                    if (!preg_match('/^[\p{L}\s]+$/u', $hoTen)) { $failCount++; continue; }
                    if ($hocHam !== '' && !preg_match('/^[\p{L}\s]+$/u', $hocHam)) { $failCount++; continue; }
                    if ($hocVi  !== '' && !preg_match('/^[\p{L}\s]+$/u', $hocVi))  { $failCount++; continue; }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $failCount++; continue; }
                    if ($telRaw !== '' && !preg_match('/^(0\d{9}|\+84\d{9})$/', $telRaw)) {
                        $failCount++; continue;
                    }
                    $chk = $conn->prepare("SELECT id FROM khoa WHERE id=?");
                    $chk->bind_param('i', $khoa_id);
                    $chk->execute();
                    $chk->store_result();
                    if ($chk->num_rows === 0) { $chk->close(); $failCount++; continue; }
                    $chk->close();

                    $st = $conn->prepare("SELECT id FROM giangvien WHERE maGV=? OR email=? LIMIT 1");
                    $st->bind_param('ss', $maGV, $email);
                    $st->execute();
                    $st->store_result();
                    if ($st->num_rows > 0) {
                        $st->close();
                        $failCount++;
                        continue;
                    }
                    $st->close();

                    $st = $conn->prepare("
                        INSERT INTO giangvien
                          (maGV, hoTen, ngaySinh, gioiTinh, hocHam, hocVi, khoa_id, email, dienThoai, diaChi, trangThai)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)
                    ");
                    $st->bind_param(
                        'ssssssissss',
                        $maGV, $hoTen, $ngaySinh, $gioiTinh, $hocHam, $hocVi, $khoa_id, $email, $dienThoai, $diaChi, $trangThai
                    );
                    if ($st->execute()) $okCount++; else $failCount++;
                    $st->close();
                }
                fclose($fh);
                $msg = "CSV import complete: $okCount rows added successfully, $failCount rows skipped.";
            }
        }
    }
    
    else {
        $maGV       = trim($_POST['maGV']      ?? '');
        $hoTen      = trim($_POST['hoTen']     ?? '');
        $ngaySinh   = $_POST['ngaySinh']       ?? '';
        $gioiTinh   = $_POST['gioiTinh']       ?? 'Other';
        $hocHam     = trim($_POST['hocHam']    ?? '');
        $hocVi      = trim($_POST['hocVi']     ?? '');
        $khoa_id    = (int)($_POST['khoa_id']  ?? 0);
        $email      = trim($_POST['email']     ?? '');

        $dienThoaiRaw = trim($_POST['dienThoai'] ?? '');
        $dienThoai    = normalize_phone($dienThoaiRaw);

        $diaChi     = trim($_POST['diaChi']    ?? '');
        $trangThai  = $_POST['trangThai']      ?? 'Active';

        if ($maGV === '' || $hoTen === '' || $khoa_id <= 0 || $email === '') {
            $err = 'Please enter Lecturer ID, Full Malee, Department, and Email.';
        }
        elseif (!preg_match('/^[\p{L}\s]+$/u', $hoTen)) {
            $err = 'Full name may contain letters and spaces only.';
        }
        elseif ($hocHam !== '' && !preg_match('/^[\p{L}\s]+$/u', $hocHam)) {
            $err = 'Academic title may contain letters and spaces only.';
        }
        elseif ($hocVi !== '' && !preg_match('/^[\p{L}\s]+$/u', $hocVi)) {
            $err = 'Degree may contain letters and spaces only.';
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Invalid email.';
        }
        elseif ($dienThoaiRaw !== '' && !preg_match('/^(0\d{9}|\+84\d{9})$/', $dienThoaiRaw)) {
            $err = 'Invalid phone number. Enter 10 digits (starting with 0) or +84xxxxxxxxx.';
        }
        else {
            if ($action === 'add') {
                $st = $conn->prepare("SELECT id FROM giangvien WHERE maGV=? OR email=? LIMIT 1");
                $st->bind_param('ss', $maGV, $email);
                $st->execute();
                $st->store_result();
                if ($st->num_rows > 0) {
                    $err = 'Sample text';
                }
                $st->close();

                if (!$err) {
                    $st = $conn->prepare("
                        INSERT INTO giangvien
                          (maGV, hoTen, ngaySinh, gioiTinh, hocHam, hocVi, khoa_id, email, dienThoai, diaChi, trangThai)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)
                    ");
                    $st->bind_param(
                        'ssssssissss',
                        $maGV, $hoTen, $ngaySinh, $gioiTinh, $hocHam, $hocVi, $khoa_id, $email, $dienThoai, $diaChi, $trangThai
                    );
                    $ok = $st->execute();
                    $st->close();

                    if ($ok) {
                        $msg = 'Sample text';
                        $_POST = [];
                    } else {
                        $err = 'Sample text' . $conn->error;
                    }
                }
            }

            if ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    $err = 'Thieu ID e cap nhat.';
                } else {
                    $st = $conn->prepare("SELECT id FROM giangvien WHERE (maGV=? OR email=?) AND id<>? LIMIT 1");
                    $st->bind_param('ssi', $maGV, $email, $id);
                    $st->execute();
                    $st->store_result();
                    if ($st->num_rows > 0) {
                        $err = 'Sample text';
                    }
                    $st->close();

                    if (!$err) {
                        $st = $conn->prepare("
                            UPDATE giangvien
                               SET maGV=?, hoTen=?, ngaySinh=?, gioiTinh=?, hocHam=?, hocVi=?, khoa_id=?, email=?, dienThoai=?, diaChi=?, trangThai=?
                             WHERE id=?
                        ");
                        $st->bind_param(
                            'ssssssissssi',
                            $maGV, $hoTen, $ngaySinh, $gioiTinh, $hocHam, $hocVi, $khoa_id, $email, $dienThoai, $diaChi, $trangThai, $id
                        );
                        $ok = $st->execute();
                        $st->close();

                        $msg = $ok ? 'Sample text' : ('Error khi cap nhat: ' . $conn->error);
                    }
                }
            }
        }
    }
}


if ($canDelete && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        try {
            $st = $conn->prepare("DELETE FROM giangvien WHERE id=?");
            $st->bind_param('i', $id);
            $ok = $st->execute();
            $st->close();
            $msg = $ok ? 'Sample text' : 'Sample text';
        } catch (Throwable $e) {
            $err = 'Sample text'.$e->getMessage();
        }
    }
}


$q = trim($_GET['q'] ?? '');
$where = $q !== ''
    ? "WHERE gv.hoTen LIKE '%" . $conn->real_escape_string($q) . "%' OR gv.maGV LIKE '%" . $conn->real_escape_string($q) . "%'"
    : '';

$sql = "
SELECT gv.id, gv.maGV, gv.hoTen, gv.email, gv.dienThoai, gv.trangThai,
       gv.ngaySinh, gv.gioiTinh, gv.hocHam, gv.hocVi, gv.diaChi,
       k.id AS khoa_id, k.tenKhoa
  FROM giangvien gv
  JOIN khoa k ON k.id = gv.khoa_id
  $where
 ORDER BY gv.id DESC";
$rows = $conn->query($sql);


if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'giangvien_'.date('Ymd_His').'.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');

    $out = fopen('php://output', 'w');

    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($out, [
        'ID','maGV','hoTen','ngaySinh','gioiTinh',
        'hocHam','hocVi','khoa','email','dienThoai','diaChi','trangThai'
    ]);

    if ($rows && $rows->num_rows) {
        $rows->data_seek(0);
        while ($r = $rows->fetch_assoc()) {

            $tel = $r['dienThoai'];
            if ($tel !== '') {
                $tel = '="'.$tel.'"';
            }

            fputcsv($out, [
                $r['id'],
                $r['maGV'],
                $r['hoTen'],
                $r['ngaySinh'],
                $r['gioiTinh'],
                $r['hocHam'],
                $r['hocVi'],
                $r['tenKhoa'],
                $r['email'],
                $tel,
                $r['diaChi'],
                $r['trangThai'],
            ]);
        }
    }

    fclose($out);
    exit;
}



$edit = null;
if ($canEdit && isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r = $conn->query("SELECT * FROM giangvien WHERE id=$eid");
    if ($r && $r->num_rows) $edit = $r->fetch_assoc();
}


$maGVPost      = $_POST['maGV']      ?? '';
$hoTenPost     = $_POST['hoTen']     ?? '';
$ngaySinhPost  = $_POST['ngaySinh']  ?? '';
$gioiTinhPost  = $_POST['gioiTinh']  ?? 'Other';
$hocHamPost    = $_POST['hocHam']    ?? '';
$hocViPost     = $_POST['hocVi']     ?? '';
$khoaIdPost    = (int)($_POST['khoa_id'] ?? 0);
$emailPost     = $_POST['email']     ?? '';
$dienThoaiPost = $_POST['dienThoai'] ?? '';
$diaChiPost    = $_POST['diaChi']    ?? '';
$trangThaiPost = $_POST['trangThai'] ?? 'Active';

$base = rtrim(dirname($_SERVER['PHP_SELF']), "/\\") . '/';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Management Lecturers</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    
    .modal-backdrop{
      position:fixed;inset:0;background:rgba(0,0,0,.35);
      display:none;align-items:center;justify-content:center;z-index:50;
    }
    .modal-backdrop.show{display:flex;}
    .modal-card{
      background:#fff;border-radius:12px;padding:16px 20px;max-width:480px;width:100%;
      box-shadow:0 10px 40px rgba(15,23,42,.2);font-size:14px;
    }
    .modal-card h3{margin-top:0;margin-bottom:10px;font-size:18px}
    .modal-row{display:flex;justify-content:space-between;margin-bottom:6px;gap:8px}
    .modal-label{color:#6b7280;min-width:90px}
    .modal-value{font-weight:500}
  </style>
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
  <h2>Management Lecturers</h2>

  <?php if ($msg): ?><div style="background:#ecfdf5;color:#065f46;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div style="background:#fee2e2;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:10px"><?= h($err) ?></div><?php endif; ?>

  <form method="get" style="display:flex;gap:8px;margin-bottom:12px">
    <input name="q" value="<?= h($q ?? '') ?>" placeholder="Enter value">
    <button class="tab">Search</button>
    <a class="tab" href="giangvien.php" style="text-decoration:none">N/A</a>
  </form>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:12px">
    <a class="tab" href="giangvien.php?export=csv" style="text-decoration:none">Export CSV</a>

    <?php if ($canEdit): ?>
      <form method="post" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center">
        <input type="hidden" name="action" value="import">
        <input type="file" name="csv_file" accept=".csv" style="max-width:220px">
        <button class="tab">Import CSV</button>
      </form>
    <?php endif; ?>
  </div>

  <div style="background:#fff;border-radius:10px;padding:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px">
    <?php if ($canEdit): ?>
      <?php if ($edit): ?>
        <h3>Edit Lecturers #<?= h($edit['id']) ?></h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= h($edit['id']) ?>">

          <input name="maGV"
                 value="<?= h($edit['maGV']) ?>"
                 placeholder="Lecturer ID"
                 required>

          <input name="hoTen"
                 value="<?= h($edit['hoTen']) ?>"
                 placeholder="Full name"
                 required
                 pattern=".*"
                 title="Letters and spaces only">

          <input type="date" name="ngaySinh" value="<?= h($edit['ngaySinh'] ?? '') ?>">

          <select name="gioiTinh">
            <?php $gt = $edit['gioiTinh'] ?? 'Other'; ?>
            <option value="Nam"   <?= $gt==='Nam'?'selected':'' ?>>Nam</option>
            <option value="Female"    <?= $gt==='Female'?'selected':'' ?>>Female</option>
            <option value="Other"  <?= $gt==='Other'?'selected':'' ?>>Other</option>
          </select>

          <input name="hocHam"
                 value="<?= h($edit['hocHam'] ?? '') ?>"
                 placeholder="Academic title (Prof., Assoc. Prof...)"
                 pattern=".*"
                 title="Letters and spaces only">

          <input name="hocVi"
                 value="<?= h($edit['hocVi']  ?? '') ?>"
                 placeholder="Degree (PhD, MSc...)"
                 pattern=".*"
                 title="Letters and spaces only">

          <select name="khoa_id" required>
            <option value="">-- Thuoc khoa --</option>
            <?php foreach ($khoa as $k): ?>
              <option value="<?= h($k['id']) ?>" <?= ((int)$edit['khoa_id']===(int)$k['id'])?'selected':'' ?>>
                <?= h($k['tenKhoa']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="email" name="email" value="<?= h($edit['email']) ?>" placeholder="Email" required>

          <input name="dienThoai"
                 value="<?= h($edit['dienThoai'] ?? '') ?>"
                 placeholder="Enter value"
                 pattern="(0\d{9}|\+84\d{9})"
                 title="Enter a valid value">

          <input name="diaChi" value="<?= h($edit['diaChi'] ?? '') ?>" placeholder="Address">

          <select name="trangThai">
            <?php $tt = $edit['trangThai'] ?? 'Active'; ?>
            <option value="Active" <?= $tt==='Active'?'selected':'' ?>>Active</option>
            <option value="Sample text"      <?= $tt==='Sample text'?'selected':'' ?>N/A</option>
            <option value="Sample text"       <?= $tt==='Sample text'?'selected':'' ?>N/A</option>
          </select>

          <div style="grid-column:1/-1;display:flex;gap:8px">
            <button class="tab">N/A</button>
            <a class="tab" href="giangvien.php" style="text-decoration:none">N/A</a>
          </div>
        </form>
      <?php else: ?>
        <h3>Add Lecturers</h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px">
          <input type="hidden" name="action" value="add">

          <input name="maGV"
                 value="<?= h($maGVPost) ?>"
                 placeholder="Lecturer ID"
                 required>

          <input name="hoTen"
                 value="<?= h($hoTenPost) ?>"
                 placeholder="Full name"
                 required
                 pattern=".*"
                 title="Letters and spaces only">

          <input type="date" name="ngaySinh" value="<?= h($ngaySinhPost) ?>">

          <select name="gioiTinh">
            <option value="Nam"  <?= $gioiTinhPost==='Nam'?'selected':'' ?>>Nam</option>
            <option value="Female"   <?= $gioiTinhPost==='Female'?'selected':'' ?>>Female</option>
            <option value="Other" <?= $gioiTinhPost==='Other'?'selected':'' ?>>Other</option>
          </select>

          <input name="hocHam"
                 value="<?= h($hocHamPost) ?>"
                 placeholder="Academic title (Prof., Assoc. Prof...)"
                 pattern=".*"
                 title="Letters and spaces only">

          <input name="hocVi"
                 value="<?= h($hocViPost)  ?>"
                 placeholder="Degree (PhD, MSc...)"
                 pattern=".*"
                 title="Letters and spaces only">

          <select name="khoa_id" required>
            <option value="">-- Thuoc khoa --</option>
            <?php foreach ($khoa as $k): ?>
              <option value="<?= h($k['id']) ?>" <?= ((int)$khoaIdPost===(int)$k['id'])?'selected':'' ?>>
                <?= h($k['tenKhoa']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input type="email" name="email" value="<?= h($emailPost) ?>" placeholder="Email" required>

          <input name="dienThoai"
                 value="<?= h($dienThoaiPost) ?>"
                 placeholder="Enter value"
                 pattern="(0\d{9}|\+84\d{9})"
                 title="Enter a valid value">

          <input name="diaChi" value="<?= h($diaChiPost) ?>" placeholder="Address">

          <select name="trangThai">
            <option value="Active" <?= $trangThaiPost==='Active'?'selected':'' ?>>Active</option>
            <option value="Sample text"      <?= $trangThaiPost==='Sample text'?'selected':'' ?>N/A</option>
            <option value="Sample text"       <?= $trangThaiPost==='Sample text'?'selected':'' ?>N/A</option>
          </select>

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
        <th>Lecturer ID</th>
        <th>Full name</th>
        <th>Date of birth</th>
        <th>Gender</th>
        <th>Hoc ham</th>
        <th>Hoc vi</th>
        <th>Department</th>
        <th>Email</th>
        <th>ien thoai</th>
        <th>Address</th>
        <th>Status</th>
        <?php if ($canEdit || $canDelete): ?><th>Actions</th><?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($rows && $rows->num_rows) {
        while ($r = $rows->fetch_assoc()) {
          echo '<tr>'.
            '<td>'.h($r['maGV']).'</td>'.

            '<td><a href="#"
                     class="gv-detail"
                     data-id="'.h($r['id']).'"
                     data-magv="'.h($r['maGV']).'"
                     data-hoten="'.h($r['hoTen']).'"
                     data-ngaysinh="'.h($r['ngaySinh']).'"
                     data-gioitinh="'.h($r['gioiTinh']).'"
                     data-hocham="'.h($r['hocHam']).'"
                     data-hocvi="'.h($r['hocVi']).'"
                     data-khoa="'.h($r['tenKhoa']).'"
                     data-email="'.h($r['email']).'"
                     data-dienthoai="'.h($r['dienThoai']).'"
                     data-diachi="'.h($r['diaChi']).'"
                     data-trangthai="'.h($r['trangThai']).'"
                  >'.h($r['hoTen']).'</a></td>'.

            '<td>'.h($r['ngaySinh']).'</td>'.
            '<td>'.h($r['gioiTinh']).'</td>'.
            '<td>'.h($r['hocHam']).'</td>'.
            '<td>'.h($r['hocVi']).'</td>'.
            '<td>'.h($r['tenKhoa']).'</td>'.
            '<td>'.h($r['email']).'</td>'.
            '<td>'.h($r['dienThoai']).'</td>'.
            '<td>'.h($r['diaChi']).'</td>'.
            '<td>'.h($r['trangThai']).'</td>';

          if ($canEdit || $canDelete) {
            echo '<td style="display:flex;gap:8px">';
            if ($canEdit) {
              echo '<a class="tab" href="giangvien.php?edit='.h($r['id']).'">Edit</a>';
            }
            if ($canDelete) {
              echo '<a class="tab" style="background:#ef4444" href="giangvien.php?delete='.h($r['id']).'" onclick="return confirm(\'Sample text';
            }
            echo '</td>';
          }
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="'.(($canEdit || $canDelete)?12:11).'">No data.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

<div class="modal-backdrop" id="gvModal">
  <div class="modal-card">
    <h3 id="mTitle">Thong tin giang vien</h3>
    <div class="modal-row"><div class="modal-label">Lecturer ID</div><div class="modal-value" id="mMaGV"></div></div>
    <div class="modal-row"><div class="modal-label">Full name</div><div class="modal-value" id="mHoTen"></div></div>
    <div class="modal-row"><div class="modal-label">Date of birth</div><div class="modal-value" id="mNgaySinh"></div></div>
    <div class="modal-row"><div class="modal-label">Gender</div><div class="modal-value" id="mGioiTinh"></div></div>
    <div class="modal-row"><div class="modal-label">Hoc ham</div><div class="modal-value" id="mHocHam"></div></div>
    <div class="modal-row"><div class="modal-label">Hoc vi</div><div class="modal-value" id="mHocVi"></div></div>
    <div class="modal-row"><div class="modal-label">Khoa</div><div class="modal-value" id="mKhoa"></div></div>
    <div class="modal-row"><div class="modal-label">Email</div><div class="modal-value" id="mEmail"></div></div>
    <div class="modal-row"><div class="modal-label"Sample text"modal-value" id="mDienThoai"></div></div>
    <div class="modal-row"><div class="modal-label">Address</div><div class="modal-value" id="mDiaChi"></div></div>
    <div class="modal-row"><div class="modal-label">Status</div><div class="modal-value" id="mTrangThai"></div></div>
    <div style="text-align:right;margin-top:12px">
      <button class="tab" type="button" onclick="closeGvModal()">N/A</button>
    </div>
  </div>
</div>

<script>
  const modal = document.getElementById('gvModal');
  const mapEl = {
    maGV: document.getElementById('mMaGV'),
    hoTen: document.getElementById('mHoTen'),
    ngaySinh: document.getElementById('mNgaySinh'),
    gioiTinh: document.getElementById('mGioiTinh'),
    hocHam: document.getElementById('mHocHam'),
    hocVi: document.getElementById('mHocVi'),
    khoa: document.getElementById('mKhoa'),
    email: document.getElementById('mEmail'),
    dienThoai: document.getElementById('mDienThoai'),
    diaChi: document.getElementById('mDiaChi'),
    trangThai: document.getElementById('mTrangThai'),
  };

  document.querySelectorAll('.gv-detail').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const d = link.dataset;
      mapEl.maGV.textContent = d.magv;
      mapEl.hoTen.textContent = d.hoten;
      mapEl.ngaySinh.textContent = d.ngaysinh;
      mapEl.gioiTinh.textContent = d.gioitinh;
      mapEl.hocHam.textContent = d.hocham;
      mapEl.hocVi.textContent = d.hocvi;
      mapEl.khoa.textContent = d.khoa;
      mapEl.email.textContent = d.email;
      mapEl.dienThoai.textContent = d.dienthoai;
      mapEl.diaChi.textContent = d.diachi;
      mapEl.trangThai.textContent = d.trangthai;
      modal.classList.add('show');
    });
  });

  function closeGvModal(){
    modal.classList.remove('show');
  }
  modal.addEventListener('click', e => {
    if(e.target === modal) closeGvModal();
  });
</script>
</body>
</html>