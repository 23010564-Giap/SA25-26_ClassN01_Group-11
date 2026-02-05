<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../backend/db.php';
function h($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }

$uname = $_SESSION['uname'] ?? null;
$urole = $_SESSION['urole'] ?? null;
$base  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

$news_item = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $st->bind_param('i', $id);
    $st->execute();
    $news_item = $st->get_result()->fetch_assoc();
    $st->close();
}

$cur = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $news_item ? h($news_item['title']) : 'Sample text' ?></title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .userbox{margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px}
    .userbox a{color:#e5e7eb;text-decoration:underline}

    .news-detail-container {
        background: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        margin-top: 20px;
    }

    .detail-title {
        font-size: 30px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 15px;
    }

    .detail-meta {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }

    .detail-image {
        width: 100%;
        max-height: 450px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .detail-content {
        font-size: 16px;
        line-height: 1.8;
        color: #334155;
    }
    .detail-content p {
        margin-bottom: 1em;
    }

    .back-link {
        display: inline-block;
        margin-top: 30px;
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
    }
    .back-link:hover {
        text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span></div>
  <ul>
    <li>
        <a href="index.php" class="<?= $cur === 'index.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i><span>Home</span>
        </a>
    </li>
    <li>
        <a href="giangvien.php" class="<?= $cur === 'giangvien.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chalkboard-user"></i><span>Lecturers</span>
        </a>
    </li>
    <li>
        <a href="khoa.php" class="<?= $cur === 'khoa.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-building-columns"></i><span>Khoa</span>
        </a>
    </li>
    <li>
        <a href="monhoc.php" class="<?= $cur === 'monhoc.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book"></i><span>Courses</span>
        </a>
    </li>
    <li>
        <a href="nganh.php" class="<?= $cur === 'nganh.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i><span>Majors</span>
        </a>
    </li>
    <li>
        <a href="lophoc.php" class="<?= $cur === 'lophoc.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chalkboard"></i><span>Management Khoa hoc</span>
        </a>
    </li>
    <li>
        <a href="khoikienthuc.php" class="<?= $cur === 'khoikienthuc.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-book"></i><span>Knowledge Blocks</span>
        </a>
    </li>
    <li>
        <a href="ctdt_manager.php" class="<?= $cur === 'ctdt_manager.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-project-diagram"></i><span>Curriculum Management</span>
        </a>
    </li>
    <li>
        <a href="news.php" class="<?= $cur === 'news.php' ? 'active' : '' ?>">
            <i class="fa-regular fa-newspaper"></i><span>News</span>
        </a>
    </li>
    <li>
        <a href="thanhtoan.php" class="<?= $cur === 'thanhtoan.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-credit-card"></i><span>Payments</span>
        </a>
    </li>
  </ul>

  <div class="userbox">
    <?php if ($uname): ?>
      <div><i class="fa-solid fa-user-shield"></i> <?= h($urole ?? 'user') ?>: <b><?= h($uname) ?></b></div>
      <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Logout</a></div>
    <?php else: ?>
      <div>N/A</div>
      <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/login.php">Login</a></div>
    <?php endif; ?>
  </div>
</div>

<div class="main-content">
  <?php if ($news_item): ?>
    <div class="news-detail-container">
        <h1 class="detail-title"><?= h($news_item['title']) ?></h1>
        <div class="detail-meta">
            <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($news_item['date'])) ?>
            <?php if (!empty($news_item['category'])): ?>
                <span style="margin-left:15px;color:#2563eb;"><i class="fa-solid fa-tag"></i> <?= h($news_item['category']) ?></span>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($news_item['image'])): ?>
            
            <img src="<?= h($news_item['image']) ?>" alt="<?= h($news_item['title']) ?>" class="detail-image">
        <?php endif; ?>
        
        <div class="detail-content">
            <?= nl2br(h($news_item['content'])) ?>
        </div>

        <a href="news.php" class="back-link"><i class="fa-solid fa-arrow-left"></i>N/A</a>
    </div>
  <?php else: ?>
    <div class="news-detail-container">
        <h1 class="detail-title">N/A</h1>
        <p>N/A</p>
        <a href="news.php" class="back-link"><i class="fa-solid fa-arrow-left"></i>N/A</a>
    </div>
  <?php endif; ?>
</div>

</body>
</html>