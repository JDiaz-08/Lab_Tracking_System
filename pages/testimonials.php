<?php
// FILE: pages/testimonials.php
session_start();
$base = '../';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireUser();
$db  = getDB();
$uid = $_SESSION['user_id'];

$errors  = [];
$success = '';

/* Handle submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_testimonial') {
    $message = trim($_POST['message'] ?? '');
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    if (!$message) {
        $errors[] = 'Please write your testimonial message.';
    } elseif (strlen($message) > 500) {
        $errors[] = 'Testimonial must be 500 characters or less.';
    } else {
        $db->prepare("INSERT INTO testimonials (user_id, message, rating) VALUES (?, ?, ?)")
           ->execute([$uid, $message, $rating]);
        $success = 'Your testimonial has been submitted for review. Thank you!';
    }
}

/* Fetch student's testimonials */
$myTestimonials = $db->prepare("
    SELECT * FROM testimonials WHERE user_id = ? ORDER BY created_at DESC
");
$myTestimonials->execute([$uid]);
$myTestimonials = $myTestimonials->fetchAll();

$pageTitle = 'Testimonials';
require_once __DIR__ . '/../includes/header.php';
echo '<link rel="stylesheet" href="' . $base . 'assets/css/user.css?v=' . filemtime(__DIR__ . '/../assets/css/user.css') . '">';
?>
<style>
.test-page-wrap { max-width: 780px; margin: 0 auto; }
.test-page-title {
  font-family: var(--font-display);
  font-size: 1.55rem; font-weight: 800; color: var(--navy);
  text-align: center; margin-bottom: 1.5rem;
}

.test-card {
  background: #fff; border: 1px solid #e2e8f0;
  border-radius: 14px; box-shadow: 0 1px 4px rgba(15,40,84,0.06);
  overflow: hidden; margin-bottom: 1.25rem;
}
.test-card-head {
  display: flex; align-items: center; gap: 9px;
  padding: 0.95rem 1.25rem; border-bottom: 1px solid #f1f5f9;
}
.test-card-ico {
  width: 28px; height: 28px; border-radius: 7px;
  background: rgba(15,40,84,0.05);
  display: flex; align-items: center; justify-content: center;
  color: var(--navy); font-size: 0.82rem; flex-shrink: 0;
}
.test-card-title { font-family: var(--font-display); font-size: 0.92rem; font-weight: 800; color: var(--navy); }
.test-card-body { padding: 1.1rem 1.25rem; }

/* Stars */
.test-stars-label { font-size: 0.80rem; font-weight: 700; color: #1e293b; margin-bottom: 0.45rem; display: block; }
.test-stars-row { display: flex; gap: 5px; margin-bottom: 1rem; }
.test-star {
  font-size: 1.9rem; cursor: pointer;
  color: #e2e8f0; transition: color 0.12s, transform 0.1s;
  user-select: none; line-height: 1;
}
.test-star.lit { color: #d97706; }
.test-star:hover { transform: scale(1.15); }

/* Textarea */
.test-field-lbl { display: block; font-size: 0.80rem; font-weight: 700; color: #1e293b; margin-bottom: 5px; }
.test-field-lbl .req { color: #dc2626; }
.test-textarea {
  width: 100%; padding: 0.65rem 0.875rem;
  border: 1.5px solid #e2e8f0; border-radius: 8px;
  font-family: var(--font-body, 'Outfit', sans-serif);
  font-size: 0.875rem; resize: vertical; min-height: 105px;
  outline: none; color: #1e293b; transition: border-color 0.18s;
}
.test-textarea:focus { border-color: #4988C4; box-shadow: 0 0 0 3px rgba(73,136,196,0.10); }
.test-char-hint { font-size: 0.68rem; color: #94a3b8; text-align: right; margin-top: 3px; }

/* Submit */
.test-submit-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 0.6rem 1.4rem; border: none; border-radius: 8px;
  background: var(--navy); color: #fff; font-family: inherit;
  font-size: 0.82rem; font-weight: 700; cursor: pointer;
  transition: all 0.15s; margin-top: 0.75rem;
}
.test-submit-btn:hover { background: var(--blue); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(15,40,84,0.18); }

/* Testimonial list */
.test-list { display: flex; flex-direction: column; gap: 0.75rem; }
.test-item {
  padding: 0.875rem 1rem; background: #f8fafc;
  border-radius: 9px; border-left: 3px solid var(--mid);
}
.test-item-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.3rem; }
.test-item-stars { color: #d97706; font-size: 0.82rem; letter-spacing: 1px; }
.test-item-stars .off { color: #e2e8f0; }
.test-item-status {
  font-size: 0.65rem; font-weight: 700; padding: 2px 8px;
  border-radius: 100px; letter-spacing: 0.3px; text-transform: uppercase;
}
.test-status-pending  { background: rgba(217,119,6,0.10); color: #92400e; }
.test-status-approved { background: rgba(22,163,74,0.10); color: #15803d; }
.test-status-rejected { background: rgba(220,38,38,0.08); color: #991b1b; }
.test-item-msg { font-size: 0.82rem; color: #475569; line-height: 1.65; font-weight: 300; }
.test-item-date { font-size: 0.67rem; color: #94a3b8; margin-top: 0.35rem; display: flex; align-items: center; gap: 4px; }
.test-empty { text-align: center; padding: 1.5rem; color: #94a3b8; font-size: 0.875rem; }
.test-empty i { font-size: 1.75rem; display: block; margin-bottom: 0.5rem; opacity: 0.4; }
</style>
<?php require_once __DIR__ . '/../includes/user-navbar.php'; ?>

<div class="user-page">
  <div class="user-page-inner">
    <div class="test-page-wrap">

      <h1 class="test-page-title">Testimonials</h1>

      <?php if ($errors): ?>
        <div class="flash-msg flash-error" style="margin-bottom:1.1rem;">
          <i class="bi bi-exclamation-circle-fill"></i>
          <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="flash-msg flash-success" style="margin-bottom:1.1rem;">
          <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <!-- Submit testimonial -->
      <div class="test-card">
        <div class="test-card-head">
          <div class="test-card-ico"><i class="bi bi-chat-heart"></i></div>
          <span class="test-card-title">Share Your Experience</span>
        </div>
        <div class="test-card-body">
          <form method="POST" action="">
            <input type="hidden" name="action" value="add_testimonial">
            <input type="hidden" name="rating" id="testRatingInput" value="5">

            <span class="test-stars-label">Your Rating</span>
            <div class="test-stars-row" id="testStarRow">
              <span class="test-star lit" data-val="1">★</span>
              <span class="test-star lit" data-val="2">★</span>
              <span class="test-star lit" data-val="3">★</span>
              <span class="test-star lit" data-val="4">★</span>
              <span class="test-star lit" data-val="5">★</span>
            </div>

            <label class="test-field-lbl" for="testMessage">
              Your Testimonial <span class="req">*</span>
            </label>
            <textarea class="test-textarea" name="message" id="testMessage"
                      maxlength="500"
                      placeholder="Share your experience with the CCS Computer Labs — facilities, environment, staff, or anything you'd like others to know..."
                      required></textarea>
            <div class="test-char-hint"><span id="testCharCount">0</span> / 500</div>

            <button type="submit" class="test-submit-btn">
              <i class="bi bi-send"></i> Submit Testimonial
            </button>
          </form>
        </div>
      </div>

      <!-- My Testimonials -->
      <div class="test-card">
        <div class="test-card-head">
          <div class="test-card-ico"><i class="bi bi-chat-square-text"></i></div>
          <span class="test-card-title">My Testimonials</span>
        </div>
        <div class="test-card-body">
          <?php if (empty($myTestimonials)): ?>
            <div class="test-empty">
              <i class="bi bi-inbox"></i>
              You haven't submitted any testimonials yet.
            </div>
          <?php else: ?>
            <div class="test-list">
              <?php foreach ($myTestimonials as $t): ?>
                <div class="test-item">
                  <div class="test-item-top">
                    <span class="test-item-stars">
                      <?= str_repeat('★', (int)$t['rating']) ?><?php if ((int)$t['rating'] < 5): ?><span class="off"><?= str_repeat('★', 5 - (int)$t['rating']) ?></span><?php endif; ?>
                    </span>
                    <span class="test-item-status test-status-<?= $t['status'] ?>">
                      <?= ucfirst($t['status']) ?>
                    </span>
                  </div>
                  <div class="test-item-msg"><?= nl2br(htmlspecialchars($t['message'])) ?></div>
                  <div class="test-item-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date('F j, Y · g:i A', strtotime($t['created_at'])) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
/* Star rating */
let testRating = 5;
const testStars = document.querySelectorAll('.test-star');

testStars.forEach(star => {
  star.addEventListener('click', () => {
    testRating = parseInt(star.dataset.val);
    document.getElementById('testRatingInput').value = testRating;
    renderTestStars(testRating);
  });
  star.addEventListener('mouseover', () => renderTestStars(parseInt(star.dataset.val)));
  star.addEventListener('mouseout',  () => renderTestStars(testRating));
});

function renderTestStars(r) {
  testStars.forEach(s => s.classList.toggle('lit', parseInt(s.dataset.val) <= r));
}

document.getElementById('testMessage')?.addEventListener('input', function() {
  document.getElementById('testCharCount').textContent = this.value.length;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
