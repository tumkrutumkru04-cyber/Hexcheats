<?php
// Shared configuration and security helpers for HEX PROTOCOL.

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// JSON data store. Keep this directory outside the public web root when possible.
define('DATA_DIR', __DIR__ . '/data');
define('LICENSES_FILE', DATA_DIR . '/licenses.json');
define('USERS_FILE', DATA_DIR . '/users.json');
define('SETTINGS_FILE', DATA_DIR . '/settings.json');
define('APPLICATIONS_FILE', DATA_DIR . '/applications.json');
define('APKS_FILE', DATA_DIR . '/apks.json');
define('AUDIT_FILE', DATA_DIR . '/audit.json');
define('RATE_LIMIT_FILE', DATA_DIR . '/rate_limits.json');
define('VPLINK_BLACKLIST_FILE', DATA_DIR . '/vplink_blacklist.json');
define('MAX_JSON_BYTES', 5 * 1024 * 1024);
// Keep this token server-side. Prefer setting VPLINK_API_TOKEN in the hosting environment;
// the fallback keeps the cPanel deployment working when environment variables are unavailable.
define('VPLINK_API_TOKEN', getenv('VPLINK_API_TOKEN') ?: 'd7810ac0d2e7108510fd6fa8c124a2fc927ceaea');
define('PUBLIC_BASE_URL', rtrim(getenv('HEX_PUBLIC_BASE_URL') ?: 'https://hexcheats-production.up.railway.app', '/'));

if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0750, true);
}

function getJsonData($file) {
    if (!is_file($file)) return [];
    $size = @filesize($file);
    if ($size !== false && $size > MAX_JSON_BYTES) return [];
    $content = @file_get_contents($file);
    if ($content === false || trim($content) === '') return [];
    $decoded = json_decode($content, true);
    return is_array($decoded) ? $decoded : [];
}

function saveJsonData($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) return false;
    $tmp = $file . '.tmp.' . bin2hex(random_bytes(4));
    $written = @file_put_contents($tmp, $encoded, LOCK_EX);
    if ($written === false) return false;
    @chmod($tmp, 0640);
    return @rename($tmp, $file);
}

function getUserIP() {
    // Do not trust spoofable X-Forwarded-For headers unless your host explicitly
    // provides a trusted proxy integration.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function getDefaultSettings() {
    return [
        'site_name' => 'HEX PROTOCOL',
        'admin_username' => 'admin',
        'admin_password' => password_hash('admin123', PASSWORD_DEFAULT),
        'must_change_password' => true,
        'cooldown_minutes' => 5,
        'referral_code' => 'HEX2024',
        'maintenance_mode' => false,
        'api_maintenance' => false,
        'public_generation_enabled' => true,
        'security' => [
            'rate_limit_enabled' => true,
            'rate_limit_window' => 60,
            'rate_limit_requests' => 30,
            'login_max_attempts' => 5,
            'login_lock_minutes' => 15,
            'session_timeout_minutes' => 120,
            'admin_ip_allowlist' => [],
            'blocked_ips' => [],
            'audit_retention_days' => 30
        ],
        'vplink' => [
            'enabled' => true,
            'api_token' => '',
            'completion_window_seconds' => 60,
            'handoff_ttl_seconds' => 1800,
            'block_without_shortener' => true
        ]
    ];
}

function mergeDefaults($current, $defaults) {
    if (!is_array($current)) $current = [];
    foreach ($defaults as $key => $value) {
        if (is_array($value)) {
            $current[$key] = mergeDefaults($current[$key] ?? [], $value);
        } elseif (!array_key_exists($key, $current)) {
            $current[$key] = $value;
        }
    }
    return $current;
}

if (!is_file(SETTINGS_FILE)) {
    saveJsonData(SETTINGS_FILE, getDefaultSettings());
} else {
    $existingSettings = getJsonData(SETTINGS_FILE);
    $settingsWithDefaults = mergeDefaults($existingSettings, getDefaultSettings());
    // Existing installations with the original admin123 bootstrap password must
    // change it before relying on the control panel.
    if (!empty($settingsWithDefaults['admin_password']) && password_verify('admin123', $settingsWithDefaults['admin_password'])) {
        $settingsWithDefaults['must_change_password'] = true;
    }
    if ($settingsWithDefaults !== $existingSettings) saveJsonData(SETTINGS_FILE, $settingsWithDefaults);
}

if (!is_file(APPLICATIONS_FILE)) {
    saveJsonData(APPLICATIONS_FILE, [
        'applications' => [
            [
                'id' => 1,
                'name' => 'HEX CHEATS XOS',
                'game' => 'HEX CHEATS XOS',
                'api_url' => 'https://hex-protocol-git-production.up.railway.app/api/generate/5hour?count=1',
                'api_type' => 'default',
                'status' => 'active',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'MOCO LOCATION',
                'game' => 'FF & MAX',
                'api_url' => 'https://github-production-4fc0.up.railway.app/key_generatehex.php?action=generate',
                'api_type' => 'moco',
                'status' => 'active',
                'created' => date('Y-m-d H:i:s')
            ]
        ]
    ]);
}

foreach ([
    LICENSES_FILE => ['licenses' => []],
    USERS_FILE => ['users' => []],
    APKS_FILE => ['apks' => []],
    AUDIT_FILE => ['events' => []],
    RATE_LIMIT_FILE => ['buckets' => []],
    VPLINK_BLACKLIST_FILE => ['entries' => []]
] as $file => $default) {
    if (!is_file($file)) saveJsonData($file, $default);
}

function settings() {
    return mergeDefaults(getJsonData(SETTINGS_FILE), getDefaultSettings());
}

function securitySettings() {
    $settings = settings();
    return $settings['security'];
}

function vplinkSettings() {
    $settings = settings();
    $vplink = $settings['vplink'] ?? [];
    return [
        'enabled' => !empty($vplink['enabled']),
        'api_token' => cleanText($vplink['api_token'] ?? '', 160),
        'completion_window_seconds' => min(60, max(60, (int)($vplink['completion_window_seconds'] ?? ($vplink['minimum_wait_seconds'] ?? 60)))),
        'handoff_ttl_seconds' => min(86400, max(300, (int)($vplink['handoff_ttl_seconds'] ?? 1800))),
        'block_without_shortener' => array_key_exists('block_without_shortener', $vplink) ? !empty($vplink['block_without_shortener']) : true
    ];
}

function getVplinkApiToken() {
    $configured = vplinkSettings()['api_token'];
    return $configured !== '' ? $configured : VPLINK_API_TOKEN;
}

function isAdminLoggedIn() {
    $settings = settings();
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) return false;
    if (!empty($settings['security']['admin_ip_allowlist'])) {
        $allowed = array_filter(array_map('trim', $settings['security']['admin_ip_allowlist']));
        if (!empty($allowed) && !in_array(getUserIP(), $allowed, true)) return false;
    }
    $timeout = max(5, (int)($settings['security']['session_timeout_minutes'] ?? 120)) * 60;
    if (!empty($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > $timeout) {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_last_activity']);
        return false;
    }
    $_SESSION['admin_last_activity'] = time();
    return true;
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireAdminApi() {
    if (!isAdminLoggedIn()) return ['success' => false, 'error' => 'Unauthorized'];
    if (!verifyCsrf()) return ['success' => false, 'error' => 'Security token expired. Refresh the admin panel and try again.'];
    return null;
}

function getRateLimitKey($bucket, $ip = null) {
    return $bucket . '|' . ($ip ?: getUserIP());
}

function rateLimitCheck($bucket, $limit, $window, $ip = null) {
    $settings = securitySettings();
    if (empty($settings['rate_limit_enabled']) && $bucket !== 'login') return ['allowed' => true, 'remaining' => $limit];
    $limit = max(1, (int)$limit);
    $window = max(1, (int)$window);
    $now = time();
    $data = getJsonData(RATE_LIMIT_FILE);
    $data['buckets'] = is_array($data['buckets'] ?? null) ? $data['buckets'] : [];
    $key = getRateLimitKey($bucket, $ip);
    $hits = array_values(array_filter($data['buckets'][$key] ?? [], function($stamp) use ($now, $window) {
        return ((int)$stamp + $window) > $now;
    }));
    $allowed = count($hits) < $limit;
    if ($allowed) $hits[] = $now;
    $data['buckets'][$key] = $hits;
    // Keep this lightweight store bounded.
    foreach ($data['buckets'] as $bucketKey => $bucketHits) {
        $fresh = array_values(array_filter($bucketHits, function($stamp) use ($now) { return ((int)$stamp + 3600) > $now; }));
        if (empty($fresh)) unset($data['buckets'][$bucketKey]); else $data['buckets'][$bucketKey] = $fresh;
    }
    saveJsonData(RATE_LIMIT_FILE, $data);
    $retryAfter = empty($hits) ? 0 : max(1, ((int)$hits[0] + $window) - $now);
    return ['allowed' => $allowed, 'remaining' => max(0, $limit - count($hits)), 'retry_after' => $retryAfter];
}

function clearRateLimit($bucket, $ip = null) {
    $data = getJsonData(RATE_LIMIT_FILE);
    $key = getRateLimitKey($bucket, $ip);
    if (isset($data['buckets'][$key])) unset($data['buckets'][$key]);
    saveJsonData(RATE_LIMIT_FILE, $data);
}

function isIpBlocked($ip = null) {
    $ip = $ip ?: getUserIP();
    return in_array($ip, securitySettings()['blocked_ips'] ?? [], true);
}

function requestGuard($scope = 'public') {
    $settings = settings();
    $security = $settings['security'];
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 65536) return ['success' => false, 'error' => 'Request too large'];
    if (isIpBlocked()) return ['success' => false, 'error' => 'Access temporarily restricted'];
    if ($scope === 'public') {
        if (empty($settings['public_generation_enabled']) || !empty($settings['maintenance_mode'])) {
            return ['success' => false, 'error' => 'Key generation is temporarily unavailable'];
        }
        $check = rateLimitCheck('public', $security['rate_limit_requests'], $security['rate_limit_window']);
    } elseif ($scope === 'login') {
        $check = rateLimitCheck('login', $security['login_max_attempts'], $security['login_lock_minutes'] * 60);
    } else {
        $check = rateLimitCheck('admin', 120, 60);
    }
    if (!$check['allowed']) {
        return ['success' => false, 'error' => 'Too many requests. Please try again shortly.', 'retry_after' => $check['retry_after'] ?? 60];
    }
    return null;
}

function recordAudit($event, $details = []) {
    $data = getJsonData(AUDIT_FILE);
    $events = is_array($data['events'] ?? null) ? $data['events'] : [];
    $events[] = [
        'event' => (string)$event,
        'details' => is_array($details) ? $details : [],
        'ip' => getUserIP(),
        'created' => date('Y-m-d H:i:s')
    ];
    $settings = settings();
    $retention = max(50, (int)($settings['security']['audit_retention_days'] ?? 30) * 50);
    saveJsonData(AUDIT_FILE, ['events' => array_slice($events, -$retention)]);
}

function cleanText($value, $max = 255) {
    $value = trim((string)$value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function isSafeRemoteUrl($url) {
    $url = trim((string)$url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    $parts = parse_url($url);
    if (!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) return false;
    if (!empty($parts['user']) || !empty($parts['pass'])) return false;
    $host = strtolower($parts['host'] ?? '');
    if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true)) return false;
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    }
    return true;
}

function generateKeyFromAPI($apiUrl, $apiType = 'default', $count = 1) {
    if (!isSafeRemoteUrl($apiUrl)) return ['success' => false, 'error' => 'Configured API URL is not allowed'];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: HEX-PROTOCOL-KeyService/2.0']
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $curlError || $httpCode < 200 || $httpCode >= 300) {
        return ['success' => false, 'error' => 'Key provider is temporarily unavailable'];
    }
    if (strlen($response) > 262144) return ['success' => false, 'error' => 'Key provider response is too large'];
    $data = json_decode($response, true);
    if ($apiType === 'moco') {
        if (!is_array($data) || ($data['ok'] ?? false) !== true) return ['success' => false, 'error' => 'Invalid API response'];
        return [
            'success' => true,
            'keys' => [cleanText($data['key'] ?? 'UNKNOWN', 120)],
            'key_type' => 'MOCO',
            'duration_value' => 5,
            'duration_type' => 'hours',
            'validity' => cleanText($data['validity'] ?? '5 Hours', 80),
            'expires_at' => cleanText($data['expires_at'] ?? '', 80),
            'max_devices' => max(1, (int)($data['max_devices'] ?? 1))
        ];
    }
    if (!is_array($data) || ($data['success'] ?? false) !== true) return ['success' => false, 'error' => 'Invalid API response'];
    return $data;
}

function checkCooldown($ip, $appId) {
    $users = getJsonData(USERS_FILE);
    $settings = settings();
    $cooldownMinutes = max(1, (int)($settings['cooldown_minutes'] ?? 5));
    $user = $users['users'][$ip] ?? [];
    $lastGeneration = $user['last_generation_by_app'][$appId] ?? ($user['last_generation'] ?? 0);
    $timeDiff = time() - (int)$lastGeneration;
    $cooldownSeconds = $cooldownMinutes * 60;
    if ($timeDiff < $cooldownSeconds) {
        return ['can_generate' => false, 'wait_time' => $cooldownSeconds - $timeDiff, 'cooldown_minutes' => $cooldownMinutes];
    }
    return ['can_generate' => true];
}

function createKeyHandoff($payload, $ttl = null) {
    if ($ttl === null) $ttl = vplinkSettings()['handoff_ttl_seconds'];
    $token = bin2hex(random_bytes(24));
    if (!isset($_SESSION['key_handoffs']) || !is_array($_SESSION['key_handoffs'])) $_SESSION['key_handoffs'] = [];
    $_SESSION['key_handoffs'][$token] = [
        'payload' => is_array($payload) ? $payload : [],
        'created' => time(),
        'expires' => time() + max(60, (int)$ttl),
        'shortener_started' => false,
        'shortener_started_at' => 0
    ];
    // Keep session state bounded if a visitor opens multiple tabs.
    foreach ($_SESSION['key_handoffs'] as $handoffToken => $handoff) {
        if ((int)($handoff['expires'] ?? 0) < time()) unset($_SESSION['key_handoffs'][$handoffToken]);
    }
    return $token;
}

function getKeyHandoff($token, $consume = false) {
    $token = preg_replace('/[^a-f0-9]/i', '', (string)$token);
    if ($token === '' || empty($_SESSION['key_handoffs'][$token])) return null;
    $handoff = $_SESSION['key_handoffs'][$token];
    if ((int)($handoff['expires'] ?? 0) < time()) {
        unset($_SESSION['key_handoffs'][$token]);
        return null;
    }
    if ($consume) unset($_SESSION['key_handoffs'][$token]);
    return $handoff;
}

function handoffSignature($token) {
    return hash_hmac('sha256', (string)$token, hash('sha256', getVplinkApiToken(), true));
}

function vplinkBlacklistKey($token) {
    return hash('sha256', preg_replace('/[^a-f0-9]/i', '', (string)$token));
}

function isVplinkBlacklisted($token) {
    $data = getJsonData(VPLINK_BLACKLIST_FILE);
    $entries = is_array($data['entries'] ?? null) ? $data['entries'] : [];
    $key = vplinkBlacklistKey($token);
    return isset($entries[$key]);
}

function blacklistVplinkHandoff($token, $reason = 'early_return') {
    $token = preg_replace('/[^a-f0-9]/i', '', (string)$token);
    if ($token === '') return;
    $data = getJsonData(VPLINK_BLACKLIST_FILE);
    $data['entries'] = is_array($data['entries'] ?? null) ? $data['entries'] : [];
    $key = vplinkBlacklistKey($token);
    $data['entries'][$key] = ['created' => time(), 'reason' => cleanText($reason, 40)];
    // Retain only recent hashes; the raw handoff token is never stored.
    foreach ($data['entries'] as $entryKey => $entry) {
        if ((int)($entry['created'] ?? 0) < time() - (30 * 86400)) unset($data['entries'][$entryKey]);
    }
    saveJsonData(VPLINK_BLACKLIST_FILE, $data);
    if (isset($_SESSION['key_handoffs'][$token])) unset($_SESSION['key_handoffs'][$token]);
    recordAudit('vplink_handoff_blocked', ['reason' => $reason]);
}

function getVplinkReturnState($token, $signature) {
    $token = preg_replace('/[^a-f0-9]/i', '', (string)$token);
    $signature = preg_replace('/[^a-f0-9]/i', '', (string)$signature);
    if ($token === '' || isVplinkBlacklisted($token)) return 'blocked';
    $handoff = getKeyHandoff($token);
    if (!$handoff) return 'expired';
    if ($signature === '' || !hash_equals(handoffSignature($token), $signature)) return 'invalid';
    if (empty($handoff['shortener_started']) || (int)($handoff['shortener_started_at'] ?? 0) <= 0) return 'bypass';
    $elapsed = time() - (int)$handoff['shortener_started_at'];
    if ($elapsed < 60) {
        blacklistVplinkHandoff($token, 'returned_before_60_seconds');
        return 'early';
    }
    return 'valid';
}

function validateVplinkReturn($token, $signature) {
    $state = getVplinkReturnState($token, $signature);
    if ($state !== 'valid') return null;
    $token = preg_replace('/[^a-f0-9]/i', '', (string)$token);
    $handoff = getKeyHandoff($token);
    if (!$handoff) return null;
    if (!isset($_SESSION['vplink_returns']) || !is_array($_SESSION['vplink_returns'])) $_SESSION['vplink_returns'] = [];
    $_SESSION['vplink_returns'][$token] = time();
    return $handoff;
}

function buildPublicUrl($path, $query = []) {
    $url = PUBLIC_BASE_URL . '/' . ltrim($path, '/');
    return $query ? $url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : $url;
}

function shortenWithVplink($destination, $alias = '') {
    $vplink = vplinkSettings();
    $apiToken = getVplinkApiToken();
    if (!$vplink['enabled']) return ['success' => false, 'error' => 'Shortener is disabled by the administrator'];
    if (!isSafeRemoteUrl($destination) || $apiToken === '') return ['success' => false, 'error' => 'Shortener is not configured'];
    $query = [
        'api' => $apiToken,
        'url' => $destination,
        'format' => 'json'
    ];
    if ($alias !== '') $query['alias'] = cleanText($alias, 40);
    $endpoint = 'https://vplink.in/api?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $response = false;
    $httpCode = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: HEX-PROTOCOL-LinkService/1.0']
        ]);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 15,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => "Accept: application/json\r\nUser-Agent: HEX-PROTOCOL-LinkService/1.0\r\n"
        ], 'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false
        ]]);
        $response = @file_get_contents($endpoint, false, $context);
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\\/\\S+\\s+(\\d+)/i', $header, $matches)) { $httpCode = (int)$matches[1]; break; }
        }
    }
    if ($response === false || $httpCode < 200 || $httpCode >= 300 || strlen((string)$response) > 65536) {
        return ['success' => false, 'error' => 'Shortener is temporarily unavailable'];
    }
    $data = json_decode($response, true);
    $shortenedUrl = is_array($data) ? ($data['shortenedUrl'] ?? '') : '';
    if (!is_string($shortenedUrl) || !isSafeRemoteUrl($shortenedUrl)) return ['success' => false, 'error' => 'Shortener returned an invalid link'];
    $host = strtolower(parse_url($shortenedUrl, PHP_URL_HOST) ?? '');
    if ($host !== 'vplink.in' && !str_ends_with($host, '.vplink.in')) return ['success' => false, 'error' => 'Shortener returned an unexpected link'];
    return ['success' => true, 'url' => $shortenedUrl];
}

function updateUserCooldown($ip, $appId) {
    $users = getJsonData(USERS_FILE);
    $user = $users['users'][$ip] ?? ['ip' => $ip, 'total_generated' => 0, 'last_generation_by_app' => []];
    $user['ip'] = $ip;
    $user['last_generation'] = time();
    $user['last_generation_by_app'] = $user['last_generation_by_app'] ?? [];
    $user['last_generation_by_app'][(string)$appId] = time();
    $user['app_id'] = $appId;
    $user['total_generated'] = (int)($user['total_generated'] ?? 0) + 1;
    $users['users'][$ip] = $user;
    saveJsonData(USERS_FILE, $users);
}
?>
