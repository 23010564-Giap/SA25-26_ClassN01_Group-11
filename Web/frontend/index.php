<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../backend/db.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
$here = basename($_SERVER['PHP_SELF']);

$role  = $_SESSION['urole'] ?? null;
$uname = $_SESSION['uname'] ?? null;
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Home - Management Truong ai hoc Phenikaa</title>
  <base href="<?= h($base) ?>">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .userbox{margin-top:16px;border-top:1px solid #475569;padding-top:12px;color:#e5e7eb;font-size:14px}
    .userbox a{color:#e5e7eb;text-decoration:underline}
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

    <!-- Box ang nhap/ang xuat: chi hien thi neu he thong co module auth -->
    <div class="userbox">
      <?php if ($uname): ?>
        <div><i class="fa-solid fa-user-shield"></i> <?= h($role ?? 'user') ?>: <b><?= h($uname) ?></b></div>
        <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/logout.php">Logout</a></div>
      <?php else: ?>
        <div>N/A</div>
        <div style="margin-top:8px"><a href="/phenikaa_manager/auth_php_pack/login.php">Login</a></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="main-content">
    <header>
      <div class="search-bar">
        <input id="search" placeholder="Search thong bao...">
        <button onclick="searchNews()">Search</button>
      </div>
    </header>

    <div class="tab-container">
      <button class="tab" onclick="filterNews('all')">N/A</button>
      <button class="tab" onclick="filterNews('phongdambao')">Phong am bao chat luong & khao thi</button>
      <button class="tab" onclick="filterNews('phongdaotao')">Phong ao tao</button>
    </div>

    <div class="news-list" id="news-list">
      <?php
        $rs = $conn->query("SELECT id,title,content,date,category FROM news ORDER BY date DESC LIMIT 12");
        if($rs && $rs->num_rows){
          while($n=$rs->fetch_assoc()){
            echo '<div class="news-item '.h($n['category']).'">'.
                    '<div class="news-title">'.h($n['title']).'</div>'.
                    '<div class="news-date">'.h($n['date']).'</div>'.
                    '<div class="news-content">'.h($n['content']).'</div>'.
                 '</div>';
          }
        } else {
          echo 'Sample text';
        }
      ?>
    </div>
  </div>

  <footer><p>© 2025 Phenikaa University. All rights reserved.</p></footer>

  <script>
    function searchNews(){
      const q=(document.getElementById('search').value||'').trim().toLowerCase();
      document.querySelectorAll('.news-item').forEach(i=>{
        const t=(i.querySelector('.news-title')?.textContent||'').toLowerCase();
        const c=(i.querySelector('.news-content')?.textContent||'').toLowerCase();
        i.style.display=(t.includes(q)||c.includes(q))?'block':'none';
      });
    }
    function filterNews(type){
      document.querySelectorAll('.news-item').forEach(i=>{
        i.style.display=(type==='all'||i.classList.contains(type))?'block':'none';
      });
    }
  </script>
</body>
</html>