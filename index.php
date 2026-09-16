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
        .home-shell { max-width: 1140px; margin: 0 auto; }
        .home-hero { position:relative; overflow:hidden; padding:36px 0 28px; }
        .home-hero::before { content:""; position:absolute; width:380px; height:380px; border-radius:50%; right:-170px; top:-210px; background:radial-gradient(circle,rgba(124,108,255,.18),transparent 68%); pointer-events:none; }
        .home-hero h1 { max-width:720px; margin:0; font-size:clamp(2.1rem,5vw,4.25rem); line-height:.98; font-weight:850; letter-spacing:-.075em; }
        .home-hero h1 span { color:var(--hp-primary); }
        .home-hero p { max-width:560px; margin:16px 0 0; color:var(--hp-muted); line-height:1.65; font-size:.94rem; }
        .hero-badges { display:flex; flex-wrap:wrap; gap:8px; margin-top:22px; }
        .hero-badge { display:inline-flex; align-items:center; gap:7px; padding:8px 11px; border:1px solid var(--hp-border); border-radius:999px; color:var(--hp-muted); background:rgba(255,255,255,.45); font-size:.7rem; font-weight:700; line-height:1; white-space:nowrap; }
        .hero-badge i { display:inline-block; flex:0 0 auto; color:var(--hp-primary); font-size:.9rem; line-height:1; }
        .license-layout { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(260px,.75fr); gap:18px; align-items:start; }
        .license-card { border-radius:22px; }
        .license-card .card-header { padding:22px 26px; }
        .license-card .card-body { padding:26px; }
        .license-card .card-footer { padding:16px 26px; }
        .section-kicker { color:var(--hp-primary); font-size:.66rem; font-weight:800; letter-spacing:.14em; text-transform:uppercase; }
        .application-field { position:relative; }
        .application-trigger { display:flex; align-items:center; justify-content:space-between; gap:12px; width:100%; min-height:68px; padding:11px 14px; text-align:left; color:var(--hp-text); border:1px solid rgba(74,88,125,.18); border-radius:14px; background:rgba(255,255,255,.64); font:inherit; line-height:1.2; appearance:none; -webkit-appearance:none; transition:border-color .18s var(--hp-ease),box-shadow .18s var(--hp-ease),background .18s var(--hp-ease); }
        .application-trigger:hover, .application-trigger.open { border-color:var(--hp-primary); box-shadow:0 0 0 4px rgba(124,108,255,.11); }
        .trigger-content { display:flex; align-items:center; gap:12px; min-width:0; flex:1 1 auto; }
        .trigger-logo, .menu-logo { display:grid; place-items:center; flex:0 0 auto; overflow:hidden; color:#fff; background:linear-gradient(135deg,var(--hp-primary),var(--hp-accent)); box-shadow:0 7px 16px rgba(104,89,231,.18); line-height:1; }
        .trigger-logo { width:42px; height:42px; border-radius:12px; }
        .trigger-logo img, .menu-logo img { width:100%; height:100%; object-fit:cover; }
        .trigger-copy { min-width:0; }
        .trigger-title, .trigger-subtitle { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .trigger-title { font-size:.86rem; font-weight:750; }
        .trigger-subtitle { margin-top:2px; color:var(--hp-muted); font-size:.7rem; }
        .trigger-chevron { display:block; flex:0 0 auto; color:var(--hp-primary); font-size:1.1rem; line-height:1; transition:transform .18s var(--hp-ease); }
        .application-trigger.open .trigger-chevron { transform:rotate(180deg); }
        .application-menu { display:none; position:absolute; z-index:20; top:calc(100% + 9px); left:0; right:0; padding:8px; border:1px solid var(--hp-border); border-radius:16px; background:var(--hp-card); box-shadow:0 20px 50px rgba(20,25,50,.18); backdrop-filter:blur(18px); }
        .application-menu.open { display:block; animation:menuIn .18s var(--hp-ease); }
        @keyframes menuIn { from { opacity:0; transform:translateY(-5px) scale(.98); } to { opacity:1; transform:translateY(0) scale(1); } }
        .application-option { display:flex; align-items:center; gap:12px; width:100%; padding:10px; border:0; border-radius:11px; color:var(--hp-text); background:transparent; font:inherit; line-height:1.2; text-align:left; cursor:pointer; appearance:none; -webkit-appearance:none; }
        .application-option:hover, .application-option.selected { background:rgba(124,108,255,.1); }
        .application-option:disabled { opacity:.55; cursor:not-allowed; }
        .menu-logo { width:38px; height:38px; border-radius:11px; }
        .menu-copy { min-width:0; flex:1; }
        .menu-name, .menu-meta { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .menu-name { font-size:.82rem; font-weight:750; }
        .menu-meta { margin-top:2px; color:var(--hp-muted); font-size:.68rem; }
        .menu-check { display:block; flex:0 0 auto; color:var(--hp-primary); line-height:1; opacity:0; }
        .application-option.selected .menu-check { opacity:1; }
        .side-panel { border-radius:22px; padding:24px; }
        .side-panel h3 { font-size:1rem; margin:0 0 18px; font-weight:800; }
        .side-item { display:flex; gap:12px; align-items:flex-start; padding:13px 0; border-bottom:1px solid var(--hp-border); }
        .side-item:last-child { border-bottom:0; padding-bottom:0; }
        .side-icon { display:grid; place-items:center; width:34px; height:34px; flex:0 0 34px; border-radius:10px; color:var(--hp-primary); background:rgba(124,108,255,.11); line-height:1; }
        .side-item strong { display:block; font-size:.8rem; }
        .side-item span { display:block; margin-top:3px; color:var(--hp-muted); font-size:.73rem; line-height:1.45; }
        .mobile-menu-lines { display:flex; flex:0 0 18px; flex-direction:column; gap:4px; width:18px; height:16px; justify-content:center; }
        .mobile-menu-lines span { display:block; width:18px; height:2px; border-radius:2px; background:currentColor; transition:transform .18s var(--hp-ease),opacity .18s var(--hp-ease); }
        .navbar-toggler[aria-expanded="true"] .mobile-menu-lines span:nth-child(1) { transform:translateY(6px) rotate(45deg); }
        .navbar-toggler[aria-expanded="true"] .mobile-menu-lines span:nth-child(2) { opacity:0; }
        .navbar-toggler[aria-expanded="true"] .mobile-menu-lines span:nth-child(3) { transform:translateY(-6px) rotate(-45deg); }
        [data-bs-theme="dark"] .hero-badge, [data-bs-theme="dark"] .application-trigger { background:rgba(19,24,37,.7); }
        [data-bs-theme="dark"] .application-menu { background:rgba(22,27,40,.96); }
        @media (max-width:991px) { .license-layout { grid-template-columns:1fr; } .side-panel { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; } .side-panel h3 { grid-column:1/-1; margin-bottom:0; } .side-item { border:1px solid var(--hp-border); border-radius:12px; padding:12px; } }
        @media (max-width:767px) { .home-hero { padding:28px 0 22px; } .home-hero h1 { font-size:clamp(2.1rem,12vw,3.25rem); } .license-card .card-header, .license-card .card-body { padding:21px; } .license-card .card-footer { padding:15px 21px; } .side-panel { display:block; padding:20px; } .side-item { border-bottom:1px solid var(--hp-border); border-left:0; border-right:0; border-radius:0; padding:12px 0; } .application-trigger { min-height:64px; padding:10px 12px; } .trigger-content { gap:10px; } .trigger-title, .trigger-subtitle { max-width:calc(100vw - 150px); } .application-menu { max-height: min(360px, 55vh); overflow-y:auto; } }
    </style>
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container"><a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL"><span>HEX PROTOCOL</span></a><button class="navbar-toggler" type="button" id="mobileMenuToggle" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation"><span class="mobile-menu-lines" aria-hidden="true"><span></span><span></span><span></span></span></button><div class="collapse navbar-collapse" id="publicNavbar"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1"><li class="nav-item"><a class="nav-link active" href="index.php">Key Free</a></li><li class="nav-item"><a class="nav-link" href="downloadapk.php">Downloads</a></li><li class="nav-item"><a class="nav-link" href="login.php">Login</a></li><li class="nav-item"><a class="nav-link" href="register.php">Register</a></li><li class="nav-item"><a class="nav-link" href="#" id="bd-theme" title="Toggle theme"><i class="bi bi-moon-stars" id="bd-theme-icon"></i></a></li></ul></div></div>
    </nav>
    <main class="public-main"><div class="container home-shell">
        <section class="home-hero"><div class="section-kicker mb-3">License gateway</div><h1>Your access.<br><span>One clean flow.</span></h1><p>Choose an application below and generate your free license key through the secure provider network.</p><div class="hero-badges"><span class="hero-badge"><i class="bi bi-shield-check"></i>Protected requests</span><span class="hero-badge"><i class="bi bi-lightning-charge-fill"></i>Fast routing</span><span class="hero-badge"><i class="bi bi-phone"></i>Mobile ready</span></div></section>
        <div class="license-layout"><section class="card card-primary card-outline license-card"><div class="card-header d-flex justify-content-between align-items-center"><div><h2 class="card-title">Create Free License</h2><div class="card-subtitle mt-1">Select an application to continue.</div></div><span class="badge text-bg-primary">Global</span></div><form id="licenseForm"><div class="card-body"><div class="mb-3"><div class="d-flex justify-content-between align-items-center mb-2"><label for="applicationTrigger" class="form-label mb-0">Application</label><span id="selectionHint" class="text-muted small">Select Application</span></div><select name="app_id" id="app_id" class="visually-hidden" required tabindex="-1" aria-hidden="true"><option value="">Select Application</option><?php foreach ($activeApps as $app): ?><option value="<?php echo (int)$app['id']; ?>" data-duration="<?php echo (int)($app['duration_hours'] ?? 5); ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>"><?php echo htmlspecialchars($app['name']); ?></option><?php endforeach; ?></select><div class="application-field"><button type="button" class="application-trigger" id="applicationTrigger" aria-expanded="false" aria-controls="applicationMenu"><span class="trigger-content"><span class="trigger-logo" id="triggerLogo"><i class="bi bi-grid-3x3-gap"></i></span><span class="trigger-copy"><span class="trigger-title" id="triggerTitle">Select Application</span><span class="trigger-subtitle" id="triggerSubtitle">Choose an app to continue</span></span></span><i class="bi bi-chevron-down trigger-chevron" aria-hidden="true"></i></button><div class="application-menu" id="applicationMenu" role="listbox" aria-label="Applications"><?php foreach ($activeApps as $app): ?><button type="button" class="application-option<?php echo !empty($app['maintenance']) ? ' disabled' : ''; ?>" data-app-id="<?php echo (int)$app['id']; ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>" <?php echo !empty($app['maintenance']) ? 'disabled' : ''; ?> role="option" aria-selected="false"><span class="menu-logo"><?php if (!empty($app['logo'])): ?><img src="<?php echo htmlspecialchars($app['logo'], ENT_QUOTES); ?>" alt=""><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?></span><span class="menu-copy"><span class="menu-name"><?php echo htmlspecialchars($app['name']); ?></span><span class="menu-meta"><?php echo !empty($app['maintenance']) ? 'Maintenance' : htmlspecialchars(($app['game'] ?? 'Application')); ?></span></span><i class="bi bi-check2 menu-check"></i></button><?php endforeach; ?></div></div></div><div class="row g-3"><div class="col-md-6"><label for="max_devices" class="form-label">Devices</label><div class="input-group"><input type="number" name="max_devices" id="max_devices" class="form-control" value="1" disabled><span class="input-group-text">device</span></div></div><div class="col-md-6"><label for="duration" class="form-label">Duration</label><select name="duration" id="duration" class="form-select" disabled><option value="5" selected>Choose an application</option></select></div><div class="col-12"><label for="vip_key" class="form-label">Key Type</label><select name="vip_key" id="vip_key" class="form-select" disabled><option value="1" selected>FREE</option></select></div></div><div id="validationResult" class="mt-3" role="status" aria-live="polite"></div></div><div class="card-footer bg-transparent d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3"><small class="text-muted"><i class="bi bi-shield-check me-1"></i>One request per cooldown window.</small><button type="submit" class="btn btn-primary px-4" id="btn_submit"><i class="bi bi-key-fill me-1"></i>Generate</button></div></form></section><aside class="card side-panel"><h3>Simple and ready</h3><div class="side-item"><span class="side-icon"><i class="bi bi-lightning-charge-fill"></i></span><div><strong>Fast provider routing</strong><span>Your request is sent to the active provider for the selected application.</span></div></div><div class="side-item"><span class="side-icon"><i class="bi bi-shield-lock-fill"></i></span><div><strong>Protected requests</strong><span>Cooldowns and validation help keep the public flow clean and reliable.</span></div></div><div class="side-item"><span class="side-icon"><i class="bi bi-phone-fill"></i></span><div><strong>Works on mobile</strong><span>A compact layout that stays clear on phones, tablets, and desktop.</span></div></div></aside></div>
    </div></main>
    <footer class="public-footer py-3"><div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2"><small>&copy; 2018 - 2026 HEX PROTOCOL</small><div class="d-flex gap-3"><a href="https://t.me/+NBy4GLVQGYFiOTU1" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a><a href="https://youtube.com/@define_hex?si=61l7qUBxotBPO3q" target="_blank" rel="noopener" aria-label="Youtube"><i class="bi bi-youtube"></i></a></div></div></footer>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="public.js"></script>
    <script>
    (function(){
        var menuToggle=document.getElementById('mobileMenuToggle'), nav=document.getElementById('publicNavbar');
        if(menuToggle&&nav){ menuToggle.addEventListener('click',function(){ var open=nav.classList.toggle('show'); menuToggle.setAttribute('aria-expanded',open?'true':'false'); }); nav.querySelectorAll('a').forEach(function(link){ link.addEventListener('click',function(){ nav.classList.remove('show'); menuToggle.setAttribute('aria-expanded','false'); }); }); }
        var trigger=document.getElementById('applicationTrigger'), menu=document.getElementById('applicationMenu'), select=document.getElementById('app_id');
        function closeMenu(){ if(!menu||!trigger)return; menu.classList.remove('open'); trigger.classList.remove('open'); trigger.setAttribute('aria-expanded','false'); }
        if(trigger&&menu){ trigger.addEventListener('click',function(){ var open=!menu.classList.contains('open'); menu.classList.toggle('open',open); trigger.classList.toggle('open',open); trigger.setAttribute('aria-expanded',open?'true':'false'); }); document.addEventListener('click',function(e){ if(!e.target.closest('.application-field'))closeMenu(); }); document.addEventListener('keydown',function(e){ if(e.key==='Escape')closeMenu(); }); }
        $('.application-option').on('click',function(){ var id=String($(this).data('app-id')), option=$('#app_id option[value="'+id+'"]'), logo=$(this).find('img').attr('src'), name=$(this).find('.menu-name').text(), game=$(this).find('.menu-meta').text(); $('#app_id').val(id); $('.application-option').removeClass('selected').attr('aria-selected','false'); $(this).addClass('selected').attr('aria-selected','true'); $('#selectionHint').text(name); $('#triggerTitle').text(name); $('#triggerSubtitle').text(game); $('#triggerLogo').html(logo?'<img src="'+escapeHtml(logo)+'" alt="">':'<i class="bi bi-box-seam"></i>'); closeMenu(); });
        $('#licenseForm').on('submit',function(e){ e.preventDefault(); var selected=$('#app_id option:selected'), appId=selected.val(), duration=selected.data('duration')||5; $('#duration').html('<option value="'+duration+'" selected>'+duration+' Hours</option>'); if(!appId){ $('#validationResult').html('<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Please select an application.</div>'); return; } if(selected.data('maintenance')==1){ $('#validationResult').html('<div class="alert alert-warning mb-0">This API is under maintenance. Choose another API.</div>'); return; } $('#btn_submit').prop('disabled',true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generating...'); $('#validationResult').html(''); $.ajax({url:'api.php?action=generate',method:'POST',data:{app_id:appId},dataType:'json',success:function(response){ if(response.success){ window.location.href='redirect.php?handoff='+encodeURIComponent(response.handoff); }else{ $('#btn_submit').prop('disabled',false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>'+escapeHtml(response.error||'Failed to generate license')+'</div>'); } },error:function(){ $('#btn_submit').prop('disabled',false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-wifi-off me-1"></i>An error occurred. Please try again.</div>'); }}); });
        function escapeHtml(value){ return String(value).replace(/[&<>'"]/g,function(char){ return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]; }); }
    })();
    </script>
</body>
</html>
