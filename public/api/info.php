<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$url   = trim($input['url'] ?? '');

if ($url === '') { echo json_encode(['msg' => 'URL kosong!']); exit; }
if (!preg_match('#^https?://#i', $url)) { echo json_encode(['msg' => 'URL tidak valid!']); exit; }

function detectPlatform($url) {
  if (preg_match('/youtu\.?be|youtube\.com/i', $url)) return 'YouTube';
  if (preg_match('/tiktok\.com/i', $url)) return 'TikTok';
  if (preg_match('/instagram\.com/i', $url)) return 'Instagram';
  return 'Other';
}

$cmd    = 'yt-dlp -j --no-warnings --no-playlist ' . escapeshellarg($url) . ' 2>/dev/null';
$output = shell_exec($cmd);

if (!$output) {
  http_response_code(500);
  echo json_encode(['msg' => 'Gagal ambil info. Cek URL / koneksi.']);
  exit;
}

$info = json_decode(trim(explode("\n", $output)[0]), true);
if (!$info) {
  http_response_code(500);
  echo json_encode(['msg' => 'Gagal parse info video.']);
  exit;
}

echo json_encode([
  'title'     => $info['title']    ?? 'Tanpa Judul',
  'thumbnail' => $info['thumbnail'] ?? '',
  'duration'  => $info['duration']  ?? 0,
  'uploader'  => $info['uploader']  ?? $info['channel'] ?? '-',
  'platform'  => detectPlatform($url),
]);
