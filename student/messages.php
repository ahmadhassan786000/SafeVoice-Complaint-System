<?php
require_once __DIR__ . '/../config/app.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/csrf.php';
require_once ROOT_PATH . '/middleware/auth.php';
require_once ROOT_PATH . '/middleware/role-check.php';
$me = require_role(ROLE_STUDENT);

// Conversations = the student's own complaints, with last-message preview and unread count.
// Student-facing queries never select the real investigator id or name.
$convos = db_all($conn, "SELECT c.id, c.complaint_code, c.status, c.created_at, cat.category_name,
    (SELECT COUNT(*) FROM complaint_messages m WHERE m.complaint_id = c.id AND m.sender_role = 'INVESTIGATOR' AND m.is_read = 0) AS unread,
    (SELECT m.message FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_msg,
    (SELECT m.sender_role FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_role,
    (SELECT m.created_at FROM complaint_messages m WHERE m.complaint_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_at
    FROM complaints c JOIN complaint_categories cat ON cat.id = c.category_id WHERE c.student_user_id = ?", 'i', [$me['id']]);
usort($convos, function ($a, $b) {
    $ta = strtotime($a['last_at'] ?: $a['created_at']); $tb = strtotime($b['last_at'] ?: $b['created_at']);
    return $tb <=> $ta ?: ((int)$b['id'] <=> (int)$a['id']);
});
$sel = isset($_GET['c']) ? (int)$_GET['c'] : ((count($convos) === 1 && !isset($_GET['list'])) ? (int)$convos[0]['id'] : 0);
$current = $sel ? student_get_complaint($conn, $sel, $me['id']) : null;
$msgs = [];
if ($current) {
    db_exec($conn, "UPDATE complaint_messages SET is_read = 1 WHERE complaint_id = ? AND sender_role = 'INVESTIGATOR'", 'i', [$sel]);
    foreach ($convos as $index => $conversation) {
        if ((int)$conversation['id'] === $sel) { $convos[$index]['unread'] = 0; break; }
    }
    // Student-facing query: sender_role only - the real sender id is never selected.
    $msgs = db_all($conn, "SELECT sender_role, message, is_info_request, created_at FROM complaint_messages WHERE complaint_id = ? ORDER BY id", 'i', [$sel]);
}
// Send results (actions/send-message.php): errors stay visible until fixed; success is a small inline confirmation.
$msg_errors = []; $msg_sent = false; $keep = [];
foreach (flash_get() as $f) {
    if ($f['type'] === 'danger') $msg_errors[] = $f['message'];
    elseif ($f['type'] === 'success' && $f['message'] === 'Message sent.') $msg_sent = true;
    else $keep[] = $f;
}
foreach ($keep as $f) flash_set($f['type'], $f['message']);
$total_unread = 0; foreach ($convos as $cv) { $total_unread += (int)$cv['unread']; }
$page_title = 'Messages'; $active = 'messages';
$page_scripts = '<script src="' . url('assets/js/messages.js') . '"></script>';
include ROOT_PATH . '/includes/student-header.php';
?>
<div class="sv-page-head">
  <div><h1 class="sv-page-title">Messages</h1>
  <p class="sv-page-sub">Follow up on your complaints. Replies come from the SafeVoice Investigation Team, so your investigator's identity stays private.</p></div>
</div>
<?php if ($msg_errors && !$current): ?><div class="alert alert-danger" role="alert"><?= e(implode(' ', $msg_errors)) ?></div><?php endif; ?>
<div class="sv-msgs-app <?= $current ? 'chat-open' : '' ?>">
  <aside class="sv-msgs-list" aria-label="Your conversations">
    <div class="sv-msgs-list-head">
      <div class="d-flex align-items-center justify-content-between mb-2"><h2>Conversations</h2><span class="sv-count-pill"><?= count($convos) ?></span></div>
      <div class="sv-search"><i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" class="form-control" placeholder="Search conversations" aria-label="Search conversations" data-filter-list="#convoList" autocomplete="off"></div>
    </div>
    <div class="sv-msgs-scroll" id="convoList">
      <?php if (!$convos): ?>
        <div class="sv-empty-state"><i class="bi bi-chat-square-text" aria-hidden="true"></i><strong>No conversations yet</strong><span>Messages are available after you submit a complaint.</span><a class="btn btn-sv btn-sm mt-2" href="<?= url('student/submit-complaint.php') ?>">Submit a complaint</a></div>
      <?php endif; ?>
      <?php foreach ($convos as $cv): $isSel = (int)$cv['id'] === $sel; ?>
        <a class="sv-convo sv-filter-item <?= $isSel ? 'active' : '' ?> <?= $cv['unread'] ? 'unread' : '' ?>" href="?c=<?= (int)$cv['id'] ?>" <?= $isSel ? 'aria-current="page"' : '' ?>>
          <img class="sv-convo-avatar" src="<?= url('assets/images/safevoice-logo.png') ?>" alt="">
          <div class="sv-convo-body">
            <div class="sv-convo-top"><strong class="sv-convo-name"><?= e($cv['complaint_code']) ?></strong><span class="sv-convo-time"><?= e(fmt_short_time($cv['last_at'] ?: $cv['created_at'])) ?></span></div>
            <div class="sv-convo-sub"><?= e($cv['category_name']) ?></div>
            <div class="sv-convo-preview">
              <span class="sv-convo-text"><?php if ($cv['last_msg'] === null): ?><em>No messages yet</em><?php else: ?><?= $cv['last_role'] === 'STUDENT' ? 'You: ' : '' ?><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $cv['last_msg']), 0, 60, '…')) ?><?php endif; ?></span>
              <?php if ($cv['unread']): ?><span class="sv-unread-dot" aria-label="<?= (int)$cv['unread'] ?> unread"><?= (int)$cv['unread'] ?></span><?php endif; ?>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
      <div class="sv-empty-state d-none" id="convoNoMatch"><i class="bi bi-search" aria-hidden="true"></i><span>No conversations match your search.</span></div>
    </div>
  </aside>
  <section class="sv-msgs-pane" aria-label="Selected conversation">
    <?php if (!$current): ?>
      <div class="sv-empty-conversation"><i class="bi bi-chat-left-dots" aria-hidden="true"></i><strong><?= $convos ? 'Select a conversation' : 'Your messages will appear here' ?></strong><span><?= $convos ? 'Choose a complaint on the left to read the history and reply.' : 'When you submit a complaint, you can follow up with the SafeVoice Investigation Team here.' ?></span></div>
    <?php else: ?>
      <header class="sv-pane-head">
        <a class="btn btn-sm btn-outline-sv d-lg-none sv-back" href="<?= url('student/messages.php?list=1') ?>" aria-label="Back to conversations"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
        <img class="sv-convo-avatar" src="<?= url('assets/images/safevoice-logo.png') ?>" alt="">
        <div class="sv-pane-title"><strong>SafeVoice Investigation Team</strong><span><?= e($current['complaint_code']) ?> · <?= e($current['category_name']) ?></span></div>
        <div class="ms-auto d-flex align-items-center gap-2"><?= status_badge($current['status']) ?>
          <a class="btn btn-sm btn-outline-sv d-none d-sm-inline-flex" href="<?= url('student/complaint-details.php?id=' . (int)$current['id']) ?>">View complaint</a></div>
      </header>
      <div class="sv-chat" role="log" aria-label="Messages for complaint <?= e($current['complaint_code']) ?>" aria-live="polite" aria-relevant="additions text">
        <?php if (!$msgs): ?><div class="sv-chat-empty"><i class="bi bi-chat-heart" aria-hidden="true"></i><strong>No messages yet</strong><span>You can write to the SafeVoice Investigation Team below.</span></div><?php endif; ?>
        <?php $lastDay = ''; foreach ($msgs as $m): $mine = $m['sender_role'] === 'STUDENT'; $day = date('Y-m-d', strtotime($m['created_at'])); ?>
          <?php if ($day !== $lastDay): $lastDay = $day; ?><div class="sv-day-sep"><span><?= e(fmt_day_label($m['created_at'])) ?></span></div><?php endif; ?>
          <div class="sv-msg <?= $mine ? 'mine' : '' ?>">
            <?php if (!$mine): ?><img class="sv-msg-avatar" src="<?= url('assets/images/safevoice-logo.png') ?>" alt="" aria-hidden="true"><?php endif; ?>
            <div class="sv-msg-col">
              <?php if (!$mine): ?><div class="sv-msg-who">SafeVoice Investigation Team</div><?php endif; ?>
              <div class="sv-msg-bubble sv-pre"><?php if ($m['is_info_request']): ?><span class="sv-info-tag"><i class="bi bi-info-circle" aria-hidden="true"></i> Information requested</span><?php endif; ?><?= e($m['message']) ?></div>
              <div class="sv-msg-meta"><time datetime="<?= e(date('c', strtotime($m['created_at']))) ?>" title="<?= e(fmt_date($m['created_at'])) ?>"><?= e(date('h:i A', strtotime($m['created_at']))) ?></time></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($msg_errors): ?><div class="sv-state sv-state-error" role="alert"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i><span><?= e(implode(' ', $msg_errors)) ?></span></div><?php endif; ?>
      <?php if ($msg_sent): ?><div class="sv-state sv-state-ok" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>Message sent. The SafeVoice Investigation Team will reply here.</span></div><?php endif; ?>
      <form class="sv-composer" id="svComposer" method="post" action="<?= url('actions/send-message.php') ?>">
        <?= csrf_field() ?><input type="hidden" name="complaint_id" value="<?= (int)$current['id'] ?>">
        <label class="visually-hidden" for="svMessage">Write a message to the SafeVoice Investigation Team</label>
        <div class="sv-composer-row">
          <textarea class="form-control" id="svMessage" name="message" rows="1" maxlength="3000" placeholder="Write a message…" required></textarea>
          <button class="btn btn-sv sv-send" id="svSend" type="submit" aria-label="Send message"><i class="bi bi-send-fill" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1">Send</span></button>
        </div>
        <div class="sv-composer-hint"><span>Press Enter to send, Shift+Enter for a new line. Please do not share passwords.</span><span id="svCount" aria-live="off">0 / 3000</span></div>
      </form>
    <?php endif; ?>
  </section>
</div>
<?php include ROOT_PATH . '/includes/student-footer.php'; ?>
