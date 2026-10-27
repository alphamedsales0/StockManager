<?php
header('Content-Type: application/json; charset=utf-8');

$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';

$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax')
]);

session_name('STOCKMANAGER');
session_start();

$sessionFile = session_save_path() . '/sess_' . session_id();

echo json_encode([
    'session_id'         => session_id(),
    'session_name'       => session_name(),
    'session_data'       => $_SESSION,
    'session_keys'       => array_keys($_SESSION),
    'cookie'             => $_COOKIE,
    'cookie_params'      => session_get_cookie_params(),
    'save_path'          => session_save_path(),
    'save_path_writable' => is_writable(session_save_path()),
    'save_path_exists'   => is_dir(session_save_path()),
    'session_file'       => $sessionFile,
    'file_exists'        => file_exists($sessionFile),
    'file_size'          => file_exists($sessionFile) ? filesize($sessionFile) : 0,
    'file_content'       => file_exists($sessionFile) ? @file_get_contents($sessionFile) : null,
    'file_perms'         => file_exists($sessionFile) ? substr(sprintf('%o', fileperms($sessionFile)), -4) : null,
    'host'               => $host,
    'https'              => $isSecure,
    'php_version'        => PHP_VERSION,
    'session_handler'    => ini_get('session.save_handler'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);