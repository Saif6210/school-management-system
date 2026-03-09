<?php
if(session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'db.php';

// Prepare project feedback table
$conn->query("CREATE TABLE IF NOT EXISTS project_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT 'Anonymous',
    rating INT NOT NULL DEFAULT 0,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_project_feedback'])) {
    $name = !empty($_POST['name']) ? $conn->real_escape_string($_POST['name']) : 'Anonymous';
    $rating = (int)$_POST['rating'];
    $comments = $conn->real_escape_string($_POST['comments']);

    $sql = "INSERT INTO project_feedback (name, rating, comments) 
            VALUES ('$name', $rating, '$comments')";
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success fs-5 text-center mt-4 shadow-sm border-0'><i class='fas fa-check-circle me-2'></i>Thank you! Your feedback for Saif Munawar's project has been submitted.</div>";
    } else {
        $msg = "<div class='alert alert-danger fs-5 text-center mt-4 border-0'><i class='fas fa-exclamation-triangle me-2'></i>Error submitting feedback. Please try again later.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Feedback - Saif Munawar</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1f1c2c 0%, #928DAB 100%);
            min-height: 100vh;
        }
        .star-rating {
            direction: rtl;
            display: inline-block;
        }
        .star-rating input[type=radio] {
            display: none;
        }
        .star-rating label {
            color: #ccc;
            font-size: 3rem;
            padding: 0;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
            margin: 0 5px;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type=radio]:checked ~ label {
            color: #ffc107;
            transform: scale(1.1);
        }
        
        .feedback-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            border: none;
            overflow: hidden;
        }
        
        .feedback-header {
            background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
            padding: 3.5rem 2rem;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card feedback-card">
                    <div class="feedback-header">
                        <i class="fas fa-laptop-code fs-1 mb-3 text-light"></i>
                        <h2 class="fw-bold mb-1">Developer Feedback</h2>
                        <h4 class="mb-3 opacity-75">Project by <strong>Saif Munawar</strong></h4>
                        <p class="mb-0 opacity-75 fs-5 pb-2">How did I do? Let me know your thoughts on this software!</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <?php if($msg): ?>
                            <?php echo $msg; ?>
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-outline-dark px-4 fw-bold rounded-pill">Return to Home</a>
                            </div>
                        <?php else: ?>
                            
                            <form method="POST" action="project_feedback.php">
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark fs-5">Your Name (Optional)</label>
                                    <input type="text" name="name" class="form-control form-control-lg bg-light" placeholder="Leave blank to remain anonymous">
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-12">
                                        <div class="p-4 bg-light rounded-4 text-center border">
                                            <h5 class="fw-bold mb-2">Project Rating</h5>
                                            <p class="text-muted small mb-2">How would you rate this School Management System overall?</p>
                                            <div class="star-rating">
                                                <input type="radio" id="r5" name="rating" value="5" required /><label for="r5" title="5 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="r4" name="rating" value="4" /><label for="r4" title="4 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="r3" name="rating" value="3" /><label for="r3" title="3 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="r2" name="rating" value="2" /><label for="r2" title="2 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="r1" name="rating" value="1" /><label for="r1" title="1 star"><i class="fas fa-star"></i></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark fs-5">Comments for Saif Munawar</label>
                                    <textarea name="comments" class="form-control bg-light" rows="5" placeholder="Share your experience, bugs found, or feature requests..." required></textarea>
                                </div>
                                
                                <button type="submit" name="submit_project_feedback" class="btn btn-danger btn-lg w-100 fw-bold rounded-pill py-3 shadow-sm" style="background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%); border: none;">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Project Feedback
                                </button>
                            </form>
                            
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white text-decoration-none fw-semibold"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
