<?php
require_once 'includes/header.php';
require_once 'db.php';

// Prepare feedback table in case Admin visits before anyone submits
$conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT 'Anonymous',
    teaching_rating INT NOT NULL DEFAULT 0,
    environment_rating INT NOT NULL DEFAULT 0,
    overall_rating INT NOT NULL DEFAULT 0,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Fetch Feedbacks
$result = $conn->query("SELECT * FROM feedback ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-heart me-2 text-danger"></i>School Feedback Review</h3>
        <p class="text-muted mb-0">Review ratings and comments submitted by parents and students.</p>
    </div>
    <a href="submit_feedback.php" target="_blank" class="btn btn-outline-primary fw-bold shadow-sm rounded-pill px-4">
        <i class="fas fa-external-link-alt me-2"></i>Open Public Form
    </a>
</div>

<div class="row g-4">
    <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary-soft rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                <i class="fas fa-user text-primary fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($row['name']); ?></h6>
                                <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="fw-semibold text-muted">Teaching Standard</small>
                            <span class="text-warning">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $row['teaching_rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="fw-semibold text-muted">School Environment</small>
                            <span class="text-warning">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $row['environment_rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="fw-bold text-dark">Overall Experience</small>
                            <span class="text-warning fs-5">
                                <?php for($i=1; $i<=5; $i++) echo ($i <= $row['overall_rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-light p-3 rounded-3 mt-3">
                        <p class="fst-italic text-dark mb-0 fs-6"><i class="fas fa-quote-left text-muted opacity-50 me-2"></i><?php echo nl2br(htmlspecialchars($row['comments'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                <i class="fas fa-comment-slash fs-1 text-muted mb-3 opacity-50"></i>
                <h4 class="fw-bold text-dark text-muted">No Feedback Yet</h4>
                <p class="text-muted">When students or parents submit feedback from the public form, it will appear here.</p>
                <div class="mt-3">
                    <a href="submit_feedback.php" target="_blank" class="btn btn-primary fw-bold rounded-pill px-4">See Public Form Here</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
</style>

<?php require_once 'includes/footer.php'; ?>
