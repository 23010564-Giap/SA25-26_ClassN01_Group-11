<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../backend/db.php';
function h($s){ return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }

$uname = $_SESSION['uname'] ?? null;
$urole = $_SESSION['urole'] ?? null;
$base  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';

$cur = basename($_SERVER['PHP_SELF']);

$defaultImg = "https://phenikaa-uni.edu.vn/img/share_facebook_2.jpg"; 
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
  <style>
    .userbox{margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px}
    .userbox a{color:#e5e7eb;text-decoration:underline}

    
    .main-content h2 { color: #334155; font-size: 24px; margin-bottom: 20px; border-left: 5px solid #2563eb; padding-left: 15px; }

    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
    }

    .news-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        border: 1px solid #e2e8f0;
        height: 100%;
    }

    .news-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .img-wrapper {
        width: 100%;
        height: 200px;
        overflow: hidden;
        position: relative;
    }

    .news-img-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .news-card:hover .news-img-thumb {
        transform: scale(1.05);
    }

    .news-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .news-date {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .news-title {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 12px;
        line-height: 1.5;
        
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .news-excerpt {
        font-size: 14px;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 20px;
        
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .read-more {
        margin-top: auto;
        font-size: 14px;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .read-more:hover { color: #1e40af; text-decoration: underline; }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="logo"><i class="fa-solid fa-graduation-cap"></i><span>PHENIKAA UNIVERSITY</span></div>
  <ul>
    <li><a href="index.php" class="<?= $cur==='index.php'?'active':'' ?>"><i class="fa-solid fa-house"></i><span>Home</span></a></li>
    <li><a href="giangvien.php" class="<?= $cur==='giangvien.php'?'active':'' ?>"><i class="fa-solid fa-chalkboard-user"></i><span>Lecturers</span></a></li>
    <li><a href="khoa.php" class="<?= $cur==='khoa.php'?'active':'' ?>"><i class="fa-solid fa-building-columns"></i><span>Khoa</span></a></li>
    <li><a href="monhoc.php" class="<?= $cur==='monhoc.php'?'active':'' ?>"><i class="fa-solid fa-book"></i><span>Courses</span></a></li>
    <li><a href="nganh.php" class="<?= $cur==='nganh.php'?'active':'' ?>"><i class="fa-solid fa-layer-group"></i><span>Majors</span></a></li>
    <li><a href="lophoc.php" class="<?= $cur==='lophoc.php'?'active':'' ?>"><i class="fa-solid fa-chalkboard"></i><span>Management Khoa hoc</span></a></li>
    <li><a href="khoikienthuc.php" class="<?= $cur==='khoikienthuc.php'?'active':'' ?>"><i class="fa-solid fa-book"></i><span>Knowledge Blocks</span></a></li>
    <li><a href="ctdt_manager.php" class="<?= $cur==='ctdt_manager.php'?'active':'' ?>"><i class="fa-solid fa-project-diagram"></i><span>Curriculum Management</span></a></li>
    <li><a href="news.php" class="<?= $cur==='news.php'?'active':'' ?>"><i class="fa-regular fa-newspaper"></i><span>News</span></a></li>
    <li><a href="thanhtoan.php" class="<?= $cur==='thanhtoan.php'?'active':'' ?>"><i class="fa-solid fa-credit-card"></i><span>Payments</span></a></li>
  </ul>
  <div class="userbox">
    <?php if ($uname): ?>
      <div><i class="fa-solid fa-user-shield"></i> <?= h($urole ?? 'user') ?>: <b><?= h($uname) ?></b></div>
      <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Logout</a></div>
    <?php endif; ?>
  </div>
</div>

<div class="main-content">
  <h2>N/A</h2>
  
  <div class="news-grid">
    <?php
      $rs = $conn->query("SELECT * FROM news ORDER BY date DESC");
      
      if ($rs && $rs->num_rows){
        while($n = $rs->fetch_assoc()){
            $imgSrc = !empty($n['image']) ? $n['image'] : $defaultImg;
    ?>
        <div class="news-card">
            <div class="img-wrapper">
                <img src="<?= h($imgSrc) ?>" 
                     alt="<?= h($n['title']) ?>" 
                     class="news-img-thumb"
                     onerror="this.onerror=null;this.src='<?= $defaultImg ?>';">
            </div>
            
            <div class="news-body">
                <div class="news-date">
                    <i class="fa-regular fa-calendar-days"></i> <?= date('d/m/Y', strtotime($n['date'])) ?>
                </div>
                <div class="news-title"><?= h($n['title']) ?></div>
                <div class="news-excerpt"><?= h(strip_tags($n['content'])) ?></div>
                
                <a href="news_detail.php?id=<?= $n['id'] ?>N/A<i class="fa-solid fa-arrow-right-long"></i>
                </a>
            </div>
        </div>
    <?php
        }
      } else {
        echo 'Sample text';
      }
    ?>
  </div>
</div>

</body>
</html>