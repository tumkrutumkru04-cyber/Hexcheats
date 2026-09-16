<?php
require_once 'config.php';

$apps = getJsonData(APPLICATIONS_FILE);
$activeApps = array_values(array_filter($apps['applications'] ?? [], static function ($app) {
    return ($app['status'] ?? '') === 'active';
}));
function hp_e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?><!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" href="https://i.ibb.co/8LJmm2FH/20260904-033616.png" type="image/png">
    <title>HEX PROTOCOL — Free License Gateway</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="public.css">
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
<header class="site-header">
    <nav class="navbar navbar-expand-lg public-navbar">
        <div class="container nav-inner">
            <a class="navbar-brand brand-mark" href="index.php" aria-label="HEX PROTOCOL home">
                <span class="brand-icon"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt=""></span>
                <span>HEX PROTOCOL</span>
            </a>
            <button class="navbar-toggler" type="button" id="mobileMenuToggle" aria-controls="publicNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="mobile-menu-lines" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Key Free</a></li>
                    <li class="nav-item"><a class="nav-link" href="downloadapk.php">Downloads</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link theme-button" href="#" id="bd-theme" title="Toggle theme" aria-label="Toggle theme"><i class="bi bi-moon-stars" id="bd-theme-icon"></i></a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="public-main">
    <div class="container home-shell">
        <section class="home-hero">
            <div class="hero-copy">
                <div class="eyebrow"><span class="eyebrow-dot"></span> License gateway</div>
                <h1>Simple access.<br><span>Built for speed.</span></h1>
                <p>Choose your application and generate a free license key through our secure provider network.</p>
                <div class="hero-badges" aria-label="Platform features">
                    <span class="hero-badge"><i class="bi bi-shield-check"></i>Protected requests</span>
                    <span class="hero-badge"><i class="bi bi-lightning-charge-fill"></i>Fast routing</span>
                    <span class="hero-badge"><i class="bi bi-phone"></i>Mobile ready</span>
                </div>
            </div>
            <div class="hero-orbit" aria-hidden="true"><span></span><span></span><span></span></div>
        </section>

        <div class="license-layout">
            <section class="card license-card">
                <div class="card-header license-header">
                    <div><div class="card-overline">Free access</div><h2 class="card-title">Create your license</h2><div class="card-subtitle">Select an application to continue.</div></div>
                    <span class="global-pill"><i class="bi bi-globe2"></i> Global</span>
                </div>
                <form id="licenseForm" novalidate>
                    <div class="card-body license-body">
                        <div class="field-block">
                            <div class="field-heading"><label for="applicationTrigger" class="form-label">Application</label><span id="selectionHint" class="field-hint">Select application</span></div>
                            <select name="app_id" id="app_id" class="visually-hidden" required tabindex="-1" aria-hidden="true"><option value="">Select Application</option><?php foreach ($activeApps as $app): ?><option value="<?php echo (int)$app['id']; ?>" data-duration="<?php echo (int)($app['duration_hours'] ?? 5); ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>"><?php echo hp_e($app['name']); ?></option><?php endforeach; ?></select>
                            <div class="application-field">
                                <button type="button" class="application-trigger" id="applicationTrigger" aria-expanded="false" aria-controls="applicationMenu"><span class="trigger-content"><span class="trigger-logo" id="triggerLogo"><i class="bi bi-grid-3x3-gap"></i></span><span class="trigger-copy"><span class="trigger-title" id="triggerTitle">Select application</span><span class="trigger-subtitle" id="triggerSubtitle">Choose an app to continue</span></span></span><i class="bi bi-chevron-down trigger-chevron" aria-hidden="true"></i></button>
                                <div class="application-menu" id="applicationMenu" role="listbox" aria-label="Applications">
                                    <?php foreach ($activeApps as $app): ?><button type="button" class="application-option<?php echo !empty($app['maintenance']) ? ' disabled' : ''; ?>" data-app-id="<?php echo (int)$app['id']; ?>" data-maintenance="<?php echo !empty($app['maintenance']) ? '1' : '0'; ?>" <?php echo !empty($app['maintenance']) ? 'disabled' : ''; ?> role="option" aria-selected="false"><span class="menu-logo"><?php if (!empty($app['logo'])): ?><img src="<?php echo hp_e($app['logo']); ?>" alt=""><?php else: ?><i class="bi bi-box-seam"></i><?php endif; ?></span><span class="menu-copy"><span class="menu-name"><?php echo hp_e($app['name']); ?></span><span class="menu-meta"><?php echo !empty($app['maintenance']) ? 'Maintenance' : hp_e($app['game'] ?? 'Application'); ?></span></span><i class="bi bi-check2 menu-check"></i></button><?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="field-block"><label for="max_devices" class="form-label">Devices</label><div class="input-group"><input type="number" name="max_devices" id="max_devices" class="form-control" value="1" disabled><span class="input-group-text">device</span></div></div>
                            <div class="field-block"><label for="duration" class="form-label">Duration</label><select name="duration" id="duration" class="form-select" disabled><option value="" selected>Select duration</option></select></div>
                            <div class="field-block field-full"><label for="vip_key" class="form-label">Key type</label><select name="vip_key" id="vip_key" class="form-select" disabled><option value="1" selected>FREE</option></select></div>
                        </div>
                        <div id="validationResult" class="validation-result" role="status" aria-live="polite"></div>
                    </div>
                    <div class="card-footer license-footer"><small><i class="bi bi-shield-check"></i> One request per cooldown window.</small><button type="submit" class="btn btn-primary generate-button" id="btn_submit" disabled aria-disabled="true"><i class="bi bi-key-fill"></i> Generate</button></div>
                </form>
            </section>

            <aside class="card side-panel">
                <div class="side-heading"><div class="card-overline">Why HEX</div><h2>Simple and ready</h2></div>
                <div class="side-item"><span class="side-icon"><i class="bi bi-lightning-charge-fill"></i></span><div><strong>Fast provider routing</strong><span>Your request is sent to the active provider for the selected application.</span></div></div>
                <div class="side-item"><span class="side-icon"><i class="bi bi-shield-lock-fill"></i></span><div><strong>Protected requests</strong><span>Cooldowns and validation keep the public flow clean and reliable.</span></div></div>
                <div class="side-item"><span class="side-icon"><i class="bi bi-phone-fill"></i></span><div><strong>Works on mobile</strong><span>A compact layout that stays clear on phones, tablets, and desktop.</span></div></div>
            </aside>
        </div>
    </div>
</main>

<footer class="public-footer"><div class="container footer-inner"><small>© 2018–2026 <strong>HEX PROTOCOL</strong></small><div class="footer-links"><a href="https://t.me/+NBy4GLVQGYFiOTU1" target="_blank" rel="noopener" aria-label="Telegram"><i class="bi bi-telegram"></i></a><a href="https://youtube.com/@define_hex?si=61l7qUBxotBPO3q" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="public.js"></script>
</body>
</html>
