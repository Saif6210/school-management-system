<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Almadina Public Model School - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
        }
    </style>
</head>
<body class="d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Almadina School</h2>
                            <p class="text-muted">Management System Login</p>
                        </div>
                        <?php
                        if(isset($_SESSION['error_msg'])) {
                            echo '<div class="alert alert-danger" role="alert">'.$_SESSION['error_msg'].'</div>';
                            unset($_SESSION['error_msg']);
                        }
                        ?>
                        <form action="authenticate.php" method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">Username</label>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Login</button>
                        </form>
                        
                        <div class="mt-4 pt-3 border-top text-center">
                            <h6 class="text-muted mb-3">Have feedback about our school?</h6>
                            <a href="submit_feedback.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold mb-2 w-100">
                                <i class="fas fa-star text-warning me-2"></i>Give School Feedback
                            </a>
                            <a href="project_feedback.php" class="btn rounded-pill px-4 fw-bold text-white w-100" style="background: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);">
                                <i class="fas fa-laptop-code me-2"></i>Rate the Software Developer
                            </a>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">Use Setup script first. Default: admin / admin</small><br>
                            <small><a href="setup.php">Run Database Setup</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
