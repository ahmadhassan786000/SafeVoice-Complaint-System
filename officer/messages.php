<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_OFFICER);

$convos = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, d.student_name, u.id AS student_uid, u.profile_image AS student_image,
    (SELECT COUNT(*) FROM complaint_messages m WHERE m.complaint_id = c.id AND m.sender_role = 'STUDENT' AND m.is_read = 0) AS unread,
    (SELECT m.message FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_msg,
    (SELECT m.sender_role FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_role,
    (SELECT m.created_at FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at
    FROM complaints c JOIN users u ON u.id = c.student_user_id JOIN complaint_student_details d ON d.complaint_id = c.id WHERE c.assigned_officer_id = ?", 'i', [$me['id']]);
usort($convos, function ($a, $b) {
    if ((int)$a['unread'] > 0 xor (int)$b['unread'] > 0) return (int)$b['unread'] > 0 ? 1 : -1;
    $ta = strtotime($a['last_at'] ?: $a['created_at']); $tb = strtotime($b['last_at'] ?: $b['created_at']);
    return $tb <=> $ta ?: ((int)$b['id'] <=> (int)$a['id']);
});
$sel = isset($_GET['c']) ? (int)$_GET['c'] : (count($convos) === 1 ? (int)$convos[0]['id'] : 0);
$c = $sel ? staff_get_complaint($conn, $sel, $me) : null;
$msgs = [];
if ($c) {
    db_exec($conn, "UPDATE complaint_messages SET is_read = 1 WHERE complaint_id = ? AND sender_role = 'STUDENT'", 'i', [$sel]);
    foreach ($convos as $index => $conversation) {
        if ((int)$conversation['id'] === $sel) { $convos[$index]['unread'] = 0; break; }
    }
    $msgs = db_all($conn, "SELECT sender_role, message, is_info_request, created_at FROM complaint_messages WHERE complaint_id = ? ORDER BY id", 'i', [$sel]);
    $student = ['id' => $c['student_uid'], 'profile_image' => $c['student_image'], 'role_code' => ROLE_STUDENT];
}
$page_title = 'Messages'; $active = 'messages';
$page_scripts = '<script src="' . url('assets/js/messages.js') . '"></script>';
include ROOT_PATH . '/includes/panel-header.php';
?>
<div class="sv-page-head">
  <div><h1 class="sv-page-title">Messages</h1>
  <p class="sv-page-sub">Reply to students on complaints assigned to you. Students see your replies as the “SafeVoice Investigation Team”.</p></div>
</div>
<div class="sv-msgs-app <?= $c ? 'chat-open' : '' ?>">
  <aside class="sv-msgs-list" aria-label="Assigned complaint conversations">
    <div class="sv-msgs-list-head">
      <div class="d-flex align-items-center justify-content-between mb-2"><h2>Conversations</h2><span class="sv-count-pill"><?= count($convos) ?></span></div>
      <div class="sv-search"><i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" class="form-control" placeholder="Search by student or complaint" aria-label="Search conversations" data-filter-list="#convoList" autocomplete="off"></div>
    </div>
    <div class="sv-msgs-scroll" id="convoList">
      <?php if (!$convos): ?><div class="sv-empty-state"><i class="bi bi-chat-square-text" aria-hidden="true"></i><strong>No assigned conversations</strong><span>Messages appear here for complaints assigned to you.</span></div><?php endif; ?>
      <?php foreach ($convos as $cv): $isSel = (int)$cv['id'] === $sel; ?>
        <a class="sv-convo sv-filter-item <?= $isSel ? 'active' : '' ?> <?= $cv['unread'] ? 'unread' : '' ?>" href="?c=<?= (int)$cv['id'] ?>" <?= $isSel ? 'aria-current="page"' : '' ?>>
          <img class="sv-convo-avatar" src="<?= e(avatar_url(['id' => $cv['student_uid'], 'profile_image' => $cv['student_image'], 'role_code' => ROLE_STUDENT])) ?>" alt="">
          <div class="sv-convo-body">
            <div class="sv-convo-top"><strong class="sv-convo-name"><?= e($cv['student_name']) ?></strong><span class="sv-convo-time"><?= e(fmt_short_time($cv['last_at'] ?: $cv['created_at'])) ?></span></div>
            <div class="sv-convo-sub"><?= e($cv['complaint_code']) ?></div>
            <div class="sv-convo-preview">
              <span class="sv-convo-text"><?php if ($cv['last_msg'] === null): ?><em>No messages yet</em><?php else: ?><?= $cv['last_role'] === 'INVESTIGATOR' ? 'You: ' : '' ?><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $cv['last_msg']), 0, 60, '…')) ?><?php endif; ?></span>
              <?php if ($cv['unread']): ?><span class="sv-unread-dot" aria-label="<?= (int)$cv['unread'] ?> unread"><?= (int)$cv['unread'] ?></span><?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
      <div class="sv-empty-state d-none" id="convoNoMatch"><i class="bi bi-search" aria-hidden="true"></i><span>No conversations match your search.</span></div>
    </div>
  </aside>
  <section class="sv-msgs-pane" aria-label="Selected conversation">
    <?php if (!$c): ?>
      <div class="sv-empty-conversation"><i class="bi bi-chat-left-dots" aria-hidden="true"></i><strong><?= $convos ? 'Select a conversation' : 'No messages to display' ?></strong><span><?= $convos ? 'Choose a student on the left to read the history and reply.' : 'Assigned complaint conversations will appear here when available.' ?></span></div>
    <?php else: ?>
      <header class="sv-pane-head">
        <a class="btn btn-sm btn-outline-sv d-lg-none sv-back" href="<?= url('officer/messages.php?list=1') ?>" aria-label="Back to conversations"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        <img class="sv-convo-avatar" src="<?= e(avatar_url($student)) ?>" alt="">
        <div class="sv-pane-title"><strong><?= e($c['student_name']) ?></strong><span><?= e($c['complaint_code']) ?></span></div>
        <div class="ms-auto d-flex align-items-center gap-2"><?= status_badge($c['status']) ?>
          <a class="btn btn-sm btn-outline-sv d-none d-sm-inline-flex" href="<?= url('officer/complaint-details.php?id=' . (int)$c['id']) ?>">View complaint</a></div>
      </header>
      <div class="sv-chat" role="log" aria-label="Messages for complaint <?= e($c['complaint_code']) ?>" aria-live="polite" aria-relevant="additions text">
        <?php if (!$msgs): ?><div class="sv-chat-empty"><i class="bi bi-chat-heart" aria-hidden="true"></i><strong>No messages yet</strong><span>Send a message or request additional information below.</span></div><?php endif; ?>
        <?php $lastDay = ''; foreach ($msgs as $m): $mine = $m['sender_role'] === 'INVESTIGATOR'; $day = date('Y-m-d', strtotime($m['created_at'])); ?>
          <?php if ($day !== $lastDay): $lastDay = $day; ?><div class="sv-day-sep"><span><?= e(fmt_day_label($m['created_at'])) ?></span></div><?php endif; ?>
          <div class="sv-msg <?= $mine ? 'mine' : '' ?>">
            <div class="sv-msg-col">
              <?php if (!$mine): ?><div class="sv-msg-who"><?= e($c['student_name']) ?></div><?php endif; ?>
              <div class="sv-msg-bubble sv-pre"><?php if ($m['is_info_request']): ?><span class="sv-info-tag"><i class="bi bi-info-circle" aria-hidden="true"></i> Information request</span><?php endif; ?><?= e($m['message']) ?></div>
              <div class="sv-msg-meta"><?= e(date('h:i A', strtotime($m['created_at']))) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <form class="sv-composer" method="post" action="<?= url('actions/send-message.php') ?>">
        <?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= (int)$c['id'] ?>">
        <label class="visually-hidden" for="svMessage">Write a message to <?= e($c['student_name']) ?></label>
        <div class="sv-composer-row">
          <textarea class="form-control" id="svMessage" name="message" rows="1" maxlength="3000" placeholder="Write to the student (they will see “SafeVoice Investigation Team”)…" required></textarea>
          <button class="btn btn-sv sv-send" type="submit" aria-label="Send message"><i class="bi bi-send-fill" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Send</span></button>
        </div>
        <div class="sv-composer-hint">
          <div class="form-check m-0"><input class="form-check-input" type="checkbox" name="is_info_request" value="1" id="infoReq"><label class="form-check-label" for="infoReq">Request additional information</label></div>
          <span id="svCount" aria-live="off">0 / 3000</span>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php include ROOT_PATH . '/includes/panel-footer.php'; ?>
