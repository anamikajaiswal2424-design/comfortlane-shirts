<?php
declare(strict_types=1);
// No Redis or database. This directory is TEMPORARY and instance-local.
function localStoreBootstrap(string $source): string
{
    $version = hash_file('sha256', $source . '/api/index.php') . hash_file('sha256', $source . '/setting.php');
    $root = sys_get_temp_dir() . '/comfortlane-simple-' . substr(hash('sha256', $source . $version), 0, 20);
    if (!is_dir($root) && !mkdir($root, 0700, true) && !is_dir($root)) throw new RuntimeException('Temporary directory unavailable');
    $lock = fopen($root . '/bootstrap.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Temporary storage busy');
    try {
        if (!is_file($root . '/ready')) {
            if (!is_dir($root . '/app')) mkdir($root . '/app', 0700, true);
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/app', FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($files as $file) {
                $target = $root . '/app/' . substr($file->getPathname(), strlen($source . '/app/'));
                if ($file->isDir()) { if (!is_dir($target)) mkdir($target, 0700, true); }
                elseif (!copy($file->getPathname(), $target)) throw new RuntimeException('Temporary copy failed');
            }
            if (!copy($source . '/setting.php', $root . '/setting.php')) throw new RuntimeException('Settings copy failed');
            mkdir($root . '/sessions', 0700, true);
            file_put_contents($root . '/ready', '1');
        }
    } finally { flock($lock, LOCK_UN); fclose($lock); }
    ini_set('session.save_handler', 'files');
    session_save_path($root . '/sessions');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', getenv('VERCEL') ? '1' : '0');
    return $root;
}
