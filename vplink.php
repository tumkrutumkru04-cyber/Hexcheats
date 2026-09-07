<?php
require_once 'config.php';

if (!isAdminLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$settings = settings();
$vplink = vplinkSettings();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $flash = ['type' => 'danger', 'text' => 'Security token expired. Refresh the page and try again.'];
    } else {
        $newToken = trim((string)($_POST['api_token'] ?? ''));
        if ($newToken !== '' && !preg_match('/^[A-Za-z0-9_-]{24,200}$/', $newToken)) {
            $flash = ['type' => 'danger', 'text' => 'Enter a valid VPLINK API token or leave the field empty to keep the server-side token.'];
        } else {
            $settings['vplink'] = [
                'enabled' => !empty($_POST['enabled']),
                'api_token' => $newToken !== '' ? cleanText($newToken, 160) : ($settings['vplink']['api_token'] ?? ''),
                'completion_window_seconds' => 60,
                'handoff_ttl_seconds' => min(86400, max(300, (int)($_POST['handoff_ttl_seconds'] ?? 1800))),
                'block_without_shortener' => !empty($_POST['block_without_shortener'])
            ];
            saveJsonData(SETTINGS_FILE, $settings);
            recordAudit('vplink_settings_updated', [
                'enabled' => $settings['vplink']['enabled'],
                'completion_window_seconds' => 60,
                'block_without_shortener' => $settings['vplink']['block_without_shortener']
            ]);
            $flash = ['type' => 'success', 'text' => 'VPLINK settings saved successfully.'];
            $settings = settings();
            $vplink = vplinkSettings();
        }
    }
}

$effectiveToken = getVplinkApiToken();
$tokenSource = !empty($settings['vplink']['api_token']) ? 'Admin setting' : 'Server-side fallback';
?><!doctype html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light dark"><meta name="csrf-token" content="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES); ?>">
    <link rel="icon" href="https://i.ibb.co/8LJmm2FH/20260904-033616.png" type="image/png"><title>HEX PROTOCOL — VPLINK Settings</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root{--blue:#1677ff;--ink:#14213d;--muted:#718096;--bg:#f4f7fb;--line:#e6edf5;--card:#fff}*{box-sizing:border-box}body{background:var(--bg);color:var(--ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}.topbar{height:74px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 34px}.brand{color:var(--ink);text-decoration:none;display:flex;align-items:center;gap:10px;font-weight:800}.brand img{width:34px;height:34px;border-radius:10px;object-fit:cover}.wrap{max-width:920px;padding:32px 20px 60px;margin:0 auto}.surface{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 8px 28px rgba(16,24,40,.045)}.header{padding:25px 26px;border-bottom:1px solid var(--line)}.header h1{font-size:1.35rem;margin:0;font-weight:800}.header p{color:var(--muted);font-size:.84rem;margin:5px 0 0}.body{padding:26px}.status{display:flex;align-items:center;gap:12px;border-radius:12px;padding:14px 16px;margin-bottom:22px}.status.on{background:#ecfdf3;color:#027a48;border:1px solid #abefc6}.status.off{background:#fef3f2;color:#b42318;border:1px solid #fecdca}.status i{font-size:1.25rem}.form-label{font-size:.78rem;font-weight:700;color:#475467}.form-control,.form-select{border-color:#d0d5dd;border-radius:9px;font-size:.86rem;padding:.62rem .75rem}.hint{color:var(--muted);font-size:.76rem;line-height:1.55}.info{background:#eff8ff;border:1px solid #b2ddff;border-radius:10px;padding:13px;color:#175cd3;font-size:.78rem}.actions{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:22px}.btn{border-radius:9px;font-size:.82rem;font-weight:700}.btn-primary{background:var(--blue);border-color:var(--blue)}[data-bs-theme="dark"]{--bg:#0b1220;--card:#111b2d;--ink:#f2f4f7;--line:#25344d;--muted:#98a2b3}[data-bs-theme="dark"] .topbar{background:#111b2d;border-color:var(--line)}[data-bs-theme="dark"] .form-control{background:#111b2d;color:#f2f4f7;border-color:#344054}[data-bs-theme="dark"] .info{background:#102a43;border-color:#1d5b8f;color:#b9d8f5}@media(max-width:575px){.topbar{padding:0 16px}.wrap{padding:22px 14px 40px}.body,.header{padding:20px}.actions{align-items:stretch;flex-direction:column-reverse}.actions>*{width:100%;text-align:center}}
    </style>
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
<header class="topbar"><a class="brand" href="admin.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL"><span>HEX PROTOCOL</span></a><div class="d-flex align-items-center gap-3"><a href="admin.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to admin</a><a href="?logout=1" class="text-secondary small text-decoration-none">Sign out</a></div></header>
<main class="wrap"><div class="surface"><div class="header"><div class="text-primary small fw-bold text-uppercase mb-2">Monetization control</div><h1>VPLINK settings</h1><p>Control the hidden short-link handoff before a visitor receives the final license key.</p></div><div class="body">
<?php if ($flash): ?><div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> small"><i class="bi <?php echo $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'; ?> me-1"></i><?php echo htmlspecialchars($flash['text']); ?></div><?php endif; ?>
<div class="status <?php echo $vplink['enabled'] ? 'on' : 'off'; ?>"><i class="bi <?php echo $vplink['enabled'] ? 'bi-check-circle-fill' : 'bi-pause-circle-fill'; ?>"></i><div><strong>Short-link flow is <?php echo $vplink['enabled'] ? 'enabled' : 'disabled'; ?></strong><div class="small">Visitors are <?php echo $vplink['enabled'] ? 'sent through the protected handoff before key delivery.' : 'not sent through VPLINK.'; ?></div></div></div>
<form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES); ?>"><div class="row g-4"><div class="col-md-6"><label class="form-label" for="api_token">VPLINK API token override</label><input type="password" class="form-control" id="api_token" name="api_token" placeholder="Leave empty to keep current token"><div class="hint mt-2">Current source: <strong><?php echo htmlspecialchars($tokenSource); ?></strong> · fingerprint: <code><?php echo htmlspecialchars(substr(hash('sha256', $effectiveToken), 0, 12)); ?></code></div></div><div class="col-md-6"><label class="form-label" for="handoff_ttl_seconds">Handoff lifetime</label><select class="form-select" id="handoff_ttl_seconds" name="handoff_ttl_seconds"><option value="900" <?php echo $vplink['handoff_ttl_seconds'] === 900 ? 'selected' : ''; ?>>15 minutes</option><option value="1800" <?php echo $vplink['handoff_ttl_seconds'] === 1800 ? 'selected' : ''; ?>>30 minutes</option><option value="3600" <?php echo $vplink['handoff_ttl_seconds'] === 3600 ? 'selected' : ''; ?>>1 hour</option><option value="86400" <?php echo $vplink['handoff_ttl_seconds'] === 86400 ? 'selected' : ''; ?>>24 hours</option></select><div class="hint mt-2">The minimum completion time is 60 seconds; later completion remains valid until the handoff lifetime expires.</div></div><div class="col-12"><div class="info"><i class="bi bi-shield-lock me-1"></i>The VPLINK URL is not shown in the site UI. Direct handoff attempts, expired sessions, and requests without the valid shortener state are blocked.</div></div><div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" <?php echo $vplink['enabled'] ? 'checked' : ''; ?>><label class="form-check-label fw-semibold" for="enabled">Enable VPLINK short-link flow</label></div></div><div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="block_without_shortener" name="block_without_shortener" <?php echo $vplink['block_without_shortener'] ? 'checked' : ''; ?>><label class="form-check-label fw-semibold" for="block_without_shortener">Block bypass and direct handoffs</label></div></div></div><div class="actions"><a href="admin.php#security" class="text-decoration-none small"><i class="bi bi-shield-check me-1"></i>Open Security center</a><button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Save VPLINK settings</button></div></form></div></div></main>
</body></html>
