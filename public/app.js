const API = location.origin;
let currentUrl = '';
let currentTitle = 'video';

const $ = id => document.getElementById(id);

function toast(msg, dur = 2500) {
  const t = $('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), dur);
}

function showLoader(text = 'Memproses...') {
  $('loaderText').textContent = text;
  $('loader').classList.remove('hidden');
}
function hideLoader() { $('loader').classList.add('hidden'); }

// Flash message dari PHP
if (window.__MSG__) {
  window.addEventListener('load', () => toast(window.__MSG__));
}

// Toggle login/register (hanya di halaman auth)
if ($('toggleBtn')) {
  let isLogin = true;
  $('toggleBtn').onclick = () => {
    isLogin = !isLogin;
    $('title').textContent        = isLogin ? 'Login' : 'Register';
    $('sub').textContent          = isLogin ? 'Masuk ke akun lo' : 'Bikin akun baru';
    $('submitBtn').textContent    = isLogin ? 'Login' : 'Register';
    $('toggleText').textContent   = isLogin ? 'Belum punya akun?' : 'Udah punya akun?';
    $('toggleBtn').textContent    = isLogin ? 'Register' : 'Login';
    $('actionField').value        = isLogin ? 'login' : 'register';
    $('password').autocomplete    = isLogin ? 'current-password' : 'new-password';
  };
}

// Platform shortcut (hanya di dashboard)
document.querySelectorAll('.plat-btn').forEach(btn => {
  btn.onclick = () => {
    const plat = btn.dataset.plat;
    const ph = {
      youtube:   'https://youtube.com/watch?v=...',
      tiktok:    'https://tiktok.com/@user/video/...',
      instagram: 'https://instagram.com/reel/...'
    }[plat];
    $('urlInput').placeholder = ph;
    $('urlInput').focus();
    $('urlInput').scrollIntoView({ behavior: 'smooth', block: 'center' });
  };
});

// Analyze
if ($('analyzeBtn')) $('analyzeBtn').onclick = async () => {
  const url = $('urlInput').value.trim();
  if (!url) return toast('Tempel link dulu!');
  if (!/^https?:\/\//i.test(url)) return toast('URL harus mulai http:// atau https://');

  showLoader('Mengambil info video...');
  try {
    const res  = await fetch(API + '/api/info.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url })
    });
    const data = await res.json();
    hideLoader();

    if (!res.ok) return toast(data.msg || 'Gagal ambil info');

    currentUrl   = url;
    currentTitle = (data.title || 'video').replace(/[^\w\s-]/g, '').slice(0, 60);

    $('vTitle').textContent     = data.title;
    $('thumb').src              = data.thumbnail || '';
    $('vPlatform').textContent  = data.platform;
    $('vUploader').textContent  = data.uploader;

    const mins = Math.floor((data.duration || 0) / 60);
    const secs = (data.duration || 0) % 60;
    $('vDuration').textContent = data.duration
      ? `⏱ ${mins}:${String(secs).padStart(2,'0')}`
      : '';

    $('infoBox').classList.remove('hidden');
    toast('Info berhasil diambil ✅');
  } catch {
    hideLoader();
    toast('Server error 😵');
  }
};

// Download
async function doDownload(format) {
  if (!currentUrl) return toast('Analisa link dulu!');
  const label = format === 'mp3' ? 'audio MP3' : 'video MP4';
  showLoader(`Mendownload ${label}... Sabar ya ⏳`);

  try {
    const res = await fetch(API + '/api/download.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url: currentUrl, format })
    });

    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      hideLoader();
      return toast(err.msg || 'Gagal download');
    }

    const blob = await res.blob();
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${currentTitle}.${format}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(a.href);

    hideLoader();
    toast('Download berhasil! 🎉');
  } catch {
    hideLoader();
    toast('Gagal download 😵');
  }
}

if ($('dlMp4')) $('dlMp4').onclick = () => doDownload('mp4');
if ($('dlMp3')) $('dlMp3').onclick = () => doDownload('mp3');
