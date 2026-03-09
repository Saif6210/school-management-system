<?php
require_once 'includes/header.php';
require_once 'db.php';

// Prepare database for new features if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    event_time VARCHAR(100) NOT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL
)");

// Insert default settings if empty
$setting_check = $conn->query("SELECT count(*) as count FROM settings")->fetch_assoc();
if ($setting_check['count'] == 0) {
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('school_name', 'Almadina Public Model School')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('academic_year', '2025-2026')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_email', 'info@almadinaschool.edu')");
    $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_phone', '+92 300 1234567')");
}

// Insert default events if empty
$event_check = $conn->query("SELECT count(*) as count FROM events")->fetch_assoc();
if ($event_check['count'] == 0) {
    if (date('m') > 3) {
        $ptm_date = date('Y') . '-12-15';
        $sports_date = date('Y', strtotime('+1 year')) . '-03-04';
    } else {
        $ptm_date = date('Y') . '-03-15';
        $sports_date = date('Y') . '-04-04';
    }
    $conn->query("INSERT INTO events (title, event_date, event_time) VALUES ('Parent-Teacher Meeting', '$ptm_date', '9:00 AM - 1:00 PM')");
    $conn->query("INSERT INTO events (title, event_date, event_time) VALUES ('Annual Sports Day', '$sports_date', 'Starting 8:00 AM')");
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Event Edit
    if (isset($_POST['edit_event'])) {
        $event_id = (int)$_POST['event_id'];
        $title = $conn->real_escape_string($_POST['title']);
        $event_date = $conn->real_escape_string($_POST['event_date']);
        $event_time = $conn->real_escape_string($_POST['event_time']);
        
        if ($conn->query("UPDATE events SET title='$title', event_date='$event_date', event_time='$event_time' WHERE id=$event_id")) {
            $success_msg = "Event updated successfully!";
        } else {
            $error_msg = "Error updating event.";
        }
    }
    // Handle Admin Password Change
    elseif (isset($_POST['change_password'])) {
        $user_id = $_SESSION['user_id'] ?? 1; // Fallback to 1 if session isn't tracking ID yet
        $old_pass = $_POST['old_password'];
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $check = $conn->query("SELECT * FROM users WHERE id=$user_id");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            if (password_verify($old_pass, $row['password'])) {
                $conn->query("UPDATE users SET password='$new_pass' WHERE id=$user_id");
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Incorrect old password.";
            }
        } else {
            $error_msg = "User not found.";
        }
    }
    // Handle Add New Admin
    elseif (isset($_POST['add_admin'])) {
        $new_username = $conn->real_escape_string($_POST['new_username']);
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $check = $conn->query("SELECT * FROM users WHERE username='$new_username'");
        if ($check && $check->num_rows > 0) {
            $error_msg = "Username already exists.";
        } else {
            // Include status='active' as required by authenticate.php
            if ($conn->query("INSERT INTO users (username, password, role, status) VALUES ('$new_username', '$new_pass', 'admin', 'active')")) {
                $success_msg = "New Admin account created successfully!";
            } else {
                // Fallback in case status column doesn't exist
                if ($conn->query("INSERT INTO users (username, password, role) VALUES ('$new_username', '$new_pass', 'admin')")) {
                    $success_msg = "New Admin account created successfully!";
                } else {
                    $error_msg = "Error creating admin account.";
                }
            }
        }
    }
    // Handle Settings Update
    elseif (isset($_POST['update_settings'])) {
        $school_name = $conn->real_escape_string($_POST['school_name']);
        $academic_year = $conn->real_escape_string($_POST['academic_year']);
        $contact_email = $conn->real_escape_string($_POST['contact_email']);
        $contact_phone = $conn->real_escape_string($_POST['contact_phone']);
        
        $conn->query("UPDATE settings SET setting_value='$school_name' WHERE setting_key='school_name'");
        $conn->query("UPDATE settings SET setting_value='$academic_year' WHERE setting_key='academic_year'");
        $conn->query("UPDATE settings SET setting_value='$contact_email' WHERE setting_key='contact_email'");
        $conn->query("UPDATE settings SET setting_value='$contact_phone' WHERE setting_key='contact_phone'");
        
        $success_msg = "Settings updated successfully!";
    }
}

// Quick stats
$student_count = $conn->query("SELECT count(*) as total FROM students")->fetch_assoc()['total'];
$teacher_count = $conn->query("SELECT count(*) as total FROM teachers")->fetch_assoc()['total'];
$class_count = $conn->query("SELECT count(*) as total FROM classes")->fetch_assoc()['total'];
$pending_fees_amount = $conn->query("SELECT sum(amount) as total FROM fees WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;
// Fetch up to 3 upcoming events
$events_result = $conn->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 3");

// Fetch settings
$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
if ($settings_result) {
    while($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$school_name = $settings['school_name'] ?? 'Almadina Public Model School';
$academic_year = $settings['academic_year'] ?? '2025-2026';
$contact_email = $settings['contact_email'] ?? 'info@almadinaschool.edu';
$contact_phone = $settings['contact_phone'] ?? '+92 300 1234567';
?>

<?php if($success_msg): ?>
<div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <strong><i class="fas fa-check-circle me-2"></i>Success!</strong> <?= $success_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if($error_msg): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
    <strong><i class="fas fa-exclamation-circle me-2"></i>Error!</strong> <?= $error_msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-white border-0 shadow-lg p-4 rounded-4 position-relative overflow-hidden">
            <!-- Decorative background blobb -->
            <div class="position-absolute rounded-circle bg-primary opacity-10" style="width: 300px; height: 300px; top: -100px; right: -50px; filter: blur(40px);"></div>
            <div class="position-absolute rounded-circle bg-info opacity-10" style="width: 200px; height: 200px; bottom: -50px; right: 150px; filter: blur(30px);"></div>
            
            <div class="position-relative z-1">
                <h2 class="fw-bold text-dark">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! 👋</h2>
                <p class="text-secondary mb-0 fs-5 mt-2">Here's a quick overview of <?php echo htmlspecialchars($school_name); ?> today.</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-semibold">Total Students</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $student_count; ?></h2>
                    </div>
                    <div class="icon-box bg-primary-soft p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-graduate fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-semibold">Total Teachers</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $teacher_count; ?></h2>
                    </div>
                    <div class="icon-box bg-success-soft p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-chalkboard-teacher fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-semibold">Total Classes</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $class_count; ?></h2>
                    </div>
                    <div class="icon-box bg-warning-soft p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-school fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-semibold">Pending Fees</h6>
                        <h2 class="fw-bold mb-0 text-dark">Rs. <?php echo number_format($pending_fees_amount); ?></h2>
                    </div>
                    <div class="icon-box bg-danger bg-opacity-10 text-danger p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="fas fa-money-bill-wave fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="fas fa-bullhorn me-2 text-primary"></i>Recent Announcements</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-start" role="alert">
                    <i class="fas fa-info-circle fs-3 mt-1 me-3 text-info"></i>
                    <div>
                        <h6 class="alert-heading fw-bold">Annual Admissions Open</h6>
                        <p class="mb-0 text-dark">Admissions for the new academic year are now open. Ensure all admission forms are processed through the Admissions module.</p>
                    </div>
                </div>
                <div class="alert alert-warning border-0 shadow-sm mt-3 d-flex align-items-start" role="alert">
                    <i class="fas fa-clock fs-3 mt-1 me-3 text-warning"></i>
                    <div>
                        <h6 class="alert-heading fw-bold">Staff Meeting</h6>
                        <p class="mb-0 text-dark">A general staff meeting is scheduled for Saturday at 10:00 AM in the main hall.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark"><i class="fas fa-calendar-alt me-2 text-primary"></i>Upcoming Events</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php 
                    $color_classes = ['bg-primary', 'bg-success', 'bg-warning text-dark'];
                    $color_index = 0;
                    if ($events_result && $events_result->num_rows > 0): 
                        while($event = $events_result->fetch_assoc()): 
                            $date = new DateTime($event['event_date']);
                            $month = strtoupper($date->format('M'));
                            $day = $date->format('d');
                            $color = $color_classes[$color_index % count($color_classes)];
                            $color_index++;
                    ?>
                    <li class="list-group-item px-0 border-0 d-flex align-items-start mb-2 position-relative">
                        <div class="<?= $color ?> text-white rounded p-2 text-center me-3 shadow-sm" style="min-width: 55px;">
                            <small class="d-block fw-bold lh-1 mb-1"><?= $month ?></small>
                            <span class="fs-4 fw-bold lh-1"><?= $day ?></span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($event['title']) ?></h6>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?= htmlspecialchars($event['event_time']) ?></small>
                        </div>
                        <button class="btn btn-sm btn-light border rounded-circle shadow-sm stretched-link" data-bs-toggle="modal" data-bs-target="#editEventModal<?= $event['id'] ?>"><i class="fas fa-edit text-muted"></i></button>
                    </li>
                    
                    <!-- Edit Event Modal -->
                    <div class="modal fade" id="editEventModal<?= $event['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow rounded-4 text-start" style="z-index: 1055;">
                                <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Event</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4 p-md-5">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Event Title</label>
                                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Event Date</label>
                                        <input type="date" name="event_date" class="form-control" value="<?= $event['event_date'] ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Event Time</label>
                                        <input type="text" name="event_time" class="form-control" value="<?= htmlspecialchars($event['event_time']) ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="edit_event" class="btn btn-primary fw-bold px-4 shadow-sm">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <li class="list-group-item px-0 border-0 text-muted">No upcoming events scheduled.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom pb-3 pt-4 px-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-cogs me-2 text-primary"></i>Admin Panel Settings</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-5">
                    <div class="col-md-6 border-md-end">
                        <h6 class="fw-bold mb-4 text-dark">Change Admin Password</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Current Password</label>
                                <input type="password" name="old_password" class="form-control bg-light border-0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">New Password</label>
                                <input type="password" name="new_password" class="form-control bg-light border-0" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary fw-bold px-4 rounded-3 shadow-sm mt-2">Update Password</button>
                        </form>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="fw-bold mb-4 text-dark">Create New Admin Account</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">New Admin Username</label>
                                <input type="text" name="new_username" class="form-control bg-light border-0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted">Admin Password</label>
                                <input type="password" name="new_password" class="form-control bg-light border-0" required>
                            </div>
                            <button type="submit" name="add_admin" class="btn btn-success fw-bold px-4 rounded-3 shadow-sm mt-2">Create Account</button>
                        </form>
                    </div>

                    <div class="col-12 mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-4 text-dark">General Settings</h6>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">School Name</label>
                                    <input type="text" name="school_name" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($school_name); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Academic Year</label>
                                    <input type="text" name="academic_year" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($academic_year); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($contact_email); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold text-muted">Contact Phone</label>
                                    <input type="text" name="contact_phone" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($contact_phone); ?>" required>
                                </div>
                            </div>
                            <button type="submit" name="update_settings" class="btn btn-info text-white fw-bold px-4 rounded-3 shadow-sm mt-2">Save Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
