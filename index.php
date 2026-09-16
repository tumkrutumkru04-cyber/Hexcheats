<?php
require_once 'config.php';

$apps = getJsonData(APPLICATIONS_FILE);
$activeApps = array_values(array_filter($apps['applications'] ?? [], function ($app) {
    return ($app['status'] ?? '') === 'active';
}));
?><!doctype html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="https://i.ibb.co/8LJmm2FH/20260904-033616.png" type="image/png">
    <title>HEX PROTOCOL - Get Free Key - Global</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public.css">
    <style>
        .generator-shell { max-width: 1100px; margin: 0 auto; }
        .generator-intro { display:flex; justify-content:space-between; gap:24px; align-items:flex-end; margin-bottom:28px; }
        .generator-intro h1 { font-size:clamp(2rem,4vw,3.35rem); line-height:1.02; font-weight:800; letter-spacing:-.06em; margin:0; max-width:620px; }
        .generator-intro p { color:var(--hp-muted); margin:12px 0 0; max-width:540px; line-height:1.65; }
        .status-chip { display:inline-flex; align-items:center; gap:7px; border:1px solid var(--hp-border); border-radius:999px; padding:8px 12px; color:var(--hp-muted); background:rgba(255,255,255,.52); font-size:.72rem; font-weight:700; white-space:nowrap; }
        .status-chip i { color:#25b994; font-size:.5rem; }
        .generator-card { border-radius:22px; }
        .generator-card .card-header { padding:22px 26px; }
        .generator-card .card-body { padding:26px; }
        .app-picker { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; }
        .app-option { position:relative; min-height:116px; padding:15px; border:1px solid var(--hp-border); border-radius:15px; background:rgba(255,255,255,.46); cursor:pointer; transition:transform .18s var(--hp-ease), border-color .18s var(--hp-ease), box-shadow .18s var(--hp-ease), background .18s var(--hp-ease); }
        .app-option:hover { transform:translateY(-2px); border-color:rgba(124,108,255,.4); box-shadow:0 12px 28px rgba(41,46,86,.1); }
        .app-option.selected { border-color:var(--hp-primary); background:rgba(124,108,255,.09); box-shadow:0 0 0 3px rgba(124,108,255,.13); }
        .app-option.disabled { opacity:.58; cursor:not-allowed; }
        .app-option input { position:absolute; opacity:0; pointer-events:none; }
        .app-option-logo { width:42px; height:42px; display:grid; place-items:center; border-radius:12px; overflow:hidden; color:#fff; background:linear-gradient(135deg,var(--hp-primary),var(--hp-accent)); box-shadow:0 7px 16px rgba(104,89,231,.18); }
        .app-option-logo img { width:100%; height:100%; object-fit:cover; }
        .app-option-name { display:block; margin-top:12px; font-size:.86rem; font-weight:750; overflow-wrap:anywhere; }
        .app-option-meta { display:block; margin-top:3px; color:var(--hp-muted); font-size:.7rem; }
        .selection-label { display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:10px; }
        .selection-label .form-label { margin:0; }
        .selection-label span { color:var(--hp-muted); font-size:.7rem; }
        [data-bs-theme="dark"] .status-chip, [data-bs-theme="dark"] .app-option { background:rgba(19,24,37,.7); }
        @media (max-width:767px) { .generator-intro { align-items:flex-start; flex-direction:column; gap:16px; } .generator-card .card-header, .generator-card .card-body { padding:21px; } .app-picker { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:420px) { .app-picker { grid-template-columns:1fr; } }
    </style>
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container"><a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL"><span>HEX PROTOCOL</span></a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="publicNavbar"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1"><li class="nav-item"><a class="nav-link active" href="index.php">Key Free</a></li><li class="nav-item"><a class="nav-link" href="downloadapk.php">Downloads</a></li><li class="nav-item"><a class="nav-link" href="login.php">Login</a></li><li class="nav-item"><a class="nav-link" href="register.php">Register</a></li><li class="nav-item"><a class="nav-link" href="#" id="bd-theme" title="Toggle theme"><i class="bi bi-moon-stars" id="bd-theme-icon"></i></a></li></ul></div></div>
    </nav>
    <main class="public-main"><div class="container generator-shell">
        <div class="row g-4 align-items-start"><div class="col-lg-8"><div class="card card-primary card-outline generator-card"><div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="card-title">Create Free License</h2><div class="card-subtitle mt-1">Select an application to continue.</div></div><span class="badge text-bg-primary">Global</span></div><form id="licenseForm"><div class="card-body"><div class="selection-label"><label for="app_id" class="form-label">Application</label><span id="selectionHint">Select an application</span></div><select name="app_id" id="app_id" class="visually-hidden" required tabindex="-1" aria-hidden="true"><option value="">Select Application</option><?php foreach ($activeApps as $app): ?><option value="<?php echo (int)$app['id']; ?>" data-duration="<?php echo (int)($app['duration_hours'] ?? 5); ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>"><?php echo htmlspecialchars($app['name']); ?></option><?php endforeach; ?></select><div class="app-picker" role="radiogroup" aria-label="Application selection"><?php foreach ($activeApps as $app): ?><button type="button" class="app-option<?php echo !empty($app['maintenance']) ? ' disabled' : ''; ?>" data-app-id="<?php echo (int)$app['id']; ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>" aria-pressed="false" <?php echo !empty($app['maintenance']) ? 'disabled' : ''; ?>><span class="app-option-logo"><?php if (!empty($app['logo'])): ?><img src="<?php echo htmlspecialchars($app['logo'], ENT_QUOTES); ?>" alt=""><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?></span><span class="app-option-name"><?php echo htmlspecialchars($app['name']); ?></span><span class="app-option-meta"><?php echo !empty($app['maintenance']) ? 'Maintenance' : htmlspecialchars(($app['game'] ?? 'Application')); ?></span></button><?php endforeach; ?></div><div class="row g-3 mt-1"><div class="col-md-6"><label for="max_devices" class="form-label">Devices</label><div class="input-group"><input type="number" name="max_devices" id="max_devices" class="form-control" value="1" disabled><span class="input-group-text">device</span></div></div><div class="col-md-6"><label for="duration" class="form-label">Duration</label><select name="duration" id="duration" class="form-select" disabled><option value="5" selected>Choose an application</option></select></div><div class="col-12"><label for="vip_key" class="form-label">Key Type</label><select name="vip_key" id="vip_key" class="form-select" disabled><option value="1" selected>FREE</option></select></div></div><div id="validationResult" class="mt-3" role="status" aria-live="polite"></div></div><div class="card-footer bg-transparent d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-3"><small class="text-muted"><i class="bi bi-shield-check me-1"></i>One request per cooldown window.</small><button type="submit" class="btn btn-primary px-4" id="btn_submit"><i class="bi bi-key-fill me-1"></i>Generate</button></div></form></div></div><div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h3 class="card-title mb-3">Simple and ready</h3><div class="public-feature mb-3"><i class="bi bi-lightning-charge-fill"></i><h3>Fast provider routing</h3><p>Your request is sent to the active provider for the selected application.</p></div><div class="public-feature mb-3"><i class="bi bi-shield-lock-fill"></i><h3>Protected requests</h3><p>Cooldowns and validation help keep the public flow clean and reliable.</p></div><div class="public-feature"><i class="bi bi-phone-fill"></i><h3>Works on mobile</h3><p>A compact layout that stays clear on phones, tablets, and desktop.</p></div></div></div></div></div>
    </div></main>
    <footer class="public-footer py-3"><div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2"><small>&copy; 2018 - 2026 HEX PROTOCOL</small><div class="d-flex gap-3"><a href="https://t.me/+NBy4GLVQGYFiOTU1" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a><a href="https://youtube.com/@define_hex?si=61l7qUBxotBPO3q" target="_blank" rel="noopener" aria-label="Youtube"><i class="bi bi-youtube"></i></a></div></div></footer>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="public.js"></script>
    <script>
    $('.app-option').on('click', function(){ const id=String($(this).data('app-id')); $('#app_id').val(id).trigger('change'); $('.app-option').removeClass('selected').attr('aria-pressed','false'); $(this).addClass('selected').attr('aria-pressed','true'); $('#selectionHint').text($(this).find('.app-option-name').text()); });
    $('#licenseForm').on('submit', function(e) { e.preventDefault(); const selected = $('#app_id option:selected'); const appId = selected.val(); const duration = selected.data('duration') || 5; $('#duration').html('<option value="'+duration+'" selected>'+duration+' Hours</option>'); if (!appId) { $('#validationResult').html('<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Please select an application.</div>'); return; } if (selected.data('maintenance') == 1) { $('#validationResult').html('<div class="alert alert-warning mb-0">This API is under maintenance. Choose another API.</div>'); return; } $('#btn_submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generating...'); $('#validationResult').html(''); $.ajax({url:'api.php?action=generate', method:'POST', data:{app_id:appId}, dataType:'json', success:function(response){ if(response.success){ window.location.href='redirect.php?handoff='+encodeURIComponent(response.handoff); } else { $('#btn_submit').prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>'+escapeHtml(response.error||'Failed to generate license')+'</div>'); } }, error:function(){ $('#btn_submit').prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-wifi-off me-1"></i>An error occurred. Please try again.</div>'); }}); });
    function escapeHtml(value){return String(value).replace(/[&<>'"]/g,function(char){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char];});}
    </script>
</body>
</html>
