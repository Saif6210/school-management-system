<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// Prepare feedback table
$conn->query("CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT 'Anonymous',
    teaching_rating INT NOT NULL DEFAULT 0,
    environment_rating INT NOT NULL DEFAULT 0,
    overall_rating INT NOT NULL DEFAULT 0,
    comments TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $name = !empty($_POST['name']) ? $conn->real_escape_string($_POST['name']) : 'Anonymous';
    $teaching = (int)$_POST['teaching_rating'];
    $environment = (int)$_POST['environment_rating'];
    $overall = (int)$_POST['overall_rating'];
    $comments = $conn->real_escape_string($_POST['comments']);

    $sql = "INSERT INTO feedback (name, teaching_rating, environment_rating, overall_rating, comments) 
            VALUES ('$name', $teaching, $environment, $overall, '$comments')";
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success fs-5 text-center mt-4 shadow-sm border-0'><i class='fas fa-check-circle me-2'></i>Thank you for your valuable feedback!</div>";
    }
    else {
        $msg = "<div class='alert alert-danger fs-5 text-center mt-4 border-0'><i class='fas fa-exclamation-triangle me-2'></i>Error submitting feedback. Please try again later.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Feedback - AlMadina</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
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
            font-size: 2.5rem;
            padding: 0;
            cursor: pointer;
            transition: color 0.2s;
            margin: 0 4px;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type=radio]:checked ~ label {
            color: #ffc107;
        }
        
        .feedback-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        
        .feedback-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem;
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
                        <i class="fas fa-school fs-1 mb-3 text-warning"></i>
                        <h2 class="fw-bold mb-1">AlMadina Public Model School</h2>
                        <p class="mb-0 opacity-75 fs-5 pb-2">We value your feedback to improve our school environment.</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <?php if ($msg): ?>
                            <?php echo $msg; ?>
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-primary px-4 fw-bold rounded-pill">Return to Home</a>
                            </div>
                        <?php
else: ?>
                            
                            <form method="POST" action="submit_feedback.php">
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark fs-5">Your Name (Optional)</label>
                                    <input type="text" name="name" class="form-control form-control-lg bg-light" placeholder="Leave blank to remain anonymous">
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-12">
                                        <div class="p-4 bg-light rounded-4 text-center border">
                                            <h5 class="fw-bold mb-2">Teaching Quality</h5>
                                            <p class="text-muted small mb-1">How would you rate the teaching standards?</p>
                                            <div class="star-rating">
                                                <input type="radio" id="t5" name="teaching_rating" value="5" required /><label for="t5" title="5 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="t4" name="teaching_rating" value="4" /><label for="t4" title="4 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="t3" name="teaching_rating" value="3" /><label for="t3" title="3 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="t2" name="teaching_rating" value="2" /><label for="t2" title="2 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="t1" name="teaching_rating" value="1" /><label for="t1" title="1 star"><i class="fas fa-star"></i></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <div class="p-4 bg-light rounded-4 text-center border">
                                            <h5 class="fw-bold mb-2">School Environment</h5>
                                            <p class="text-muted small mb-1">How is the discipline, cleanliness, and atmosphere?</p>
                                            <div class="star-rating">
                                                <input type="radio" id="e5" name="environment_rating" value="5" required /><label for="e5" title="5 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="e4" name="environment_rating" value="4" /><label for="e4" title="4 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="e3" name="environment_rating" value="3" /><label for="e3" title="3 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="e2" name="environment_rating" value="2" /><label for="e2" title="2 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="e1" name="environment_rating" value="1" /><label for="e1" title="1 star"><i class="fas fa-star"></i></label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12">
                                        <div class="p-4 bg-light rounded-4 text-center border">
                                            <h5 class="fw-bold mb-2">Overall Experience</h5>
                                            <p class="text-muted small mb-1">How would you rate your overall experience?</p>
                                            <div class="star-rating">
                                                <input type="radio" id="o5" name="overall_rating" value="5" required /><label for="o5" title="5 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="o4" name="overall_rating" value="4" /><label for="o4" title="4 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="o3" name="overall_rating" value="3" /><label for="o3" title="3 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="o2" name="overall_rating" value="2" /><label for="o2" title="2 stars"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="o1" name="overall_rating" value="1" /><label for="o1" title="1 star"><i class="fas fa-star"></i></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold text-dark fs-5">Additional Comments / Suggestions</label>
                                    <textarea name="comments" class="form-control bg-light" rows="5" placeholder="Tell us what you loved or what we can improve..." required></textarea>
                                </div>
                                
                                <button type="submit" name="submit_feedback" class="btn btn-primary btn-lg w-100 fw-bold rounded-pill py-3 shadow-sm">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Feedback
                                </button>
                            </form>
                            
                        <?php
endif; ?>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-muted text-decoration-none fw-semibold"><i class="fas fa-arrow-left me-1"></i> Back to School Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
