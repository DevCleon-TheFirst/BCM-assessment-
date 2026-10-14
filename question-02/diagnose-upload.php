<?php
declare(strict_types=1);

function format_bytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function parse_ini_bytes(string $val): int {
    $val = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $num = (int) $val;
    switch ($last) {
        case 'g': $num *= 1024 * 1024 * 1024; break;
        case 'm': $num *= 1024 * 1024; break;
        case 'k': $num *= 1024; break;
    }
    return $num;
}

echo "=== Media Upload Diagnostics ===\n\n";

// User capabilities
$users = [
    'editor_colleague' => ['roles' => ['editor'], 'caps' => ['upload_files' => true]],
    'editor_failing'   => ['roles' => ['contributor'], 'caps' => ['upload_files' => false]],
];

echo "[Check 1: Capabilities]\n";
foreach ($users as $username => $profile) {
    $can_upload = $profile['caps']['upload_files'] ?? false;
    $role = implode(', ', $profile['roles']);
    echo " - {$username} ({$role}): " . ($can_upload ? "OK\n" : "FAIL (missing upload_files)\n");
}
echo "\n";

// Server upload limits
echo "[Check 2: PHP Limits]\n";
$upload_max = ini_get('upload_max_filesize') ?: '2M';
$post_max = ini_get('post_max_size') ?: '8M';
$memory = ini_get('memory_limit') ?: '128M';

echo " - upload_max_filesize: {$upload_max}\n";
echo " - post_max_size:       {$post_max}\n";
echo " - memory_limit:        {$memory}\n";

$test_file_size = 15 * 1024 * 1024;
echo " - Candidate 15MB file: ";
if ($test_file_size > parse_ini_bytes($upload_max)) {
    echo "EXCEEDS LIMIT ({$upload_max}) -> HTTP 413 / upload error\n";
} else {
    echo "OK\n";
}
echo "\n";

// MIME types
echo "[Check 3: MIME Types]\n";
$allowed_mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$candidates = [
    'hero-image.jpg' => 'image/jpeg',
    'graphic.svg'    => 'image/svg+xml',
    'photo.heic'     => 'image/heic',
];

foreach ($candidates as $filename => $mime) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $supported = isset($allowed_mimes[$ext]);
    echo " - {$filename}: " . ($supported ? "ALLOWED\n" : "REJECTED (disallowed MIME)\n");
}
echo "\n";

// Directory permissions
echo "[Check 4: Directory Permissions]\n";
$uploads_dir = sys_get_temp_dir() . '/wp-content-uploads';
@mkdir($uploads_dir, 0755, true);
$is_writable = is_writable($uploads_dir);
echo " - {$uploads_dir}: " . ($is_writable ? "Writable (OK)\n" : "Read-only (FAIL)\n");
@rmdir($uploads_dir);

echo "\nDone.\n";
