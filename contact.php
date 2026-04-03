<?php
// contact.php
require_once 'includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    // Simulated contact form logic
    $success = true;
}
?>

<div class="container py-5 animate-fade-in">
    <div class="text-center mb-5">
        <h1 class="fw-bold mb-3 display-4 text-white">Join the Conversation</h1>
        <p class="text-muted fs-5">Partner with us, share feedback, or seek assistance.</p>
    </div>

    <div class="row g-5">
        <!-- Contact Form Side -->
        <div class="col-lg-7">
            <div class="glass-card p-5 border-0 shadow-lg">
                <?php if ($success): ?>
                    <div class="text-center py-4">
                        <div class="p-3 bg-success bg-opacity-25 rounded-circle d-inline-flex mb-4">
                            <i class="fas fa-paper-plane text-success fs-2"></i>
                        </div>
                        <h3 class="fw-bold mb-3">Transmission Received!</h3>
                        <p class="text-muted mb-4 opacity-75">Your message has been securely sent to our support hub. We'll be in touch within 24 standard business cycles.</p>
                        <a href="index.php" class="btn btn-primary rounded-pill px-5">Back to Market</a>
                    </div>
                <?php else: ?>
                    <h4 class="fw-bold text-white mb-4">Send Us a Pulse</h4>
                    <form action="contact.php" method="POST">
                        <div class="row g-4">
                            <div class="col-md-6 text-start">
                                <label class="form-label text-muted small fw-bold">IDENTIFIER</label>
                                <input type="text" class="form-control" placeholder="Nexus-01" required>
                            </div>
                            <div class="col-md-6 text-start">
                                <label class="form-label text-muted small fw-bold">SECURE CHANNEL (EMAIL)</label>
                                <input type="email" class="form-control" placeholder="user@nexus.market" required>
                            </div>
                            <div class="col-12 text-start">
                                <label class="form-label text-muted small fw-bold">PRIMARY INQUIRY</label>
                                <input type="text" class="form-control" placeholder="Regarding Order #NX-ORD-..." required>
                            </div>
                            <div class="col-12 text-start">
                                <label class="form-label text-muted small fw-bold">DATA PACKAGE (MESSAGE)</label>
                                <textarea class="form-control" rows="5" placeholder="Share your thoughts with us..." required></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="send_message" class="btn btn-primary btn-lg w-100 rounded-pill py-3 shadow-lg">Transmit Now <i class="fas fa-chevron-right ms-2 opacity-50"></i></button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Side -->
        <div class="col-lg-5">
            <div class="glass-card p-5 border-0 h-100 shadow-lg text-start">
                <h4 class="fw-bold text-white mb-5">Command Hub</h4>
                
                <div class="d-flex align-items-start mb-5">
                    <div class="bg-white bg-opacity-10 p-3 rounded-4 me-4 border border-light border-opacity-10 text-primary">
                        <i class="fas fa-building fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white mb-1">Central Terminal</h6>
                        <p class="text-muted small mb-0 opacity-75">100 Market Way, Second-Floor Suite<br>Global Digital Hub, NY 10001</p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-5">
                    <div class="bg-white bg-opacity-10 p-3 rounded-4 me-4 border border-light border-opacity-10 text-primary">
                        <i class="fas fa-clock fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white mb-1">Operational Hours</h6>
                        <p class="text-muted small mb-0 opacity-75">Active Mon-Fri | 09:00 - 22:00 UTC<br>Emergency Response 24/7</p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-5">
                    <div class="bg-white bg-opacity-10 p-3 rounded-4 me-4 border border-light border-opacity-10 text-primary">
                        <i class="fas fa-shield-alt fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-white mb-1">Security Node</h6>
                        <p class="text-muted small mb-0 opacity-75">Secure Line: +1 (800) NEXUS-SUPPORT<br>PGP Encryption Active</p>
                    </div>
                </div>

                <div class="mt-auto py-4 text-center border-top border-light border-opacity-10">
                    <h6 class="fw-bold text-muted small mb-3">SECURE FEED ON SOCIALS</h6>
                    <div class="d-flex justify-content-center gap-4">
                        <a href="#" class="text-white opacity-50 fs-4 hover-opacity-100"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white opacity-50 fs-4 hover-opacity-100"><i class="fab fa-discord"></i></a>
                        <a href="#" class="text-white opacity-50 fs-4 hover-opacity-100"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
