<?php
$input  = json_decode(file_get_contents('php://input'), true);
$url    = trim($input['url'] ?? '');
$format = $input['format'] ?? 'mp4';

if ($url === '') {
  http_response_code(400);
  echo json_encode(['msg' => 'URL kosong!']); exit;
}

$tmpDir = __DIR__ . '/../../tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);

$id     = time() . rand(1000, 9999);
$output = $tmpDir . '/' . $id . '.%(ext)s';

$escUrl = escapeshellarg($url);
$escOut = escapeshellarg($output);

if ($format === 'mp3') {
  $cmd = "yt-dlp -x --audio-format mp3 --no-playlist -o $escOut $escUrl 2>&1";
} else {
  $cmd = "yt-dlp -f \"best[ext=mp4]/best\" --no-playlist -o $escOut $escUrl 2>&1";
}

exec($cmd, $out, $code);

if ($code !== 0) {
  http_response_code(500);
  echo json_encode(['msg' => 'Gagal download. Coba lagi / ganti URL.']);
  exit;
}

$files = glob($tmpDir . '/' . $id . '.*');
if (!$files) {
  http_response_code(500);
  echo json_encode(['msg' => 'File tidak ditemukan.']);
  exit;
}

$file = $files[0];
$ext  = pathinfo($file, PATHINFO_EXTENSION);

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="download_' . $id . '.' . $ext . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: no-cache');

readfile($file);
@unlink($file);
