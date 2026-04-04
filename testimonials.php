<?php
// testimonials.php
require_once 'includes/header.php';

$error = "";
$success = "";

// --- Handle Review Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $error = "Access Restricted: Please login to verify your identity before submitting a testimonial.";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = (int)$_POST['rating'];
        $content = $_POST['content'];

        if ($rating < 1 || $rating > 5) {
            $error = "Validation Error: Rating must be between 1 and 5 stars.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO testimonials (user_id, rating, content) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $rating, $content]);
                $success = "Gratitude Synchronized: Your review has been added to our global archives.";
            } catch (PDOException $e) {
                $error = "Transmission Failure: Could not broadcast your review.";
            }
        }
    }
}

// --- Fetch All Testimonials ---
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.name, u.profile_pic 
        FROM testimonials t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    $reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Global Archive Access Error: " . $e->getMessage());
}
?>

<div class="container py-5 animate-fade-in text-start">
    <div class="row mb-5 align-items-center">
        <div class="col-lg-8">
            <h1 class="fw-bold mb-0">Platform Testimonials</h1>
            <p class="text-muted fs-5">Authentic feedback from our elite buyer and seller community.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn btn-primary rounded-pill px-5 py-3 shadow-lg fw-bold" data-bs-toggle="modal" data-bs-target="#reviewModal">Submit Your Review</button>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light rounded-pill px-5 py-3 fw-bold">Login to Rate Us</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success border-0 rounded-4 shadow-sm mb-5 p-4"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger border-0 rounded-4 shadow-sm mb-5 p-4"><?php echo $error; ?></div><?php endif; ?>

    <!-- Reviews Grid -->
    <div class="row g-4">
        <?php foreach ($reviews as $r): ?>
            <div class="col-md-6 col-xl-4 flex-grow-1">
                <div class="glass-card p-5 border-0 h-100 shadow-lg d-flex flex-column">
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?php echo htmlspecialchars($r['profile_pic'] ?: 'assets/img/default_user.png'); ?>" class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                        <div>
                            <h6 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($r['name']); ?></h6>
                            <span class="text-muted small"><?php echo date("M d, Y", strtotime($r['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-4 text-warning">
                        <?php for ($i=1; $i<=5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= $r['rating'] ? '' : 'fa-star-o opacity-25'); ?>"></i>
                        <?php endfor; ?>
                    </div>

                    <p class="text-muted italic flex-grow-1">"<?php echo htmlspecialchars($r['content']); ?>"</p>
                    
                    <div class="mt-4 border-top border-light border-opacity-10 pt-4">
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small">Verified User</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($reviews) == 0): ?>
            <div class="col-12 py-5 text-center opacity-50 italic">
                <i class="fas fa-quote-left fs-1 mb-3"></i>
                <p class="fs-5">The Nexus global archive is currently empty. Be the first to share your experience!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Add Review -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card border-0 shadow-lg p-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-white mb-0">Acquisition Feedback</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-4">
                <form action="testimonials.php" method="POST">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">RATING (STARS)</label>
                        <select name="rating" class="form-control bg-dark text-white border-primary border-opacity-25" required>
                            <option value="5">★★★★★ - Immaculate Experience</option>
                            <option value="4">★★★★ - Highly Productive</option>
                            <option value="3">★★★ - Regular Experience</option>
                            <option value="2">★★ - Minor Disruptions</option>
                            <option value="1">★ - Needs Calibration</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">BROADCAST CONTENT (REVIEW)</label>
                        <textarea name="content" class="form-control bg-dark text-white border-primary border-opacity-25" rows="5" placeholder="Describe your synchronized journey with Nexus..." required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-lg">SYNCHRONIZE REVIEW</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
