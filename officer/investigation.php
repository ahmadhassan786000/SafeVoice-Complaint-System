<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);
$id = (int)($_GET['id'] ?? 0);

if (!$id) {   // ---- list view ----
    $rows = db_all($conn, "SELECT c.id, c.complaint_code, c.status, cat.category_name, d.student_name, i.findings, i.updated_at
        FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id JOIN complaint_student_details d ON d.complaint_id = c.id LEFT JOIN investigations i ON i.complaint_id = c.id
        WHERE c.assigned_officer_id = ? ORDER BY c.id DESC LIMIT 300", 'i', [$me['id']]);
    $page_title = 'Investigation Management'; $active = 'investigation';
    include ROOT_PATH . '/includes/panel-header.php'; ?>
    <section class="sv-investigation sv-investigation-list">
      <header class="sv-investigation-list-heading">
        <div>
          <h1 class="sv-page-title mb-1">Investigation Management</h1>
          <p class="text-muted mb-0">Review assigned complaints and continue casework.</p>
        </div>
      </header>
      <div class="sv-card sv-investigation-table-card">
        <div class="sv-investigation-table-heading">
          <h2 class="h5 mb-0">Assigned complaints</h2>
        </div>
        <div class="table-responsive">
          <table class="table sv-table sv-investigation-table mb-0">
            <thead><tr><th>Complaint</th><th>Student</th><th>Category</th><th>Status</th><th>Finding</th><th>Last update</th><th><span class="visually-hidden">Action</span></th></tr></thead>
            <tbody>
              <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5">No assigned complaints.</td></tr><?php endif; ?>
              <?php foreach ($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['complaint_code']) ?></td><td><?= e($r['student_name']) ?></td><td><?= e($r['category_name']) ?></td><td><?= status_badge($r['status']) ?></td><td><?= e($r['findings'] ?: '—') ?></td><td><?= e(fmt_date($r['updated_at'])) ?></td><td><a class="btn btn-sm btn-sv" href="?id=<?= (int)$r['id'] ?>">Open</a></td></tr><?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php include ROOT_PATH . '/includes/panel-footer.php'; exit;
}

// ---- workspace ----
$c = staff_get_complaint($conn, $id, $me);
if (!$c) { http_response_code(403); flash_set('danger', 'Complaint not found or not assigned to you.'); redirect('officer/investigation.php'); }
$inv = db_one($conn, "SELECT * FROM investigations WHERE complaint_id = ?", 'i', [$id]);
if (!$inv) {
    db_insert($conn, "INSERT INTO investigations (complaint_id, officer_user_id) VALUES (?,?)", 'ii', [$id, $me['id']]);
    $inv = db_one($conn, "SELECT * FROM investigations WHERE complaint_id = ?", 'i', [$id]);
}
$statements = db_all($conn, "SELECT statement_details, created_at FROM statements WHERE investigation_id = ? ORDER BY id DESC", 'i', [$inv['id']]);
$evidence = db_all($conn, "SELECT id, file_name FROM complaint_evidence WHERE complaint_id = ? ORDER BY id", 'i', [$id]);

$page_title = 'Investigation ' . $c['complaint_code']; $active = 'investigation';
include ROOT_PATH . '/includes/panel-header.php';
$ret = 'officer/investigation.php?id=' . $id;
?>
<div class="sv-investigation">
  <header class="sv-investigation-casehead">
    <div class="sv-investigation-case-context">
      <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
        <h1 class="sv-page-title sv-investigation-title mb-0">Investigation <span aria-hidden="true">/</span> <?= e($c['complaint_code']) ?></h1>
        <?= status_badge($c['status']) ?>
      </div>
      <p class="sv-investigation-meta mb-0"><?= e($c['category_name']) ?><span aria-hidden="true"> · </span><?= e($c['student_name']) ?> <span class="text-muted">(<?= e($c['student_code']) ?>)</span></p>
    </div>
    <nav class="sv-investigation-actions" aria-label="Complaint actions">
      <a class="btn btn-outline-sv" href="<?= url('officer/complaint-details.php?id=' . $id) ?>">Complaint details</a>
      <a class="btn btn-outline-sv" href="<?= url('officer/messages.php?c=' . $id) ?>">Messages</a>
    </nav>
  </header>

  <div class="row g-3 g-xl-4">
    <div class="col-xl-8">
      <form method="post" action="<?= url('actions/save-investigation.php') ?>" class="sv-card sv-investigation-panel mb-3 mb-xl-4"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $id ?>">
        <div class="sv-investigation-panel-heading">
          <div>
            <p class="sv-investigation-eyebrow mb-1">Case record</p>
            <h2 class="h5 mb-0">Investigation record</h2>
          </div>
        </div>
        <div class="sv-investigation-panel-body">
          <div class="mb-3"><label class="form-label" for="investigation-summary">Investigation summary</label><textarea id="investigation-summary" class="form-control" name="investigation_summary" rows="3" maxlength="10000"><?= e($inv['investigation_summary']) ?></textarea></div>
          <div class="sv-investigation-internal mb-3">
            <label class="form-label" for="investigation-notes">Investigation notes / evidence review</label>
            <textarea id="investigation-notes" class="form-control" name="investigation_notes" rows="4" maxlength="10000"><?= e($inv['investigation_notes']) ?></textarea>
            <div class="form-text">Internal only — these notes are never shown to the student.</div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="investigation-findings">Investigation finding</label>
            <select id="investigation-findings" class="form-select" name="findings"><option value="">Not decided</option><?php foreach (['Substantiated', 'Not Substantiated', 'Inconclusive', 'Referred'] as $f): ?><option <?= $inv['findings'] === $f ? 'selected' : '' ?>><?= e($f) ?></option><?php endforeach; ?></select>
            <div class="form-text">Findings are stored separately from the complaint status.</div>
          </div>
          <div class="mb-4"><label class="form-label" for="investigation-report">Final investigation report</label><textarea id="investigation-report" class="form-control" name="final_report" rows="6" maxlength="10000"><?= e($inv['final_report']) ?></textarea></div>
          <div class="sv-investigation-form-footer">
            <span class="form-text">Save to update this case record.</span>
            <button class="btn btn-sv">Save Investigation</button>
          </div>
        </div>
      </form>

      <section class="sv-card sv-investigation-panel">
        <div class="sv-investigation-panel-heading">
          <div>
            <p class="sv-investigation-eyebrow mb-1">Recorded accounts</p>
            <h2 class="h5 mb-0">Statements <span class="sv-investigation-count"><?= count($statements) ?></span></h2>
          </div>
        </div>
        <div class="sv-investigation-panel-body">
          <?php if ($statements): ?>
            <div class="sv-investigation-statements mb-4">
              <?php foreach ($statements as $s): ?><article class="sv-investigation-statement"><div class="small text-muted mb-2"><?= e(fmt_date($s['created_at'])) ?></div><div class="sv-pre"><?= e($s['statement_details']) ?></div></article><?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="sv-investigation-empty mb-4"><strong>No statements recorded</strong><span>Add a statement below to keep accounts together with the investigation record.</span></div>
          <?php endif; ?>
          <form method="post" action="<?= url('actions/add-statement.php') ?>" class="sv-investigation-statement-form"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $id ?>">
            <label class="form-label" for="statement-details">Add a statement</label>
            <textarea id="statement-details" class="form-control mb-2" name="statement_details" rows="3" maxlength="5000" placeholder="Record who made the statement, when, and what was said…" required></textarea>
            <button class="btn btn-sv btn-sm">Add Statement</button>
          </form>
        </div>
      </section>
    </div>
    <aside class="col-xl-4">
      <form method="post" action="<?= url('actions/update-status.php') ?>" class="sv-card sv-investigation-panel mb-3 mb-xl-4"><?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($ret) ?>">
        <div class="sv-investigation-panel-heading">
          <div>
            <p class="sv-investigation-eyebrow mb-1">Case workflow</p>
            <h2 class="h5 mb-0">Update status</h2>
          </div>
        </div>
        <div class="sv-investigation-panel-body">
          <label class="form-label" for="investigation-status">Complaint status</label>
          <select id="investigation-status" class="form-select mb-3" name="status"><?php foreach (['Under Review', 'Under Investigation', 'Resolved', 'Closed'] as $s): ?><option <?= $c['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
          <label class="form-label" for="status-internal-notes">Internal note <span class="text-muted fw-normal">(optional)</span></label>
          <textarea id="status-internal-notes" class="form-control mb-3" name="internal_notes" rows="3" maxlength="2000" placeholder="Add a note for the case record"></textarea>
          <button class="btn btn-sv w-100">Update Status</button>
          <div class="form-text mt-2">The student is notified of the change. Internal notes are hidden from the student.</div>
        </div>
      </form>
      <section class="sv-card sv-investigation-panel">
        <div class="sv-investigation-panel-heading">
          <div>
            <p class="sv-investigation-eyebrow mb-1">Case materials</p>
            <h2 class="h5 mb-0">Evidence <span class="sv-investigation-count"><?= count($evidence) ?></span></h2>
          </div>
        </div>
        <ul class="list-group list-group-flush sv-investigation-evidence">
          <?php foreach ($evidence as $ev): ?><li class="list-group-item"><a target="_blank" href="<?= url('actions/evidence-file.php?id=' . (int)$ev['id']) ?>"><?= e($ev['file_name']) ?></a></li><?php endforeach; ?>
          <?php if (!$evidence): ?><li class="list-group-item text-muted">No evidence attached.</li><?php endif; ?>
        </ul>
      </section>
    </aside>
  </div>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
