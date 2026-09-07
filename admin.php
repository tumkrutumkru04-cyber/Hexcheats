<?php
require_once 'config.php';

$settings = settings();
$loginError = null;
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $guard = requestGuard('login');
    if ($guard) {
        $loginError = $guard['error'];
    } elseif (!verifyCsrf()) {
        $loginError = 'Security token expired. Please refresh and try again.';
    } else {
        $username = cleanText($_POST['username'] ?? '', 80);
        $password = (string)($_POST['password'] ?? '');
        if ($username === ($settings['admin_username'] ?? 'admin') && password_verify($password, $settings['admin_password'] ?? '')) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_last_activity'] = time();
            $_SESSION['admin_username'] = $username;
            clearRateLimit('login');
            recordAudit('admin_login_success');
            header('Location: admin.php');
            exit;
        }
        recordAudit('admin_login_failed', ['username' => $username]);
        $loginError = 'Invalid credentials or account temporarily locked.';
    }
}

if (isset($_GET['logout'])) {
    if (isAdminLoggedIn()) recordAudit('admin_logout');
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_last_activity'], $_SESSION['admin_username']);
    header('Location: admin.php');
    exit;
}

$isLoggedIn = isAdminLoggedIn();
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verifyCsrf()) {
        $flash = ['type' => 'danger', 'text' => 'Security token expired. Refresh the page and try again.'];
    } else {
        $settings['cooldown_minutes'] = min(1440, max(1, (int)($_POST['cooldown_minutes'] ?? 5)));
        $settings['referral_code'] = cleanText($_POST['referral_code'] ?? '', 80);
        if (!empty($_POST['new_password'])) {
            $newPassword = (string)$_POST['new_password'];
            if (strlen($newPassword) < 10) {
                $flash = ['type' => 'danger', 'text' => 'New password must be at least 10 characters.'];
            } else {
                $settings['admin_password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $settings['must_change_password'] = false;
                saveJsonData(SETTINGS_FILE, $settings);
                recordAudit('admin_password_changed');
                $flash = ['type' => 'success', 'text' => 'Password and system settings saved.'];
            }
        } else {
            saveJsonData(SETTINGS_FILE, $settings);
            recordAudit('system_settings_updated');
            $flash = ['type' => 'success', 'text' => 'System settings saved.'];
        }
        $settings = settings();
    }
}

$apps = getJsonData(APPLICATIONS_FILE);
$apks = getJsonData(APKS_FILE);
$users = getJsonData(USERS_FILE);
$licenses = getJsonData(LICENSES_FILE);
$audit = getJsonData(AUDIT_FILE);
$security = $settings['security'];
$licensesData = array_reverse($licenses['licenses'] ?? []);
$usersData = $users['users'] ?? [];
$appsData = $apps['applications'] ?? [];
$apksData = $apks['apks'] ?? [];
$auditData = array_reverse($audit['events'] ?? []);
$today = date('Y-m-d');
$licensesToday = count(array_filter($licenses['licenses'] ?? [], fn($license) => str_starts_with((string)($license['created'] ?? ''), $today)));
$activeApps = count(array_filter($appsData, fn($app) => ($app['status'] ?? '') === 'active'));
$blockedIps = $security['blocked_ips'] ?? [];
?><!doctype html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES); ?>">
    <link href="https://i.ibb.co/8LJmm2FH/20260904-033616.png" rel="shortcut icon" type="image/x-icon">
    <title>HEX PROTOCOL — Control Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --hp-blue:#1677ff; --hp-ink:#14213d; --hp-muted:#718096; --hp-bg:#f4f7fb; --hp-card:#fff; --hp-line:#e6edf5; }
        * { box-sizing:border-box; }
        body { background:var(--hp-bg); color:var(--hp-ink); font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; }
        .shell { min-height:100vh; display:flex; }
        .sidebar { width:258px; background:#101828; color:#d0d5dd; padding:24px 16px; position:fixed; inset:0 auto 0 0; z-index:1030; display:flex; flex-direction:column; }
        .brand { color:#fff; text-decoration:none; display:flex; gap:10px; align-items:center; font-weight:800; letter-spacing:.02em; font-size:1.05rem; padding:6px 10px 28px; }
        .brand img { width:34px; height:34px; object-fit:cover; border-radius:10px; }
        .eyebrow { color:#98a2b3; font-size:.68rem; text-transform:uppercase; letter-spacing:.13em; font-weight:700; padding:12px 12px 8px; }
        .side-link { display:flex; align-items:center; gap:11px; color:#98a2b3; border-radius:10px; padding:11px 12px; text-decoration:none; font-weight:600; font-size:.9rem; margin:2px 0; transition:.18s ease; }
        .side-link:hover,.side-link.active { background:#1d2939; color:#fff; }
        .side-link.active { box-shadow:inset 3px 0 var(--hp-blue); }
        .side-link i { font-size:1.05rem; width:20px; text-align:center; }
        .side-bottom { margin-top:auto; border-top:1px solid #1d2939; padding-top:14px; }
        .main { margin-left:258px; width:calc(100% - 258px); min-height:100vh; }
        .topbar { height:76px; background:rgba(255,255,255,.86); backdrop-filter:blur(12px); border-bottom:1px solid var(--hp-line); display:flex; align-items:center; justify-content:space-between; padding:0 34px; position:sticky; top:0; z-index:100; }
        .page-title h1 { font-size:1.35rem; margin:0; font-weight:800; }
        .page-title p { margin:3px 0 0; color:var(--hp-muted); font-size:.82rem; }
        .content { padding:30px 34px 48px; max-width:1500px; }
        .surface { background:var(--hp-card); border:1px solid var(--hp-line); border-radius:16px; box-shadow:0 8px 28px rgba(16,24,40,.045); }
        .metric { padding:19px; min-height:126px; position:relative; overflow:hidden; }
        .metric:after { content:""; width:86px; height:86px; border-radius:50%; background:rgba(22,119,255,.08); position:absolute; right:-25px; bottom:-25px; }
        .metric .label { color:var(--hp-muted); font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        .metric .value { font-size:2rem; line-height:1.1; font-weight:850; margin-top:12px; }
        .metric .icon { position:absolute; right:17px; top:17px; width:34px; height:34px; display:grid; place-items:center; border-radius:10px; background:#edf4ff; color:var(--hp-blue); z-index:1; }
        .section-card { padding:22px; }
        .section-head { display:flex; justify-content:space-between; gap:12px; align-items:flex-start; margin-bottom:17px; }
        .section-head h2 { font-size:1rem; margin:0; font-weight:800; }
        .section-head p { color:var(--hp-muted); font-size:.8rem; margin:4px 0 0; }
        .table { --bs-table-bg:transparent; margin:0; font-size:.84rem; vertical-align:middle; }
        .table thead th { color:#667085; font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; border-bottom-color:var(--hp-line); white-space:nowrap; }
        .table td { border-color:#eef2f6; }
        .table code { color:#315aa8; font-size:.78rem; }
        .badge-soft { padding:6px 9px; border-radius:999px; font-size:.7rem; font-weight:700; }
        .badge-soft.green { background:#ecfdf3; color:#027a48; }.badge-soft.red { background:#fef3f2; color:#b42318; }.badge-soft.blue { background:#eff8ff; color:#175cd3; }.badge-soft.amber { background:#fffaeb; color:#b54708; }
        .security-banner { border:1px solid #b2ddff; background:linear-gradient(120deg,#eff8ff,#f5f9ff); border-radius:14px; padding:17px 19px; display:flex; align-items:flex-start; gap:14px; }
        .security-banner i { color:var(--hp-blue); font-size:1.25rem; }
        .security-banner strong { display:block; font-size:.9rem; margin-bottom:3px; }.security-banner span { color:#475467; font-size:.8rem; }
        .form-label { font-size:.78rem; font-weight:700; color:#475467; }.form-control,.form-select { border-color:#d0d5dd; border-radius:9px; font-size:.86rem; padding:.62rem .75rem; }.form-control:focus,.form-select:focus { border-color:#84adff; box-shadow:0 0 0 .2rem rgba(22,119,255,.12); }
        .btn { border-radius:9px; font-weight:700; font-size:.82rem; }.btn-primary { background:var(--hp-blue); border-color:var(--hp-blue); }.btn-light { border-color:#d0d5dd; background:#fff; }.btn-icon { width:32px; height:32px; padding:0; display:inline-grid; place-items:center; }
        .login-wrap { min-height:100vh; display:grid; place-items:center; padding:24px; background:radial-gradient(circle at top right,#e6f0ff,transparent 45%), var(--hp-bg); }.login-card { width:min(430px,100%); padding:30px; }.login-logo { width:52px; height:52px; object-fit:cover; border-radius:15px; margin-bottom:18px; }.login-card h1 { font-size:1.55rem; font-weight:850; }.login-card .sub { color:var(--hp-muted); font-size:.84rem; margin-bottom:24px; }
        .notice { border-radius:10px; padding:11px 13px; font-size:.8rem; }.notice.warning { background:#fffaeb; color:#b54708; border:1px solid #fedf89; }.notice.danger { background:#fef3f2; color:#b42318; border:1px solid #fecdca; }.notice.success { background:#ecfdf3; color:#027a48; border:1px solid #abefc6; }
        .empty { text-align:center; color:var(--hp-muted); padding:28px 10px; font-size:.84rem; }
        .mobile-top { display:none; }
        [data-bs-theme="dark"] { --hp-bg:#0b1220; --hp-card:#111b2d; --hp-ink:#f2f4f7; --hp-line:#25344d; --hp-muted:#98a2b3; }
        [data-bs-theme="dark"] .topbar { background:rgba(17,27,45,.9); }.dark-muted { color:var(--hp-muted); }
        [data-bs-theme="dark"] .metric .icon { background:#1c3156; }.table { color:var(--hp-ink); }.table thead th { color:#98a2b3; }.table td { border-color:var(--hp-line); }.btn-light { background:#162238; color:#e4e7ec; border-color:#344054; }.form-control,.form-select { background:#111b2d; color:#f2f4f7; border-color:#344054; }.form-label { color:#d0d5dd; }.security-banner { background:#102a43; border-color:#1d5b8f; }.security-banner span { color:#b9d8f5; }
        @media (max-width: 991px) { .sidebar { transform:translateX(-100%); transition:.2s ease; }.sidebar.open { transform:none; }.main { margin-left:0; width:100%; }.mobile-top { display:block; }.topbar { padding:0 18px; }.content { padding:22px 18px 38px; } }
        @media (max-width: 575px) { .topbar { height:68px; }.page-title h1 { font-size:1.08rem; }.page-title p { display:none; }.metric .value { font-size:1.65rem; }.section-card { padding:16px; } }
    </style>
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
<?php if (!$isLoggedIn): ?>
    <div class="login-wrap">
        <div class="surface login-card">
            <img class="login-logo" src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL">
            <h1>Admin control center</h1>
            <p class="sub">Manage keys, applications, downloads, access controls, and platform security from one place.</p>
            <?php if ($loginError): ?><div class="notice danger mb-3"><i class="bi bi-exclamation-triangle me-1"></i><?php echo htmlspecialchars($loginError); ?></div><?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES); ?>">
                <div class="mb-3"><label class="form-label" for="adminUser">Username</label><input class="form-control" id="adminUser" name="username" value="" autocomplete="username" required></div>
                <div class="mb-4"><label class="form-label" for="adminPass">Password</label><input class="form-control" id="adminPass" type="password" name="password" autocomplete="current-password" required></div>
                <button class="btn btn-primary w-100 py-2" type="submit" name="login"><i class="bi bi-shield-lock me-2"></i>Sign in securely</button>
            </form>
            <div class="d-flex justify-content-between align-items-center mt-4"><a class="small text-decoration-none" href="index.php"><i class="bi bi-arrow-left me-1"></i>Return to site</a><button class="btn btn-light btn-sm" id="themeToggle"><i class="bi bi-moon-stars"></i></button></div>
            <p class="dark-muted small mt-4 mb-0">Default bootstrap credentials should be changed immediately after first sign-in.</p>
        </div>
    </div>
<?php else: ?>
    <div class="shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="index.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt=""><span>HEX PROTOCOL</span></a>
            <div class="eyebrow">Workspace</div>
            <a class="side-link active" href="#overview"><i class="bi bi-grid-1x2-fill"></i>Overview</a>
            <a class="side-link" href="#licenses"><i class="bi bi-key-fill"></i>Licenses</a>
            <a class="side-link" href="#users"><i class="bi bi-people-fill"></i>Users</a>
            <a class="side-link" href="#applications"><i class="bi bi-boxes"></i>Applications</a>
            <a class="side-link" href="#downloads"><i class="bi bi-download"></i>Downloads</a>
            <div class="eyebrow mt-3">Protection</div>
            <a class="side-link" href="#security"><i class="bi bi-shield-check"></i>Security center</a>
            <a class="side-link" href="vplink.php"><i class="bi bi-link-45deg"></i>VPLINK settings</a>
            <a class="side-link" href="#audit"><i class="bi bi-activity"></i>Audit trail</a>
            <div class="side-bottom">
                <a class="side-link" href="index.php"><i class="bi bi-box-arrow-up-right"></i>View public site</a>
                <a class="side-link" href="?logout=1"><i class="bi bi-box-arrow-right"></i>Sign out</a>
            </div>
        </aside>
        <main class="main">
            <header class="topbar">
                <div class="d-flex align-items-center gap-3"><button class="btn btn-light btn-icon mobile-top" id="menuToggle"><i class="bi bi-list"></i></button><div class="page-title"><h1>Control center</h1><p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Administrator'); ?>. Here is your platform status.</p></div></div>
                <div class="d-flex align-items-center gap-2"><span class="badge-soft green"><i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle"></i>System online</span><button class="btn btn-light btn-icon" id="themeToggle" title="Toggle theme"><i class="bi bi-moon-stars"></i></button></div>
            </header>
            <div class="content">
                <?php if (!empty($settings['must_change_password'])): ?><div class="notice warning mb-4"><i class="bi bi-shield-exclamation me-2"></i><strong>Action required:</strong> the bootstrap administrator password is still active. Change it in <a href="#security" class="alert-link">Security center</a> before sharing access.</div><?php endif; ?>
                <?php if ($flash): ?><div class="notice <?php echo htmlspecialchars($flash['type']); ?> mb-4"><?php echo htmlspecialchars($flash['text']); ?></div><?php endif; ?>
                <section id="overview" class="mb-4">
                    <div class="row g-3">
                        <div class="col-6 col-xl-3"><div class="surface metric"><div class="icon"><i class="bi bi-key"></i></div><div class="label">Total licenses</div><div class="value"><?php echo number_format(count($licenses['licenses'] ?? [])); ?></div><div class="dark-muted small mt-2"><span class="text-success fw-bold">+<?php echo $licensesToday; ?></span> generated today</div></div></div>
                        <div class="col-6 col-xl-3"><div class="surface metric"><div class="icon"><i class="bi bi-people"></i></div><div class="label">Tracked users</div><div class="value"><?php echo number_format(count($usersData)); ?></div><div class="dark-muted small mt-2">IP activity records</div></div></div>
                        <div class="col-6 col-xl-3"><div class="surface metric"><div class="icon"><i class="bi bi-app-indicator"></i></div><div class="label">Active apps</div><div class="value"><?php echo number_format($activeApps); ?></div><div class="dark-muted small mt-2"><?php echo count($appsData); ?> configured total</div></div></div>
                        <div class="col-6 col-xl-3"><div class="surface metric"><div class="icon"><i class="bi bi-shield-lock"></i></div><div class="label">Blocked IPs</div><div class="value"><?php echo number_format(count($blockedIps)); ?></div><div class="dark-muted small mt-2">Rate limiting <?php echo !empty($security['rate_limit_enabled']) ? 'enabled' : 'disabled'; ?></div></div></div>
                    </div>
                </section>
                <div class="security-banner mb-4"><i class="bi bi-shield-check"></i><div><strong>Application protection is active</strong><span>Requests are validated, sensitive actions require CSRF protection, admin access is rate limited, and remote provider calls use verified HTTPS/TLS settings. For volumetric DDoS protection, place the site behind a managed edge WAF such as Cloudflare.</span></div><a class="btn btn-sm btn-light ms-auto text-nowrap" href="#security">Review controls</a></div>

                <section id="licenses" class="surface section-card mb-4"><div class="section-head"><div><h2>License activity</h2><p>Recent keys generated through the public flow.</p></div><button class="btn btn-outline-danger btn-sm" onclick="clearAllLicenses()"><i class="bi bi-trash3 me-1"></i>Clear data</button></div><div class="table-responsive"><table class="table"><thead><tr><th>Key</th><th>Application</th><th>Game</th><th>IP address</th><th>Created</th><th>Status</th></tr></thead><tbody><?php if (!$licensesData): ?><tr><td colspan="6"><div class="empty">No licenses have been generated yet.</div></td></tr><?php else: foreach (array_slice($licensesData, 0, 18) as $license): ?><tr><td><code><?php echo htmlspecialchars($license['key'] ?? 'N/A'); ?></code></td><td><?php echo htmlspecialchars($license['app_name'] ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($license['game'] ?? 'N/A'); ?></td><td class="font-monospace small"><?php echo htmlspecialchars($license['ip'] ?? 'N/A'); ?></td><td class="small dark-muted"><?php echo htmlspecialchars($license['created'] ?? 'N/A'); ?></td><td><span class="badge-soft green">Active</span></td></tr><?php endforeach; endif; ?></tbody></table></div></section>

                <section id="users" class="surface section-card mb-4"><div class="section-head"><div><h2>User activity</h2><p>Per-IP generation counters and cooldown state.</p></div><span class="badge-soft blue"><?php echo count($usersData); ?> records</span></div><div class="table-responsive"><table class="table"><thead><tr><th>IP address</th><th>Total generated</th><th>Last generation</th><th>Action</th></tr></thead><tbody><?php if (!$usersData): ?><tr><td colspan="4"><div class="empty">No user activity recorded yet.</div></td></tr><?php else: foreach ($usersData as $ip => $user): ?><tr><td class="font-monospace small"><?php echo htmlspecialchars($ip); ?></td><td><span class="badge-soft blue"><?php echo (int)($user['total_generated'] ?? 0); ?></span></td><td class="small dark-muted"><?php echo !empty($user['last_generation']) ? date('Y-m-d H:i:s', (int)$user['last_generation']) : 'Never'; ?></td><td><button class="btn btn-outline-danger btn-sm" onclick="clearUser(<?php echo htmlspecialchars(json_encode($ip), ENT_QUOTES); ?>)"><i class="bi bi-trash3 me-1"></i>Clear</button> <button class="btn btn-outline-secondary btn-sm" onclick="blockIp(<?php echo htmlspecialchars(json_encode($ip), ENT_QUOTES); ?>)"><i class="bi bi-slash-circle me-1"></i>Block</button></td></tr><?php endforeach; endif; ?></tbody></table></div></section>

                <section id="applications" class="surface section-card mb-4"><div class="section-head"><div><h2>Application management</h2><p>Control remote key providers and public availability.</p></div><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAppModal"><i class="bi bi-plus-lg me-1"></i>Add application</button></div><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Name</th><th>Game</th><th>Provider</th><th>Status</th><th>Actions</th></tr></thead><tbody><?php if (!$appsData): ?><tr><td colspan="6"><div class="empty">No applications configured.</div></td></tr><?php else: foreach ($appsData as $app): ?><tr><td class="dark-muted">#<?php echo (int)$app['id']; ?></td><td class="fw-semibold"><?php echo htmlspecialchars($app['name']); ?></td><td><?php echo htmlspecialchars($app['game']); ?></td><td><span class="badge-soft <?php echo ($app['api_type'] ?? 'default') === 'moco' ? 'amber' : 'blue'; ?>"><?php echo htmlspecialchars(strtoupper($app['api_type'] ?? 'default')); ?></span></td><td><span class="badge-soft <?php echo ($app['status'] ?? '') === 'active' ? 'green' : 'red'; ?>"><?php echo htmlspecialchars(ucfirst($app['status'] ?? 'inactive')); ?></span></td><td><button class="btn btn-light btn-icon me-1" title="Edit" onclick="editApp(<?php echo (int)$app['id']; ?>)"><i class="bi bi-pencil"></i></button><button class="btn btn-outline-danger btn-icon" title="Delete" onclick="deleteApp(<?php echo (int)$app['id']; ?>)"><i class="bi bi-trash3"></i></button></td></tr><?php endforeach; endif; ?></tbody></table></div></section>

                <section id="downloads" class="surface section-card mb-4"><div class="section-head"><div><h2>Download catalog</h2><p>Manage APK listings displayed on the public downloads page.</p></div><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAPKModal"><i class="bi bi-plus-lg me-1"></i>Add download</button></div><div class="table-responsive"><table class="table"><thead><tr><th>Name</th><th>Version</th><th>Package</th><th>Size</th><th>Downloads</th><th>Actions</th></tr></thead><tbody><?php if (!$apksData): ?><tr><td colspan="6"><div class="empty">No APK downloads have been added.</div></td></tr><?php else: foreach ($apksData as $idx => $apk): ?><tr><td class="fw-semibold"><?php echo htmlspecialchars($apk['name']); ?></td><td><?php echo htmlspecialchars($apk['version']); ?></td><td class="small dark-muted"><?php echo htmlspecialchars($apk['package']); ?></td><td><?php echo htmlspecialchars($apk['size'] ?? '—'); ?></td><td><?php echo (int)($apk['downloads'] ?? 0); ?></td><td><button class="btn btn-light btn-icon me-1" onclick="editAPK(<?php echo (int)$idx; ?>)"><i class="bi bi-pencil"></i></button><button class="btn btn-outline-danger btn-icon" onclick="deleteAPK(<?php echo (int)$idx; ?>)"><i class="bi bi-trash3"></i></button></td></tr><?php endforeach; endif; ?></tbody></table></div></section>

                <section id="security" class="surface section-card mb-4"><div class="section-head"><div><h2>Security center</h2><p>Manage rate limits, sessions, maintenance mode, and admin access policy.</p></div><span class="badge-soft green"><i class="bi bi-lock-fill me-1"></i>Protected</span></div>
                    <form id="securityForm"><div class="row g-3"><div class="col-md-6 col-xl-3"><label class="form-label">Public generation</label><select class="form-select" name="public_generation_enabled"><option value="1" <?php echo !empty($settings['public_generation_enabled']) ? 'selected' : ''; ?>>Enabled</option><option value="0" <?php echo empty($settings['public_generation_enabled']) ? 'selected' : ''; ?>>Disabled</option></select></div><div class="col-md-6 col-xl-3"><label class="form-label">Maintenance mode</label><select class="form-select" name="maintenance_mode"><option value="0" <?php echo empty($settings['maintenance_mode']) ? 'selected' : ''; ?>>Off</option><option value="1" <?php echo !empty($settings['maintenance_mode']) ? 'selected' : ''; ?>>On</option></select></div><div class="col-md-6 col-xl-3"><label class="form-label">Cooldown minutes</label><input class="form-control" type="number" name="cooldown_minutes" min="1" max="1440" value="<?php echo (int)($settings['cooldown_minutes'] ?? 5); ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Referral code</label><input class="form-control" name="referral_code" value="<?php echo htmlspecialchars($settings['referral_code'] ?? ''); ?>"></div><div class="col-12"><hr class="my-1"></div><div class="col-md-6 col-xl-3"><label class="form-label">Rate limiting</label><select class="form-select" name="rate_limit_enabled"><option value="1" <?php echo !empty($security['rate_limit_enabled']) ? 'selected' : ''; ?>>Enabled</option><option value="0" <?php echo empty($security['rate_limit_enabled']) ? 'selected' : ''; ?>>Disabled</option></select></div><div class="col-md-6 col-xl-3"><label class="form-label">Requests per window</label><input class="form-control" type="number" name="rate_limit_requests" min="1" max="1000" value="<?php echo (int)$security['rate_limit_requests']; ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Window (seconds)</label><input class="form-control" type="number" name="rate_limit_window" min="10" max="3600" value="<?php echo (int)$security['rate_limit_window']; ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Login max attempts</label><input class="form-control" type="number" name="login_max_attempts" min="3" max="20" value="<?php echo (int)$security['login_max_attempts']; ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Login lock (minutes)</label><input class="form-control" type="number" name="login_lock_minutes" min="1" max="1440" value="<?php echo (int)$security['login_lock_minutes']; ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Admin session timeout</label><input class="form-control" type="number" name="session_timeout_minutes" min="5" max="1440" value="<?php echo (int)$security['session_timeout_minutes']; ?>"></div><div class="col-md-6 col-xl-3"><label class="form-label">Audit retention (days)</label><input class="form-control" type="number" name="audit_retention_days" min="1" max="365" value="<?php echo (int)$security['audit_retention_days']; ?>"></div><div class="col-md-6"><label class="form-label">Admin IP allowlist <span class="dark-muted fw-normal">(optional, comma or space separated)</span></label><input class="form-control" name="admin_ip_allowlist" value="<?php echo htmlspecialchars(implode(', ', $security['admin_ip_allowlist'] ?? [])); ?>" placeholder="203.0.113.10"></div><div class="col-md-6"><label class="form-label">Change administrator password</label><input class="form-control" type="password" name="new_password" minlength="10" autocomplete="new-password" placeholder="At least 10 characters"></div></div><div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" type="submit"><i class="bi bi-shield-check me-1"></i>Save protection settings</button><button class="btn btn-light" type="button" onclick="clearRateLimits()"><i class="bi bi-arrow-repeat me-1"></i>Reset rate limits</button></div><div id="securityResult" class="mt-3"></div></form>
                    <div class="row g-3 mt-3"><div class="col-lg-6"><div class="border rounded-3 p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-2"><strong class="small">Blocked IP addresses</strong><span class="badge-soft red"><?php echo count($blockedIps); ?></span></div><?php if (!$blockedIps): ?><div class="dark-muted small">No blocked IPs. Use the user table or add one below.</div><?php else: foreach ($blockedIps as $ip): ?><div class="d-flex justify-content-between align-items-center border-top py-2"><code><?php echo htmlspecialchars($ip); ?></code><button class="btn btn-outline-success btn-sm" onclick="unblockIp(<?php echo htmlspecialchars(json_encode($ip), ENT_QUOTES); ?>)">Unblock</button></div><?php endforeach; endif; ?><div class="input-group input-group-sm mt-3"><input class="form-control" id="blockIpInput" placeholder="IP address"><button class="btn btn-outline-danger" onclick="blockIp(document.getElementById('blockIpInput').value)">Block IP</button></div></div></div><div class="col-lg-6"><div class="border rounded-3 p-3 h-100"><strong class="small d-block mb-2">Edge DDoS protection</strong><p class="dark-muted small mb-2">Application controls reduce abuse and request floods. Volumetric attacks must be absorbed upstream by your DNS/hosting provider.</p><ol class="dark-muted small mb-0 ps-3"><li>Proxy the domain through Cloudflare or your host WAF.</li><li>Enable managed challenge/rate limiting rules.</li><li>Keep origin access restricted to trusted proxy IPs.</li></ol></div></div></div>
                </section>

                <section id="audit" class="surface section-card"><div class="section-head"><div><h2>Audit trail</h2><p>Recent administrative and security events.</p></div><button class="btn btn-light btn-sm" onclick="clearAudit()"><i class="bi bi-eraser me-1"></i>Clear log</button></div><div class="table-responsive"><table class="table"><thead><tr><th>Event</th><th>Details</th><th>IP</th><th>Timestamp</th></tr></thead><tbody><?php if (!$auditData): ?><tr><td colspan="4"><div class="empty">No audit events yet.</div></td></tr><?php else: foreach (array_slice($auditData, 0, 25) as $event): ?><tr><td><span class="badge-soft blue"><?php echo htmlspecialchars(str_replace('_', ' ', ucfirst($event['event'] ?? 'event'))); ?></span></td><td class="small dark-muted"><?php echo htmlspecialchars(json_encode($event['details'] ?? [], JSON_UNESCAPED_SLASHES)); ?></td><td class="font-monospace small"><?php echo htmlspecialchars($event['ip'] ?? ''); ?></td><td class="small dark-muted"><?php echo htmlspecialchars($event['created'] ?? ''); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section>
            </div>
        </main>
    </div>

    <div class="modal fade" id="addAppModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add application</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Application name</label><input id="appName" class="form-control"></div><div class="mb-3"><label class="form-label">Game name</label><input id="appGame" class="form-control"></div><div class="mb-3"><label class="form-label">Provider API URL</label><input id="appApiUrl" class="form-control" placeholder="https://provider.example/api"></div><div><label class="form-label">API type</label><select id="appApiType" class="form-select"><option value="default">Default</option><option value="moco">MOCO</option></select></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="addApp()">Add application</button></div></div></div></div>
    <div class="modal fade" id="addAPKModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add download</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Application name</label><input id="apkName" class="form-control"></div><div class="row g-3"><div class="col-6"><label class="form-label">Version</label><input id="apkVersion" class="form-control"></div><div class="col-6"><label class="form-label">File size</label><input id="apkSize" class="form-control"></div></div><div class="mb-3 mt-3"><label class="form-label">Package name</label><input id="apkPackage" class="form-control"></div><div class="mb-3"><label class="form-label">Description</label><textarea id="apkDesc" class="form-control" rows="2"></textarea></div><div class="mb-3"><label class="form-label">Logo URL (optional)</label><input id="apkLogo" class="form-control"></div><div><label class="form-label">Download URL</label><input id="apkFileUrl" class="form-control" placeholder="https://example.com/app.apk"></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="addAPK()">Add download</button></div></div></div></div>
    <div class="modal fade" id="editAPKModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit download</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" id="editAPKIndex"><div class="mb-3"><label class="form-label">Application name</label><input id="editApkName" class="form-control"></div><div class="row g-3"><div class="col-6"><label class="form-label">Version</label><input id="editApkVersion" class="form-control"></div><div class="col-6"><label class="form-label">File size</label><input id="editApkSize" class="form-control"></div></div><div class="mb-3 mt-3"><label class="form-label">Package name</label><input id="editApkPackage" class="form-control"></div><div class="mb-3"><label class="form-label">Description</label><textarea id="editApkDesc" class="form-control" rows="2"></textarea></div><div class="mb-3"><label class="form-label">Logo URL</label><input id="editApkLogo" class="form-control"></div><div><label class="form-label">Download URL</label><input id="editApkFileUrl" class="form-control"></div></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" onclick="updateAPK()">Save changes</button></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const APPS = <?php echo json_encode($appsData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const APKS = <?php echo json_encode($apksData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    function api(action, data={}, onDone) { data.csrf_token=CSRF; $.ajax({url:'api.php?action='+encodeURIComponent(action),method:'POST',data,dataType:'json'}).done(function(r){ if(onDone) onDone(r); else if(!r.success) alert(r.error||'Action failed'); }).fail(function(){ alert('The request could not be completed.'); }); }
    function refreshIfOk(r){ if(r.success) window.location.reload(); else alert(r.error||'Action failed'); }
    function clearAllLicenses(){ if(confirm('Clear all license and user activity data? This cannot be undone.')) api('clear_all',{},refreshIfOk); }
    function clearUser(ip){ if(ip && confirm('Clear this user activity and their generated licenses?')) api('clear_user',{ip},refreshIfOk); }
    function blockIp(ip){ ip=(ip||'').trim(); if(!ip){alert('Enter an IP address.');return;} api('block_ip',{ip},refreshIfOk); }
    function unblockIp(ip){ if(confirm('Unblock '+ip+'?')) api('unblock_ip',{ip},refreshIfOk); }
    function clearAudit(){ if(confirm('Clear the audit log?')) api('clear_audit',{},refreshIfOk); }
    function clearRateLimits(){ api('clear_rate_limits',{},function(r){ if(r.success) showResult('securityResult','Rate-limit buckets reset.','success'); else showResult('securityResult',r.error||'Action failed','danger'); }); }
    function showResult(id,text,type){ const el=document.getElementById(id); if(el) el.innerHTML='<div class="notice '+type+'">'+text+'</div>'; }
    function addApp(){ const data={name:$('#appName').val(),game:$('#appGame').val(),api_url:$('#appApiUrl').val(),api_type:$('#appApiType').val()}; if(!data.name||!data.game||!data.api_url){alert('All fields are required.');return;} api('add_application',data,refreshIfOk); }
    function editApp(id){ const app=APPS.find(a=>Number(a.id)===Number(id)); if(!app)return; const name=prompt('Application name:',app.name); if(name===null)return; const game=prompt('Game name:',app.game); if(game===null)return; const api_url=prompt('Provider API URL:',app.api_url); if(api_url===null)return; const api_type=prompt('API type (default or moco):',app.api_type||'default'); if(api_type===null)return; const status=confirm('Press OK to keep this application active. Press Cancel to disable it.')?'active':'inactive'; api('edit_application',{id,name,game,api_url,api_type,status},refreshIfOk); }
    function deleteApp(id){ if(confirm('Delete this application?')) api('delete_application',{id},refreshIfOk); }
    function addAPK(){ const data={name:$('#apkName').val(),version:$('#apkVersion').val(),package:$('#apkPackage').val(),description:$('#apkDesc').val(),logo:$('#apkLogo').val(),size:$('#apkSize').val(),file_url:$('#apkFileUrl').val()}; if(!data.name||!data.version||!data.package||!data.file_url){alert('Name, version, package, and download URL are required.');return;} api('add_apk',data,refreshIfOk); }
    function editAPK(index){ const apk=APKS[index]; if(!apk)return; $('#editAPKIndex').val(index); $('#editApkName').val(apk.name||''); $('#editApkVersion').val(apk.version||''); $('#editApkPackage').val(apk.package||''); $('#editApkDesc').val(apk.description||''); $('#editApkLogo').val(apk.logo||''); $('#editApkSize').val(apk.size||''); $('#editApkFileUrl').val(apk.file_url||''); bootstrap.Modal.getOrCreateInstance(document.getElementById('editAPKModal')).show(); }
    function updateAPK(){ const data={index:$('#editAPKIndex').val(),name:$('#editApkName').val(),version:$('#editApkVersion').val(),package:$('#editApkPackage').val(),description:$('#editApkDesc').val(),logo:$('#editApkLogo').val(),size:$('#editApkSize').val(),file_url:$('#editApkFileUrl').val()}; if(!data.name||!data.version||!data.package||!data.file_url){alert('Name, version, package, and download URL are required.');return;} api('edit_apk',data,refreshIfOk); }
    function deleteAPK(index){ if(confirm('Delete this download listing?')) api('delete_apk',{index},refreshIfOk); }
    $('#securityForm').on('submit',function(e){e.preventDefault(); const data=Object.fromEntries(new FormData(this).entries()); api('update_security',data,function(r){showResult('securityResult',r.success?'Security settings saved successfully.':(r.error||'Unable to save settings.'),r.success?'success':'danger'); if(r.success)setTimeout(function(){window.location.reload()},700);});});
    document.querySelectorAll('.side-link[href^="#"]').forEach(function(link){link.addEventListener('click',function(){document.querySelectorAll('.side-link').forEach(x=>x.classList.remove('active'));link.classList.add('active');document.getElementById('sidebar').classList.remove('open');});});
    document.getElementById('menuToggle')?.addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('open'));
    function setTheme(){ const current=document.documentElement.getAttribute('data-bs-theme')||'light'; const next=current==='dark'?'light':'dark'; document.documentElement.setAttribute('data-bs-theme',next); localStorage.setItem('selectedTheme',next); document.querySelectorAll('#themeToggle i').forEach(i=>i.className=next==='dark'?'bi bi-sun':'bi bi-moon-stars'); }
    document.querySelectorAll('#themeToggle').forEach(b=>b.addEventListener('click',setTheme));
    </script>
<?php endif; ?>
</body></html>
