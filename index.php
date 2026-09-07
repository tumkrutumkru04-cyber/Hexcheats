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
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL"><span>HEX PROTOCOL</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavbar" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="publicNavbar"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link active" href="index.php">Key Free</a></li>
                <li class="nav-item"><a class="nav-link" href="downloadapk.php">Downloads</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <li class="nav-item"><a class="nav-link" href="#" id="bd-theme" title="Toggle theme"><i class="bi bi-moon-stars" id="bd-theme-icon"></i></a></li>
            </ul></div>
        </div>
    </nav>

    <main class="public-main">
        <div class="container">
            <div class="row g-4 align-items-start">
                <div class="col-lg-8">
                    <div class="card card-primary card-outline" id="createLicenseCard">
                        <div class="card-header d-flex justify-content-between align-items-center py-3">
                            <div><h2 class="card-title">Create Free License</h2><div class="card-subtitle mt-1">Choose your application and generate access.</div></div>
                            <span class="badge text-bg-primary">Global</span>
                        </div>
                        <form id="licenseForm">
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6"><label for="app_id" class="form-label">Application</label><select name="app_id" id="app_id" class="form-select" required><option value="">Select Application</option><?php foreach ($activeApps as $app): ?><option value="<?php echo (int)$app['id']; ?>"><?php echo htmlspecialchars($app['name']); ?></option><?php endforeach; ?></select></div>
                                    <div class="col-md-6"><label for="max_devices" class="form-label">Devices</label><div class="input-group"><input type="number" name="max_devices" id="max_devices" class="form-control" value="1" disabled><span class="input-group-text">device</span></div></div>
                                    <div class="col-md-6"><label for="duration" class="form-label">Duration</label><select name="duration" id="duration" class="form-select" disabled><option value="5" selected>5 Hours</option></select></div>
                                    <div class="col-md-6"><label for="vip_key" class="form-label">Key Type</label><select name="vip_key" id="vip_key" class="form-select" disabled><option value="1" selected>FREE</option></select></div>
                                </div>
                                <div id="validationResult" class="mt-3" role="status" aria-live="polite"></div>
                            </div>
                            <div class="card-footer bg-transparent d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-3"><small class="text-muted"><i class="bi bi-shield-check me-1"></i>One request per cooldown window.</small><button type="submit" class="btn btn-primary px-4" id="btn_submit"><i class="bi bi-key-fill me-1"></i>Generate</button></div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4"><div class="card h-100"><div class="card-body p-4"><h3 class="card-title mb-3">Simple and ready</h3><div class="public-feature mb-3"><i class="bi bi-lightning-charge-fill"></i><h3>Fast provider routing</h3><p>Your request is sent to the active provider for the selected application.</p></div><div class="public-feature mb-3"><i class="bi bi-shield-lock-fill"></i><h3>Protected requests</h3><p>Cooldowns and validation help keep the public flow clean and reliable.</p></div><div class="public-feature"><i class="bi bi-phone-fill"></i><h3>Works on mobile</h3><p>A compact layout that stays clear on phones, tablets, and desktop.</p></div></div></div></div>
            </div>
        </div>
    </main>

    <footer class="public-footer py-3"><div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2"><small>&copy; 2018 - 2026 HEX PROTOCOL</small><div class="d-flex gap-3"><a href="https://t.me/+NBy4GLVQGYFiOTU1" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a><a href="https://youtube.com/@define_hex?si=61l7qUBxotBPO3q" target="_blank" rel="noopener" aria-label="Youtube"><i class="bi bi-youtube"></i></a></div></div></footer>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="public.js"></script>
    <script>
    $('#licenseForm').on('submit', function(e) {
        e.preventDefault();
        const appId = $('#app_id').val();
        if (!appId) { $('#validationResult').html('<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Please select an application.</div>'); return; }
        $('#btn_submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generating...');
        $('#validationResult').html('');
        $.ajax({url:'api.php?action=generate', method:'POST', data:{app_id:appId}, dataType:'json', success:function(response){ if(response.success){ window.location.href='redirect.php?handoff='+encodeURIComponent(response.handoff); } else { $('#btn_submit').prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>'+escapeHtml(response.error||'Failed to generate license')+'</div>'); } }, error:function(){ $('#btn_submit').prop('disabled', false).html('<i class="bi bi-key-fill me-1"></i>Generate'); $('#validationResult').html('<div class="alert alert-danger mb-0"><i class="bi bi-wifi-off me-1"></i>An error occurred. Please try again.</div>'); }});
    });
    function escapeHtml(value){return String(value).replace(/[&<>'"]/g,function(char){return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char];});}
    </script>
</body>
</html>
