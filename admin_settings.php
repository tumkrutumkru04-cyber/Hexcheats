<?php
require_once 'config.php';

if (!isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    header('Location: admin.php');
    exit;
}

$settings = settings();
$settings['cooldown_minutes'] = min(1440, max(1, (int)($_POST['cooldown_minutes'] ?? $settings['cooldown_minutes'])));
$settings['referral_code'] = cleanText($_POST['referral_code'] ?? $settings['referral_code'], 80);
if (!empty($_POST['new_password']) && strlen((string)$_POST['new_password']) >= 10) {
    $settings['admin_password'] = password_hash((string)$_POST['new_password'], PASSWORD_DEFAULT);
    $settings['must_change_password'] = false;
}
saveJsonData(SETTINGS_FILE, $settings);
recordAudit('legacy_settings_updated');
header('Location: admin.php#security');
exit;
?>
