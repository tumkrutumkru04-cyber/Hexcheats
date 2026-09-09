<?php
// api.php - public key generation and authenticated admin actions.
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Invalid action'];

$readOnlyActions = ['get_applications'];
if (!in_array($action, $readOnlyActions, true) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $response = ['success' => false, 'error' => 'POST required'];
} elseif ($action === 'generate') {
    $response = handleGenerate();
} elseif ($action === 'get_applications') {
    $response = handleGetApplications();
} elseif (in_array($action, [
    'add_application', 'edit_application', 'delete_application',
    'add_apk', 'edit_apk', 'delete_apk', 'clear_user', 'clear_all',
    'update_security', 'block_ip', 'unblock_ip', 'clear_audit', 'clear_rate_limits'
], true)) {
    $guard = requireAdminApi();
    $response = $guard ?: match ($action) {
        'add_application' => handleAddApplication(),
        'edit_application' => handleEditApplication(),
        'delete_application' => handleDeleteApplication(),
        'add_apk' => handleAddAPK(),
        'edit_apk' => handleEditAPK(),
        'delete_apk' => handleDeleteAPK(),
        'clear_user' => handleClearUser(),
        'clear_all' => handleClearAll(),
        'update_security' => handleUpdateSecurity(),
        'block_ip' => handleBlockIp(),
        'unblock_ip' => handleUnblockIp(),
        'clear_audit' => handleClearAudit(),
        'clear_rate_limits' => handleClearRateLimits(),
        default => ['success' => false, 'error' => 'Invalid action']
    };
}

echo json_encode($response, JSON_UNESCAPED_SLASHES);
exit;

function handleGenerate() {
    $guard = requestGuard('public');
    if ($guard) return $guard;
    if (!empty(settings()['api_maintenance'])) return ['success' => false, 'error' => 'Key API is temporarily under maintenance. Please try again later.'];
    if (isIpBlocked()) return ['success' => false, 'error' => 'Access temporarily restricted'];

    $appId = filter_input(INPUT_POST, 'app_id', FILTER_VALIDATE_INT);
    if (!$appId || $appId < 1) return ['success' => false, 'error' => 'Please select an application'];

    $apps = getJsonData(APPLICATIONS_FILE);
    $app = null;
    foreach (($apps['applications'] ?? []) as $candidate) {
        if ((int)($candidate['id'] ?? 0) === $appId && ($candidate['status'] ?? '') === 'active') {
            $app = $candidate;
            break;
        }
    }
    if (!$app) return ['success' => false, 'error' => 'Application not found or inactive'];

    $ip = getUserIP();
    $cooldownCheck = checkCooldown($ip, $appId);
    if (!$cooldownCheck['can_generate']) {
        return ['success' => false, 'error' => 'Please wait ' . ceil($cooldownCheck['wait_time'] / 60) . ' minutes before generating another key'];
    }

    $apiResponse = generateKeyFromAPI($app['api_url'] ?? '', $app['api_type'] ?? 'default', 1);
    if (empty($apiResponse['success'])) return ['success' => false, 'error' => $apiResponse['error'] ?? 'API request failed'];
    $keys = is_array($apiResponse['keys'] ?? null) ? $apiResponse['keys'] : [];
    $key = cleanText($keys[0] ?? '', 160);
    if ($key === '') return ['success' => false, 'error' => 'Key provider returned an empty key'];

    $licenses = getJsonData(LICENSES_FILE);
    $licenses['licenses'] = is_array($licenses['licenses'] ?? null) ? $licenses['licenses'] : [];
    $licenses['licenses'][] = [
        'key' => $key,
        'ip' => $ip,
        'app_id' => $appId,
        'app_name' => cleanText($app['name'] ?? 'Application', 120),
        'game' => cleanText($app['game'] ?? '', 120),
        'created' => date('Y-m-d H:i:s'),
        'api_response' => $apiResponse
    ];
    saveJsonData(LICENSES_FILE, $licenses);
    updateUserCooldown($ip, $appId);
    recordAudit('license_generated', ['app_id' => $appId, 'app_name' => cleanText($app['name'] ?? '', 120)]);

    $durationValue = max(1, (int)($apiResponse['duration_value'] ?? 5));
    $maxDevices = max(1, (int)($apiResponse['max_devices'] ?? 1));
    $handoff = createKeyHandoff([
        'key' => $key,
        'key_type' => cleanText($apiResponse['key_type'] ?? 'FREE', 40),
        'duration_value' => $durationValue,
        'duration_type' => cleanText($apiResponse['duration_type'] ?? 'hours', 30),
        'app_name' => cleanText($app['name'] ?? '', 120),
        'game' => cleanText($app['game'] ?? '', 120),
        'validity' => cleanText($apiResponse['validity'] ?? '5 Hours to 10 Hours', 80),
        'validity_range' => '5 Hours to 10 Hours',
        'expires_at' => cleanText($apiResponse['expires_at'] ?? '', 80),
        'max_devices' => $maxDevices
    ]);
    return [
        'success' => true,
        'handoff' => $handoff,
        'duration_value' => $durationValue,
        'app_name' => cleanText($app['name'] ?? '', 120),
        'game' => cleanText($app['game'] ?? '', 120)
    ];
}

function handleGetApplications() {
    if (!isAdminLoggedIn()) return ['success' => false, 'error' => 'Unauthorized'];
    $apps = getJsonData(APPLICATIONS_FILE);
    return ['success' => true, 'applications' => array_values($apps['applications'] ?? [])];
}

function handleAddApplication() {
    $name = cleanText($_POST['name'] ?? '', 100);
    $game = cleanText($_POST['game'] ?? '', 100);
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiType = cleanText($_POST['api_type'] ?? 'default', 30);
    if ($name === '' || $game === '' || !isSafeRemoteUrl($apiUrl)) return ['success' => false, 'error' => 'Enter valid application details and a public HTTPS/HTTP API URL'];
    if (!in_array($apiType, ['default', 'moco'], true)) $apiType = 'default';

    $apps = getJsonData(APPLICATIONS_FILE);
    $apps['applications'] = is_array($apps['applications'] ?? null) ? $apps['applications'] : [];
    $newId = 1;
    foreach ($apps['applications'] as $app) $newId = max($newId, (int)($app['id'] ?? 0) + 1);
    $apps['applications'][] = ['id' => $newId, 'name' => $name, 'game' => $game, 'api_url' => $apiUrl, 'api_type' => $apiType, 'status' => 'active', 'created' => date('Y-m-d H:i:s')];
    saveJsonData(APPLICATIONS_FILE, $apps);
    recordAudit('application_added', ['id' => $newId, 'name' => $name]);
    return ['success' => true, 'message' => 'Application added successfully'];
}

function handleEditApplication() {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = cleanText($_POST['name'] ?? '', 100);
    $game = cleanText($_POST['game'] ?? '', 100);
    $apiUrl = trim($_POST['api_url'] ?? '');
    $apiType = cleanText($_POST['api_type'] ?? 'default', 30);
    $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
    if (!$id || $name === '' || $game === '' || !isSafeRemoteUrl($apiUrl)) return ['success' => false, 'error' => 'Enter valid application details and a public HTTPS/HTTP API URL'];
    if (!in_array($apiType, ['default', 'moco'], true)) $apiType = 'default';

    $apps = getJsonData(APPLICATIONS_FILE);
    $found = false;
    foreach ($apps['applications'] as &$app) {
        if ((int)($app['id'] ?? 0) === $id) {
            $app['name'] = $name; $app['game'] = $game; $app['api_url'] = $apiUrl; $app['api_type'] = $apiType; $app['status'] = $status;
            $found = true; break;
        }
    }
    unset($app);
    if (!$found) return ['success' => false, 'error' => 'Application not found'];
    saveJsonData(APPLICATIONS_FILE, $apps);
    recordAudit('application_updated', ['id' => $id, 'name' => $name, 'status' => $status]);
    return ['success' => true, 'message' => 'Application updated successfully'];
}

function handleDeleteApplication() {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) return ['success' => false, 'error' => 'Application ID required'];
    $apps = getJsonData(APPLICATIONS_FILE);
    $before = count($apps['applications'] ?? []);
    $apps['applications'] = array_values(array_filter($apps['applications'] ?? [], fn($app) => (int)($app['id'] ?? 0) !== $id));
    if (count($apps['applications']) === $before) return ['success' => false, 'error' => 'Application not found'];
    saveJsonData(APPLICATIONS_FILE, $apps);
    recordAudit('application_deleted', ['id' => $id]);
    return ['success' => true, 'message' => 'Application deleted successfully'];
}

function handleAddAPK() {
    $name = cleanText($_POST['name'] ?? '', 100);
    $version = cleanText($_POST['version'] ?? '', 40);
    $package = cleanText($_POST['package'] ?? '', 160);
    $description = cleanText($_POST['description'] ?? '', 500);
    $logo = trim($_POST['logo'] ?? '');
    $size = cleanText($_POST['size'] ?? '', 40);
    $fileUrl = trim($_POST['file_url'] ?? '');
    if ($name === '' || $version === '' || $package === '' || !isSafeRemoteUrl($fileUrl)) return ['success' => false, 'error' => 'Enter valid APK details and a public download URL'];
    if ($logo !== '' && !isSafeRemoteUrl($logo)) $logo = '';
    $apks = getJsonData(APKS_FILE);
    $apks['apks'] = is_array($apks['apks'] ?? null) ? $apks['apks'] : [];
    $apks['apks'][] = ['name' => $name, 'version' => $version, 'package' => $package, 'description' => $description, 'logo' => $logo, 'size' => $size, 'file_url' => $fileUrl, 'downloads' => 0, 'created' => date('Y-m-d H:i:s')];
    saveJsonData(APKS_FILE, $apks);
    recordAudit('apk_added', ['name' => $name, 'version' => $version]);
    return ['success' => true, 'message' => 'APK added successfully'];
}

function handleEditAPK() {
    $index = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
    $name = cleanText($_POST['name'] ?? '', 100);
    $version = cleanText($_POST['version'] ?? '', 40);
    $package = cleanText($_POST['package'] ?? '', 160);
    $description = cleanText($_POST['description'] ?? '', 500);
    $logo = trim($_POST['logo'] ?? '');
    $size = cleanText($_POST['size'] ?? '', 40);
    $fileUrl = trim($_POST['file_url'] ?? '');
    if ($index === false || $index < 0 || $name === '' || $version === '' || $package === '' || !isSafeRemoteUrl($fileUrl)) return ['success' => false, 'error' => 'Enter valid APK details and a public download URL'];
    if ($logo !== '' && !isSafeRemoteUrl($logo)) $logo = '';
    $apks = getJsonData(APKS_FILE);
    if (!isset($apks['apks'][$index])) return ['success' => false, 'error' => 'APK not found'];
    $old = $apks['apks'][$index];
    $apks['apks'][$index] = ['name' => $name, 'version' => $version, 'package' => $package, 'description' => $description, 'logo' => $logo, 'size' => $size, 'file_url' => $fileUrl, 'downloads' => (int)($old['downloads'] ?? 0), 'created' => $old['created'] ?? date('Y-m-d H:i:s')];
    saveJsonData(APKS_FILE, $apks);
    recordAudit('apk_updated', ['name' => $name, 'version' => $version]);
    return ['success' => true, 'message' => 'APK updated successfully'];
}

function handleDeleteAPK() {
    $index = filter_input(INPUT_POST, 'index', FILTER_VALIDATE_INT);
    $apks = getJsonData(APKS_FILE);
    if ($index === false || $index < 0 || !isset($apks['apks'][$index])) return ['success' => false, 'error' => 'APK not found'];
    $deleted = $apks['apks'][$index];
    array_splice($apks['apks'], $index, 1);
    saveJsonData(APKS_FILE, $apks);
    recordAudit('apk_deleted', ['name' => $deleted['name'] ?? '']);
    return ['success' => true, 'message' => 'APK deleted successfully'];
}

function handleClearUser() {
    $ip = trim($_POST['ip'] ?? '');
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return ['success' => false, 'error' => 'Valid IP required'];
    $users = getJsonData(USERS_FILE); unset($users['users'][$ip]); saveJsonData(USERS_FILE, $users);
    $licenses = getJsonData(LICENSES_FILE);
    $licenses['licenses'] = array_values(array_filter($licenses['licenses'] ?? [], fn($license) => ($license['ip'] ?? '') !== $ip));
    saveJsonData(LICENSES_FILE, $licenses);
    recordAudit('user_cleared', ['ip' => $ip]);
    return ['success' => true, 'message' => 'User cleared successfully'];
}

function handleClearAll() {
    saveJsonData(LICENSES_FILE, ['licenses' => []]);
    saveJsonData(USERS_FILE, ['users' => []]);
    recordAudit('all_generation_data_cleared');
    return ['success' => true, 'message' => 'All data cleared successfully'];
}

function handleUpdateSecurity() {
    $settings = settings();
    $security = $settings['security'];
    $security['rate_limit_enabled'] = !empty($_POST['rate_limit_enabled']);
    $security['rate_limit_window'] = min(3600, max(10, (int)($_POST['rate_limit_window'] ?? 60)));
    $security['rate_limit_requests'] = min(1000, max(1, (int)($_POST['rate_limit_requests'] ?? 30)));
    $security['login_max_attempts'] = min(20, max(3, (int)($_POST['login_max_attempts'] ?? 5)));
    $security['login_lock_minutes'] = min(1440, max(1, (int)($_POST['login_lock_minutes'] ?? 15)));
    $security['session_timeout_minutes'] = min(1440, max(5, (int)($_POST['session_timeout_minutes'] ?? 120)));
    $security['audit_retention_days'] = min(365, max(1, (int)($_POST['audit_retention_days'] ?? 30)));
    $allowlist = trim($_POST['admin_ip_allowlist'] ?? '');
    $security['admin_ip_allowlist'] = [];
    foreach (preg_split('/[\s,]+/', $allowlist, -1, PREG_SPLIT_NO_EMPTY) as $ip) if (filter_var($ip, FILTER_VALIDATE_IP)) $security['admin_ip_allowlist'][] = $ip;
    $settings['security'] = $security;
    $settings['maintenance_mode'] = !empty($_POST['maintenance_mode']);
    $settings['api_maintenance'] = !empty($_POST['api_maintenance']);
    $settings['public_generation_enabled'] = !empty($_POST['public_generation_enabled']);
    $settings['cooldown_minutes'] = min(1440, max(1, (int)($_POST['cooldown_minutes'] ?? 5)));
    $settings['referral_code'] = cleanText($_POST['referral_code'] ?? ($settings['referral_code'] ?? ''), 80);
    if (!empty($_POST['new_password'])) {
        $newPassword = (string)$_POST['new_password'];
        if (strlen($newPassword) < 10) return ['success' => false, 'error' => 'New password must be at least 10 characters'];
        $settings['admin_password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $settings['must_change_password'] = false;
        clearRateLimit('login');
    }
    saveJsonData(SETTINGS_FILE, $settings);
    recordAudit('security_settings_updated');
    return ['success' => true, 'message' => 'Security settings saved successfully'];
}

function handleBlockIp() {
    $ip = trim($_POST['ip'] ?? '');
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return ['success' => false, 'error' => 'Valid IP required'];
    $settings = settings();
    $blocked = $settings['security']['blocked_ips'] ?? [];
    if (!in_array($ip, $blocked, true)) $blocked[] = $ip;
    $settings['security']['blocked_ips'] = array_values($blocked);
    saveJsonData(SETTINGS_FILE, $settings);
    recordAudit('ip_blocked', ['ip' => $ip]);
    return ['success' => true, 'message' => 'IP blocked'];
}

function handleUnblockIp() {
    $ip = trim($_POST['ip'] ?? '');
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return ['success' => false, 'error' => 'Valid IP required'];
    $settings = settings();
    $settings['security']['blocked_ips'] = array_values(array_filter($settings['security']['blocked_ips'] ?? [], fn($blockedIp) => $blockedIp !== $ip));
    saveJsonData(SETTINGS_FILE, $settings);
    recordAudit('ip_unblocked', ['ip' => $ip]);
    return ['success' => true, 'message' => 'IP unblocked'];
}

function handleClearAudit() {
    saveJsonData(AUDIT_FILE, ['events' => []]);
    recordAudit('audit_log_cleared');
    return ['success' => true, 'message' => 'Audit log cleared'];
}

function handleClearRateLimits() {
    saveJsonData(RATE_LIMIT_FILE, ['buckets' => []]);
    recordAudit('rate_limits_cleared');
    return ['success' => true, 'message' => 'Rate limits cleared'];
}
?>
