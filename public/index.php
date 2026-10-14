<?php
session_save_path(__DIR__ . '/../data/sessions');
if (!is_dir(session_save_path())) @mkdir(session_save_path(), 0755, true);
session_start();

$DATA  = __DIR__ . '/../data';
$USERS = $DATA . '/users.json';
if (!is_dir($DATA)) mkdir($DATA, 0755, true);
if (!file_exists($USERS)) file_put_contents($USERS, '[]');

function loadUsers() { global $USERS; return json_decode(file_get_contents($USERS), true) ?: []; }
function saveUsers($u) { global $USERS; file_put_contents($USERS, json_encode($u, JSON_PRETTY_PRINT)); }

$msg = '';

// Logout
if (isset($_GET['logout'])) {
  session_destroy();
  header('Location: index.php'); exit;
}

// Handle login / register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $action   = $_POST['action'];

  if ($username === '' || $password === '') {
    $msg = 'Isi semua field!';
  } elseif ($action === 'register') {
    $users = loadUsers();
    $exists = false;
    foreach ($users as $u) if ($u['username'] === $username) { $exists = true; break; }
    if ($exists) {
      $msg = 'Username udah dipake!';
    } else {
      $users[] = [
        'username'  => $username,
        'password'  => password_hash($password, PASSWORD_DEFAULT),
        'createdAt' => time()
      ];
      saveUsers($users);
      $_SESSION['username'] = $username;
      header('Location: index.php'); exit;
    }
  } elseif ($action === 'login') {
    $users = loadUsers();
    $found = null;
    foreach ($users as $u) if ($u['username'] === $username) { $found = $u; break; }
    if (!$found) {
      $msg = 'User tidak ditemukan!';
    } elseif (!password_verify($password, $found['password'])) {
      $msg = 'Password salah!';
    } else {
      $_SESSION['username'] = $username;
      header('Location: index.php'); exit;
    }
  }
}

$loggedIn = isset($_SESSION['username']);
$user     = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BlueTools — <?= $loggedIn ? 'Dashboard' : 'Login' ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php if (!$loggedIn): ?>
<!-- ===== AUTH SCREEN ===== -->
<div class="auth-wrap">
  <div class="card auth-card">
    <div class="logo">💙 BlueTools</div>
    <h1 id="title">Login</h1>
    <p class="sub" id="sub">Masuk ke akun lo</p>

    <form method="POST" action="index.php" id="authForm">
      <input type="hidden" name="action" id="actionField" value="login">

      <label>Username</label>
      <input name="username" id="username" placeholder="username" required autocomplete="username">
      <label>Password</label>
      <input name="password" id="password" type="password" placeholder="••••••••" required autocomplete="current-password">

      <button class="btn" type="submit" id="submitBtn">Login</button>
    </form>

    <p class="toggle">
      <span id="toggleText">Belum punya akun?</span>
      <a id="toggleBtn">Register</a>
    </p>
  </div>
</div>

<?php else: ?>
<!-- ===== DASHBOARD ===== -->
<div class="dash-wrap">
  <header class="topbar">
    <div class="brand">💙 BlueTools</div>
    <div class="user-box">
      <span><?= htmlspecialchars($user) ?></span>
      <a href="?logout=1" class="btn-mini">Logout</a>
    </div>
  </header>

  <main class="container">
    <div class="welcome">
      <h2>Halo, <?= htmlspecialchars($user) ?> 👋</h2>
      <p>Download video dari YouTube, TikTok & Instagram dengan mudah.</p>
    </div>

    <div class="platforms">
      <button class="plat-btn" data-plat="youtube">
        <span class="plat-icon yt">▶</span> YouTube
      </button>
      <button class="plat-btn" data-plat="tiktok">
        <span class="plat-icon tt">♪</span> TikTok
      </button>
      <button class="plat-btn" data-plat="instagram">
        <span class="plat-icon ig">◎</span> Instagram
      </button>
    </div>

    <div class="card tool-card">
      <h3>🔗 Tempel Link Video</h3>
      <div class="row">
        <input id="urlInput" placeholder="https://youtube.com/watch?v=..." />
        <button class="btn" id="analyzeBtn">Analisa</button>
      </div>
      <p class="hint">Support: YouTube • TikTok • Instagram (Reels, Post, Story)</p>

      <div id="infoBox" class="info hidden">
        <img id="thumb" class="thumb" alt="thumbnail">
        <div class="info-meta">
          <h4 id="vTitle"></h4>
          <p class="meta"><span id="vPlatform" class="badge"></span> <span id="vUploader"></span></p>
          <p class="meta" id="vDuration"></p>
          <div class="actions">
            <button class="btn" id="dlMp4">⬇ Download MP4</button>
            <button class="btn outline" id="dlMp3">🎵 Download MP3</button>
          </div>
        </div>
      </div>
    </div>

    <p class="footer">Made with 💙 · PHP + yt-dlp</p>
  </main>
</div>
<?php endif; ?>

<div id="toast"></div>
<div id="loader" class="loader hidden"><div class="spinner"></div><p id="loaderText">Memproses...</p></div>

<?php if ($msg): ?>
<script>window.__MSG__ = <?= json_encode($msg) ?>;</script>
<?php endif; ?>
<script src="app.js"></script>
</body>
</html>
