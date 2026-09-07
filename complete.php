<?php
require_once 'config.php';

$handoff = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['handoff'] ?? ''));
$sig = preg_replace('/[^a-f0-9]/i', '', (string)($_GET['sig'] ?? ''));
$destination = 'generated.php?handoff=' . urlencode($handoff) . '&sig=' . urlencode($sig);

// The generated page performs the final validation again. This endpoint is intentionally
// only a server-side redirect so the raw VPLINK URL is never rendered by HEX PROTOCOL.
if (validateVplinkReturn($handoff, $sig)) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Location: ' . $destination, true, 302);
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $destination, true, 302);
exit;
