<?php
require_once __DIR__ . "/../config/app.php";
require_once ROOT_PATH . "/includes/functions.php";
require_once ROOT_PATH . "/includes/csrf.php";
require_once ROOT_PATH . "/middleware/auth.php";
require_once ROOT_PATH . "/middleware/role-check.php";
$me = require_role(ROLE_ADMIN);

$byCat = db_all($conn, "SELECT cat.category_name AS label, COUNT(c.id) AS n FROM complaint_categories cat LEFT JOIN complaints c ON c.category_id=cat.id GROUP BY cat.id ORDER BY n DESC");
$bySchool = db_all($conn, "SELECT d.school_name AS label, COUNT(*) AS n FROM complaint_student_details d GROUP BY d.school_name ORDER BY n DESC LIMIT 10");
$byStatus = db_all($conn, "SELECT status AS label, COUNT(*) AS n FROM complaints GROUP BY status");
$byMonth = array_reverse(db_all($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') AS label, COUNT(*) AS n FROM complaints GROUP BY label ORDER BY label DESC LIMIT 12"));
$trend = array_reverse(db_all($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') AS label, COUNT(*) AS n FROM complaint_status_history WHERE new_status = 'Resolved' AND old_status <> new_status GROUP BY label ORDER BY label DESC LIMIT 12"));
$rp = db_one($conn, "SELECT SUM(status IN ('Resolved','Closed')) AS done, SUM(status NOT IN ('Resolved','Closed')) AS pending FROM complaints");
$rvp = [['label' => 'Resolved / Closed', 'n' => (int)$rp['done']], ['label' => 'Pending / Open', 'n' => (int)$rp['pending']]];
$load = db_all($conn, "SELECT u.full_name, u.account_status, COUNT(c.id) AS total, SUM(c.status NOT IN ('Resolved','Closed')) AS open_n, SUM(c.status IN ('Resolved','Closed')) AS done
    FROM users u JOIN roles r ON r.id=u.role_id AND r.role_code='INVESTIGATOR' LEFT JOIN complaints c ON c.assigned_officer_id=u.id GROUP BY u.id ORDER BY open_n DESC");
$loadChart = array_map(function ($r) { return ['label' => $r['full_name'], 'n' => (int)$r['open_n']]; }, $load);
$page_title = 'Reports & Analytics'; $active = 'reports';
$js = [['rCat', 'bar', $byCat], ['rSchool', 'bar', $bySchool], ['rStatus', 'doughnut', $byStatus], ['rMonth', 'line', $byMonth], ['rRvp', 'doughnut', $rvp], ['rTrend', 'line', $trend], ['rLoad', 'bar', $loadChart]];
$page_scripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><script>';
foreach ($js as $j) $page_scripts .= 'svChart("' . $j[0] . '","' . $j[1] . '",' . json_encode($j[2]) . ');';
$page_scripts .= '</script>';
include ROOT_PATH . '/includes/panel-header.php';
$boxes = [['rCat', 'Complaints by category'], ['rSchool', 'Complaints by school (top 10)'], ['rStatus', 'Complaints by status'], ['rMonth', 'Monthly complaints'], ['rRvp', 'Resolved versus pending'], ['rTrend', 'Complaint resolution trend (resolved per month)'], ['rLoad', 'Investigator workload (open)']];
?>
<h1 class="sv-page-title">Reports &amp; Analytics</h1>
<div class="row g-4"><?php foreach ($boxes as $b): ?><div class="col-lg-6"><div class="sv-card p-3"><h2 class="h6"><?= e($b[1]) ?></h2><div class="chart-box"><canvas id="<?= $b[0] ?>"></canvas></div></div></div><?php endforeach; ?>
<div class="col-lg-6"><div class="sv-card"><div class="card-header-sv">Investigator workload table</div><div class="table-responsive"><table class="table sv-table mb-0"><thead><tr><th>Investigator</th><th>Total</th><th>Open</th><th>Done</th></tr></thead><tbody><?php foreach ($load as $l): ?><tr><td><?= e($l['full_name']) ?> <?= $l['account_status'] !== 'active' ? '<span class="badge bg-secondary">' . e($l['account_status']) . '</span>' : '' ?></td><td><?= (int)$l['total'] ?></td><td><?= (int)$l['open_n'] ?></td><td><?= (int)$l['done'] ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
