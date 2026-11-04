<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);
$o = (int)$me['id'];

$byStatus = db_all($conn, "SELECT status AS label, COUNT(*) AS n FROM complaints WHERE assigned_officer_id = ? GROUP BY status", 'i', [$o]);
$byCat = db_all($conn, "SELECT cat.category_name AS label, COUNT(*) AS n FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id WHERE c.assigned_officer_id = ? GROUP BY cat.id ORDER BY n DESC", 'i', [$o]);
$byMonth = db_all($conn, "SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, COUNT(*) AS n FROM complaints WHERE assigned_officer_id = ? GROUP BY label ORDER BY label DESC LIMIT 12", 'i', [$o]);
$byFinding = db_all($conn, "SELECT COALESCE(i.findings, 'Not decided') AS label, COUNT(*) AS n FROM complaints c LEFT JOIN investigations i ON i.complaint_id = c.id WHERE c.assigned_officer_id = ? GROUP BY label", 'i', [$o]);
$byMonth = array_reverse($byMonth);

$page_title = 'Reports'; $active = 'reports';
$page_scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>
function mk(id,type,rows){new Chart(document.getElementById(id),{type:type,data:{labels:rows.map(r=>r.label),datasets:[{data:rows.map(r=>+r.n),backgroundColor:["#1f8a4c","#0b3d2e","#6fbf8f","#f0ad4e","#5a32a3","#d9534f","#14573f","#9bd1b0"]}]},options:{maintainAspectRatio:false,plugins:{legend:{display:type!=="bar"}}}});}
mk("cStatus","doughnut",' . json_encode($byStatus) . ');mk("cCat","bar",' . json_encode($byCat) . ');mk("cMonth","bar",' . json_encode($byMonth) . ');mk("cFind","doughnut",' . json_encode($byFinding) . ');</script>';
include ROOT_PATH . '/includes/panel-header.php';
?>
<h1 class="sv-page-title">Reports</h1><p class="text-muted">Statistics for your assigned complaints only.</p>
<div class="row g-4">
  <div class="col-lg-6"><div class="sv-card p-3"><h2 class="h6">Complaints by status</h2><div class="chart-box"><canvas id="cStatus"></canvas></div></div></div>
  <div class="col-lg-6"><div class="sv-card p-3"><h2 class="h6">Complaints by category</h2><div class="chart-box"><canvas id="cCat"></canvas></div></div></div>
  <div class="col-lg-6"><div class="sv-card p-3"><h2 class="h6">Monthly complaints</h2><div class="chart-box"><canvas id="cMonth"></canvas></div></div></div>
  <div class="col-lg-6"><div class="sv-card p-3"><h2 class="h6">Investigation findings</h2><div class="chart-box"><canvas id="cFind"></canvas></div></div></div>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
