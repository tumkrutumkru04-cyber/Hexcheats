<?php
require_once 'config.php';

$handoffToken = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['handoff'] ?? ''));
$handoff = getKeyHandoff($handoffToken);
$payload = is_array($handoff['payload'] ?? null) ? $handoff['payload'] : [];
$app = $payload['app_name'] ?? 'HEX PROTOCOL';
$duration = $payload['duration_value'] ?? '5';
$finalUrl = ($handoff && $handoffToken !== '') ? buildPublicUrl('complete.php', ['handoff' => $handoffToken, 'sig' => handoffSignature($handoffToken)]) : '';
$shortResult = $finalUrl !== '' ? shortenWithVplink($finalUrl, 'hex_' . substr($handoffToken, 0, 10)) : ['success' => false, 'error' => 'Invalid handoff'];
$shortUrl = $shortResult['success'] ? $shortResult['url'] : '';
$vplink = vplinkSettings();
if ($shortUrl && !empty($_SESSION['key_handoffs'][$handoffToken])) {
    $_SESSION['key_handoffs'][$handoffToken]['shortener_started'] = true;
    $_SESSION['key_handoffs'][$handoffToken]['shortener_started_at'] = time();
}
$directFallbackAllowed = !$shortUrl && !$vplink['block_without_shortener'] && $handoff;
if ($directFallbackAllowed) $_SESSION['vplink_returns'][$handoffToken] = time();
$destination = $shortUrl ?: ($directFallbackAllowed ? buildPublicUrl('generated.php', ['handoff' => $handoffToken, 'sig' => handoffSignature($handoffToken)]) : '');
$error = (!$handoff || $destination === '') ? 'Bypass blocked. Generate a new key and complete the secure task.' : ($shortUrl ? '' : 'The secure task is temporarily unavailable.');
?><!doctype html>
<html data-bs-theme="light" lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="color-scheme" content="light dark">
    <?php if ($destination !== ''): ?><meta http-equiv="refresh" content="2;url=<?php echo htmlspecialchars($destination, ENT_QUOTES); ?>"><?php endif; ?>
    <link rel="icon" href="https://i.ibb.co/8LJmm2FH/20260904-033616.png" type="image/png"><title>HEX PROTOCOL - Continue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"><link rel="stylesheet" href="public.css">
    <style>.flow-steps{display:flex;gap:8px;justify-content:center;margin:0 auto 24px;max-width:430px}.flow-step{flex:1;padding:9px 5px;border-radius:10px;background:var(--bs-tertiary-bg);color:var(--bs-secondary-color);font-size:.72rem;font-weight:600}.flow-step.active{background:rgba(13,110,253,.1);color:var(--bs-primary)}.flow-step i{display:block;font-size:1rem;margin-bottom:3px}</style>
    <script>(function(){var t=localStorage.getItem('selectedTheme')||'light';document.documentElement.setAttribute('data-bs-theme',t==='dark'?'dark':'light');})();</script>
</head>
<body>
<nav class="navbar navbar-expand-lg public-navbar"><div class="container"><a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php"><img src="https://i.ibb.co/8LJmm2FH/20260904-033616.png" alt="HEX PROTOCOL"><span>HEX PROTOCOL</span></a><div class="ms-auto"><a class="nav-link d-inline-block" href="#" id="bd-theme" title="Toggle theme"><i class="bi bi-moon-stars" id="bd-theme-icon"></i></a></div></div></nav>
<main class="public-main"><div class="container"><div class="result-card"><div class="card card-primary card-outline"><div class="card-body text-center p-4 p-md-5"><?php if ($error && !$handoff): ?><div class="text-danger mb-3"><i class="bi bi-shield-x fs-2"></i></div><h1 class="h4 mb-2">Bypass blocked</h1><p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p><a href="index.php" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i>Generate a new key</a><?php else: ?><div class="text-primary mb-3"><i class="bi bi-shield-lock fs-2"></i></div><h1 class="h4 mb-2">Secure key task</h1><p class="text-muted mb-3">Complete the task. Your key page will open automatically when it is finished.</p><div class="flow-steps"><div class="flow-step active"><i class="bi bi-check-circle"></i>Generated</div><div class="flow-step active"><i class="bi bi-hourglass-split"></i>Complete task</div><div class="flow-step"><i class="bi bi-key"></i>Get key</div></div><p class="small text-muted mb-3">Application: <strong><?php echo htmlspecialchars($app); ?></strong> · <?php echo htmlspecialchars($duration); ?> hours</p><?php if ($error): ?><div class="alert alert-warning text-start small"><i class="bi bi-info-circle me-1"></i><?php echo htmlspecialchars($error); ?></div><?php endif; ?><div class="progress mb-3" role="progressbar" aria-label="Opening secure task" style="height:6px"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:80%"></div></div><?php if ($destination !== ''): ?><p class="small text-muted mb-0"><i class="bi bi-arrow-repeat me-1"></i>Continuing automatically…</p><?php else: ?><a href="index.php" class="btn btn-primary"><i class="bi bi-arrow-left me-1"></i>Generate a new key</a><?php endif; ?><?php endif; ?></div></div></div></div></main>
<footer class="public-footer py-3"><div class="container d-flex justify-content-between align-items-center"><small>&copy; 2018 - 2026 HEX PROTOCOL</small><a href="index.php" class="small"><i class="bi bi-arrow-left me-1"></i>Back to generator</a></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script src="public.js"></script><?php if ($destination !== ''): ?><script>setTimeout(function(){window.location.href=<?php echo json_encode($destination); ?>;},2000);</script><?php endif; ?>
</body></html>
