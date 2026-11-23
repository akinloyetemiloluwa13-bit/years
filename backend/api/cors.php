<?php
// Ensure this script only executes once per request
if (defined('APP_CORS_LOADED')) {
    return;
}
define('APP_CORS_LOADED', true);

// Configure cross-site PHP session cookies BEFORE session_start()
if (!headers_sent()) {
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    $isSecureRequest = (
        !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off'
    ) || $forwardedProto === 'https';
    $sameSite = $isSecureRequest ? 'None' : 'Lax';

    ini_set('session.cookie_samesite', $sameSite);
    ini_set('session.cookie_secure', $isSecureRequest ? '1' : '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_path', '/');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecureRequest,
        'httponly' => true,
        'samesite' => $sameSite
    ]);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
$envAllowed = trim((string)getenv('FRONTEND_ORIGIN'));

$isAllowed = false;

if (!empty($origin)) {
    // 1. Check against FRONTEND_ORIGIN env variable (supports comma-separated list or single origin)
    if (!empty($envAllowed)) {
        if ($envAllowed === '*') {
            $isAllowed = true;
        } else {
            $allowedList = array_map('trim', explode(',', $envAllowed));
            foreach ($allowedList as $allowed) {
                if (rtrim($allowed, '/') === rtrim($origin, '/')) {
                    $isAllowed = true;
                    break;
                }
            }
        }
    }

    // 2. Allow Vercel domains, this GitHub Pages origin, and local development.
    if (!$isAllowed) {
        $parsed = parse_url($origin);
        $host = $parsed['host'] ?? '';
        if (
            str_ends_with($host, '.vercel.app') || 
            $host === 'vercel.app' ||
            $origin === 'https://akinloyetemiloluwa13-bit.github.io' ||
            $host === 'localhost' ||
            $host === '127.0.0.1' ||
            str_starts_with($host, 'localhost:') ||
            str_starts_with($host, '127.0.0.1:')
        ) {
            $isAllowed = true;
        }
    }
}

// Send CORS headers if origin is allowed
if ($isAllowed && !empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
    header('Access-Control-Max-Age: 86400');
    header('Vary: Origin');
}

// Handle preflight OPTIONS requests immediately
if ($requestMethod === 'OPTIONS') {
    if (!headers_sent()) {
        header('Content-Length: 0');
        header('Content-Type: text/plain');
    }
    http_response_code(204);
    exit;
}
