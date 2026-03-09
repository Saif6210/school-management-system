<?php
require_once 'includes/header.php';
require_once 'db.php';

// Prepare feedback table in case Admin visits before anyone submits
$conn->query("CREATE TABLE IF NOT EXISTS project_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT 'Anonymous',
    rating INT NOT NULL DEFAULT 0,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";
// Handle Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_feedback'])) {
    $id = (int)$_POST['id'];
    if ($conn->query("DELETE FROM project_feedback WHERE id=$id")) {
        $msg = "<div class='alert alert-success fs-6'><i class='fas fa-check-circle me-2'></i>Feedback deleted permanently.</div>";
    } else {
        $msg = "<div class='alert alert-danger fs-6'><i class='fas fa-exclamation-triangle me-2'></i>Error deleting feedback.</div>";
    }
}

// Fetch Feedbacks
$result = $conn->query("SELECT * FROM project_feedback ORDER BY id DESC");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-laptop-code me-2" style="color: #FF416C;"></i>Developer Feedback</h3>
        <p class="text-muted mb-0">Review feedback specifically regarding the software developed by <strong>Saif Munawar</strong>.</p>
    </div>
    <a href="project_feedback.php" target="_blank" class="btn fw-bold shadow-sm rounded-pill px-4" style="background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); color: white; border: none;">
        <i class="fas fa-external-link-alt me-2"></i>Open Public Form
    </a>
</div>

<?php if($msg) echo $msg; ?>

<div class="row g-4">
    <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative overflow-hidden">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: rgba(255, 65, 108, 0.1);">
                                <i class="fas fa-user fs-5" style="color: #FF416C;"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($row['name']); ?></h6>
                                <small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 text-center">
                        <h6 class="fw-bold text-muted mb-2 text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Project Rating</h6>
                        <span class="text-warning fs-3">
                            <?php for($i=1; $i<=5; $i++) echo ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </span>
                    </div>
                    
                    <div class="bg-light p-3 rounded-3 mt-auto mb-3">
                        <p class="fst-italic text-dark mb-0 fs-6"><i class="fas fa-quote-left text-muted opacity-50 me-2"></i><?php echo nl2br(htmlspecialchars($row['comments'])); ?></p>
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this developer feedback? This cannot be undone.');">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="delete_feedback" class="btn btn-outline-danger w-100 rounded-pill fw-bold"><i class="fas fa-trash-alt me-2"></i>Delete Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endwhile; else: ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                <i class="fas fa-code fs-1 text-muted mb-3 opacity-50"></i>
                <h4 class="fw-bold text-dark text-muted">No Project Feedback Yet</h4>
                <p class="text-muted">When users submit feedback regarding your software development, it will appear here.</p>
                <div class="mt-3">
                    <a href="project_feedback.php" target="_blank" class="btn text-white fw-bold rounded-pill px-4" style="background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);">View the Public Form</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
</style>

<?php require_once 'includes/footer.php'; ?>
