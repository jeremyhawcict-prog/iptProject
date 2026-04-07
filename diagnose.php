<?php
/**
 * MediQueue — Diagnostic Page
 * Upload to ByetHost, visit /Mediqueue/diagnose.php, then DELETE this file.
 */
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');
?>
<!DOCTYPE html>
<html><head><title>MediQueue Diagnostics</title>
<style>body{font-family:monospace;background:#0f1a2e;color:#ccc;padding:20px;line-height:1.8;}
.ok{color:#10B981;} .fail{color:#EF4444;} .warn{color:#F59E0B;}
h2{color:#5B8DB4;border-bottom:1px solid #333;padding-bottom:8px;}
</style></head><body>
<h1>MediQueue Diagnostics</h1>

<h2>1. PHP Version</h2>
<?php
$v = phpversion();
echo "PHP $v — ";
echo version_compare($v, '8.0', '>=') ? '<span class="ok">OK (8.0+)</span>' : '<span class="fail">TOO OLD — Need PHP 8.0+. str_contains() and other features require it. Check ByetHost cPanel → PHP version.</span>';
?>

<h2>2. Database Connection</h2>
<?php
try {
    require_once __DIR__ . '/includes/config.php';
    $pdo = getDB();
    $pdo->query("SELECT 1");
    echo '<span class="ok">Connected to ' . DB_HOST . '/' . DB_NAME . '</span>';
} catch (Throwable $e) {
    echo '<span class="fail">FAILED: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>

<h2>3. Tables Check</h2>
<?php
$required = ['users','appointments','time_slots','patient_records','feedback','notifications','system_settings','doctor_schedules','audit_logs','doctor_profiles','patient_profiles','password_resets','slot_reservations','waitlist'];
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $t) {
        $found = in_array($t, $tables);
        echo "$t — " . ($found ? '<span class="ok">EXISTS</span>' : '<span class="fail">MISSING</span>') . '<br>';
    }
} catch (Throwable $e) {
    echo '<span class="fail">' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>

<h2>4. Session</h2>
<?php
echo 'session_status: ' . session_status() . ' (2 = active)<br>';
echo 'session_id: ' . session_id() . '<br>';
echo 'user_id: ' . ($_SESSION['user_id'] ?? '<span class="warn">NOT SET — not logged in</span>') . '<br>';
echo 'user_role: ' . ($_SESSION['user_role'] ?? 'N/A') . '<br>';
?>

<h2>5. API Endpoint Test</h2>
<?php
// Test what the API endpoint actually returns
$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/api/reports/dashboard-stats.php';
echo "Testing: $apiUrl<br>";

$ch = curl_init($apiUrl);
if ($ch) {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    echo "HTTP $httpCode<br>";
    if ($err) echo '<span class="fail">cURL error: ' . htmlspecialchars($err) . '</span><br>';
    if ($response) {
        $len = strlen($response);
        echo "Response length: $len bytes<br>";
        $json = json_decode($response, true);
        if ($json !== null) {
            echo '<span class="ok">Valid JSON ✓</span><br>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
        } else {
            echo '<span class="fail">NOT valid JSON! Raw response:</span><br>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
        }
    } else {
        echo '<span class="fail">Empty response</span><br>';
    }
} else {
    echo '<span class="warn">cURL not available — testing with file_get_contents</span><br>';
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => 'Cookie: PHPSESSID=' . session_id()]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    if ($response === false) {
        echo '<span class="fail">Request failed</span>';
    } else {
        echo 'Response: <pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
    }
}
?>

<h2>6. File Permissions</h2>
<?php
$paths = [
    __DIR__ . '/storage/rates' => 'storage/rates (rate limiter)',
    __DIR__ . '/assets/uploads/photos' => 'assets/uploads/photos',
];
foreach ($paths as $path => $label) {
    echo "$label — ";
    if (!is_dir($path)) {
        echo '<span class="fail">MISSING</span>';
    } elseif (is_writable($path)) {
        echo '<span class="ok">Writable ✓</span>';
    } else {
        echo '<span class="fail">NOT writable — chmod 755 or 777</span>';
    }
    echo '<br>';
}
?>

<h2>7. JS Test (browser-side)</h2>
<div id="jsResult" style="color:#F59E0B;">Testing...</div>
<script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
<script>
(function(){
    var el = document.getElementById('jsResult');
    if (typeof utils === 'undefined') {
        el.innerHTML = '<span style="color:#EF4444">FAIL: utils.js did not load! Check BASE_URL and file path.</span>';
        return;
    }
    el.innerHTML = '<span style="color:#10B981">utils.js loaded ✓</span><br>';

    // Test an API call
    el.innerHTML += 'Testing API call to dashboard-stats...<br>';
    utils.apiGet(utils.apiUrl('reports/dashboard-stats.php'), function(err, data) {
        if (err) {
            el.innerHTML += '<span style="color:#EF4444">API ERROR: ' + err.message + '</span><br>';
            return;
        }
        if (!data) {
            el.innerHTML += '<span style="color:#EF4444">API returned null data</span><br>';
            return;
        }
        el.innerHTML += 'HTTP ' + (data._httpStatus || '?') + ' — ';
        if (data.success) {
            el.innerHTML += '<span style="color:#10B981">API works ✓</span>';
        } else {
            el.innerHTML += '<span style="color:#EF4444">API returned error: ' + (data.message || 'unknown') + '</span>';
        }
        el.innerHTML += '<br>Raw: <pre>' + JSON.stringify(data, null, 2).substring(0, 500) + '</pre>';
    });
})();
</script>

<hr>
<p style="color:#EF4444;"><strong>⚠ DELETE this file after diagnosis!</strong></p>
</body></html>
