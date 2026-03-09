<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Mark Student Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_student_attendance'])) {
    $date = $conn->real_escape_string($_POST['att_date']);
    $class_id = (int)$_POST['class_id'];
    
    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
        foreach ($_POST['attendance'] as $student_id => $status) {
            $student_id = (int)$student_id;
            $status = $conn->real_escape_string($status);
            
            // Check if already marked for this date
            $check = $conn->query("SELECT id FROM attendance WHERE user_id=$student_id AND user_type='student' AND date='$date'");
            if ($check->num_rows > 0) {
                $conn->query("UPDATE attendance SET status='$status' WHERE user_id=$student_id AND user_type='student' AND date='$date'");
            } else {
                $conn->query("INSERT INTO attendance (user_id, user_type, date, status) VALUES ($student_id, 'student', '$date', '$status')");
            }
        }
        $msg = "<div class='alert alert-success fs-6'><i class='fas fa-check-circle me-2'></i>Student attendance marked successfully for $date!</div>";
    }
}

// 2. Mark Staff Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_staff_attendance'])) {
    $date = $conn->real_escape_string($_POST['att_date']);
    
    if (isset($_POST['attendance']) && is_array($_POST['attendance'])) {
        foreach ($_POST['attendance'] as $teacher_id => $status) {
            $teacher_id = (int)$teacher_id;
            $status = $conn->real_escape_string($status);
            
            // Check if already marked for this date
            $check = $conn->query("SELECT id FROM attendance WHERE user_id=$teacher_id AND user_type='teacher' AND date='$date'");
            if ($check->num_rows > 0) {
                $conn->query("UPDATE attendance SET status='$status' WHERE user_id=$teacher_id AND user_type='teacher' AND date='$date'");
            } else {
                $conn->query("INSERT INTO attendance (user_id, user_type, date, status) VALUES ($teacher_id, 'teacher', '$date', '$status')");
            }
        }
        $msg = "<div class='alert alert-success fs-6'><i class='fas fa-check-circle me-2'></i>Staff attendance marked successfully for $date!</div>";
    }
}

// Fetch Classes 1 to 5 ONLY
$classes_query = $conn->query("SELECT * FROM classes WHERE class_name IN ('Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5') ORDER BY class_name ASC");
$classes = [];
while($row = $classes_query->fetch_assoc()) $classes[] = $row;
if(empty($classes)) {
    // Attempt to manually create if they don't exist
    $default_classes = ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5"];
    foreach ($default_classes as $dc) {
        $chk = $conn->query("SELECT id FROM classes WHERE class_name='$dc'");
        if($chk->num_rows == 0) $conn->query("INSERT INTO classes (class_name) VALUES ('$dc')");
    }
    // reload
    $classes_query = $conn->query("SELECT * FROM classes WHERE class_name IN ('Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5') ORDER BY class_name ASC");
    while($row = $classes_query->fetch_assoc()) $classes[] = $row;
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-calendar-check me-2 text-primary"></i>Attendance Management</h3>
        <p class="text-muted mb-0">Mark daily attendance for students and teaching staff.</p>
    </div>
    <?php if ($action != ''): ?>
    <a href="attendance.php" class="btn btn-outline-secondary fw-bold shadow-sm px-4">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
    <?php endif; ?>
</div>

<?php if($msg != "") echo $msg; ?>

<?php if ($action == 'student'): 
    $class_id = (int)$_GET['class_id'];
    $date = $conn->real_escape_string($_GET['date']);
    
    $cname = "";
    foreach($classes as $c) { if($c['id'] == $class_id) $cname = $c['class_name']; }
    
    // Auto-Generate Exactly 25 Students if they don't exist for this class
    $stud_count_q = $conn->query("SELECT COUNT(*) as cnt FROM students WHERE class_id=$class_id");
    $stud_count = $stud_count_q->fetch_assoc()['cnt'];
    
    if ($stud_count < 25) {
        $needed = 25 - $stud_count;
        for ($i = 0; $i < $needed; $i++) {
            $sid = "STD-" . rand(10000, 99999);
            $conn->query("INSERT INTO students (student_id, name, father_name, class_id, admission_date) VALUES ('$sid', 'Student $sid', 'Father $sid', $class_id, CURDATE())");
        }
    }
    
    // Fetch students and their attendance for today if exists
    $students = [];
    $res = $conn->query("
        SELECT s.id, s.name, s.student_id, a.status 
        FROM students s 
        LEFT JOIN attendance a ON s.id = a.user_id AND a.user_type='student' AND a.date='$date'
        WHERE s.class_id=$class_id 
        ORDER BY s.id ASC
    ");
    while($r = $res->fetch_assoc()) $students[] = $r;
?>
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-primary text-white border-0 pt-4 pb-4 rounded-top-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <h5 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Marking Attendance: <?php echo htmlspecialchars($cname); ?></h5>
        <p class="mb-0 text-white-50"><i class="fas fa-calendar-alt me-1"></i>Date: <?php echo date('d M, Y', strtotime($date)); ?></p>
    </div>
    <div class="card-body p-4 p-md-5">
        <form method="POST" action="attendance.php">
            <input type="hidden" name="att_date" value="<?php echo htmlspecialchars($date); ?>">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            
            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3">Roll No</th>
                            <th class="py-3">Student Name</th>
                            <th class="py-3 text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach($students as $st): 
                            $status = $st['status'] ? $st['status'] : 'Present'; // Default is Present
                        ?>
                        <tr>
                            <td class="py-3 px-4 fw-bold text-muted"><?php echo $sn++; ?></td>
                            <td class="py-3 text-secondary"><?php echo htmlspecialchars($st['student_id']); ?></td>
                            <td class="py-3 fw-semibold text-dark"><?php echo htmlspecialchars($st['name']); ?></td>
                            <td class="py-3 text-center">
                                <div class="btn-group shadow-sm flex-wrap w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="pres_<?php echo $st['id']; ?>" value="Present" <?php if($status=='Present') echo 'checked'; ?>>
                                    <label class="btn btn-outline-success fw-bold px-3 py-2 flex-grow-1" for="pres_<?php echo $st['id']; ?>">Present</label>

                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="abs_<?php echo $st['id']; ?>" value="Absent" <?php if($status=='Absent') echo 'checked'; ?>>
                                    <label class="btn btn-outline-danger fw-bold px-3 py-2 flex-grow-1" for="abs_<?php echo $st['id']; ?>">Absent</label>

                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="lv_<?php echo $st['id']; ?>" value="Leave" <?php if($status=='Leave') echo 'checked'; ?>>
                                    <label class="btn btn-outline-warning fw-bold px-4 py-2 flex-grow-1" for="lv_<?php echo $st['id']; ?>">Leave</label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-end border-top pt-4">
                <button type="submit" name="save_student_attendance" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm">
                    <i class="fas fa-save me-2"></i>Save Student Attendance
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action == 'staff'): 
    $date = $conn->real_escape_string($_GET['date']);
    
    // Fetch teachers and attendance
    $staff = [];
    $res = $conn->query("
        SELECT t.id, t.name, t.teacher_id, t.subject, a.status 
        FROM teachers t 
        LEFT JOIN attendance a ON t.id = a.user_id AND a.user_type='teacher' AND a.date='$date'
        ORDER BY t.name ASC
    ");
    if($res) while($r = $res->fetch_assoc()) $staff[] = $r;
?>
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-success text-white border-0 pt-4 pb-4 rounded-top-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <h5 class="fw-bold mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Marking Staff Attendance</h5>
        <p class="mb-0 text-white-50"><i class="fas fa-calendar-alt me-1"></i>Date: <?php echo date('d M, Y', strtotime($date)); ?></p>
    </div>
    <div class="card-body p-4 p-md-5">
        <?php if(count($staff) == 0): ?>
            <div class="alert alert-warning border-0"><i class="fas fa-exclamation-triangle me-2"></i>No teaching staff found. Please hire teachers first!</div>
        <?php else: ?>
        <form method="POST" action="attendance.php">
            <input type="hidden" name="att_date" value="<?php echo htmlspecialchars($date); ?>">
            
            <div class="table-responsive">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3">Staff ID</th>
                            <th class="py-3">Name & Subject</th>
                            <th class="py-3 text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $sn = 1; foreach($staff as $st): 
                            $status = $st['status'] ? $st['status'] : 'Present'; 
                        ?>
                        <tr>
                            <td class="py-3 px-4 fw-bold text-muted"><?php echo $sn++; ?></td>
                            <td class="py-3 text-secondary"><?php echo htmlspecialchars($st['teacher_id']); ?></td>
                            <td class="py-3">
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($st['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($st['subject']); ?></small>
                            </td>
                            <td class="py-3 text-center">
                                <div class="btn-group shadow-sm flex-wrap w-100" role="group">
                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="pres_staff_<?php echo $st['id']; ?>" value="Present" <?php if($status=='Present') echo 'checked'; ?>>
                                    <label class="btn btn-outline-success fw-bold px-3 py-2 flex-grow-1" for="pres_staff_<?php echo $st['id']; ?>">Present</label>

                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="abs_staff_<?php echo $st['id']; ?>" value="Absent" <?php if($status=='Absent') echo 'checked'; ?>>
                                    <label class="btn btn-outline-danger fw-bold px-3 py-2 flex-grow-1" for="abs_staff_<?php echo $st['id']; ?>">Absent</label>

                                    <input type="radio" class="btn-check" name="attendance[<?php echo $st['id']; ?>]" id="lv_staff_<?php echo $st['id']; ?>" value="Leave" <?php if($status=='Leave') echo 'checked'; ?>>
                                    <label class="btn btn-outline-warning fw-bold px-4 py-2 flex-grow-1" for="lv_staff_<?php echo $st['id']; ?>">Leave</label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-end border-top pt-4">
                <button type="submit" name="save_staff_attendance" class="btn btn-success btn-lg fw-bold px-5 shadow-sm">
                    <i class="fas fa-save me-2"></i>Save Staff Attendance
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php else: // Dashboard view ?>
<div class="row g-4">
    <!-- Student Forms Column -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="fas fa-user-graduate me-2 text-primary"></i>Student Form</h5>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="attendance.php">
                    <input type="hidden" name="action" value="student">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Class (1 to 5 Only)</label>
                        <select class="form-select form-select-lg bg-light" name="class_id" required>
                            <option value="">Choose Class...</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control form-control-lg bg-light" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary py-3 w-100 fw-bold shadow-sm rounded-3">Load 25 Students List</button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h5 class="fw-bold text-dark"><i class="fas fa-chalkboard-teacher me-2 text-success"></i>Staff Form</h5>
            </div>
            <div class="card-body p-4">
                <form method="GET" action="attendance.php">
                    <input type="hidden" name="action" value="staff">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control form-control-lg bg-light" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-success py-3 w-100 fw-bold shadow-sm rounded-3">Load Staff List</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Summaries Column -->
    <div class="col-md-7">
        <?php 
            // 1. Calculate Today's Class Summaries
            $today = date('Y-m-d');
            $summary_sql = "
                SELECT c.class_name, a.status, COUNT(*) as cnt
                FROM attendance a
                JOIN students s ON a.user_id = s.id AND a.user_type='student'
                JOIN classes c ON s.class_id = c.id
                WHERE a.date = '$today' AND c.class_name IN ('Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5')
                GROUP BY c.class_name, a.status
                ORDER BY c.class_name ASC
            ";
            $sum_res = $conn->query($summary_sql);
            $class_summaries = [];
            while ($r = $sum_res->fetch_assoc()) {
                $class_summaries[$r['class_name']][$r['status']] = $r['cnt'];
            }
        ?>
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-3 border-bottom mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fas fa-chart-pie me-2 text-warning"></i>Today's Attendance Summary (<?php echo date('d M, Y'); ?>)</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">Student Classes Processed Today</h6>
                <?php if (empty($class_summaries)): ?>
                    <div class="alert alert-secondary border-0"><i class="fas fa-info-circle me-2"></i>No student classes marked for today.</div>
                <?php else: ?>
                    <div class="row g-3 mb-5">
                    <?php foreach ($class_summaries as $cname => $stats): 
                        $p = isset($stats['Present']) ? $stats['Present'] : 0;
                        $a = isset($stats['Absent']) ? $stats['Absent'] : 0;
                        $l = isset($stats['Leave']) ? $stats['Leave'] : 0;
                        $total = $p + $a + $l;
                        $p_pct = $total > 0 ? round(($p / $total) * 100) : 0;
                    ?>
                        <div class="col-md-6">
                            <div class="border p-3 rounded-4 bg-light shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo $cname; ?></h6>
                                    <span class="badge bg-secondary px-2 py-1">Total: <?php echo $total; ?></span>
                                </div>
                                <div class="progress mb-2 rounded-pill" style="height: 10px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $p_pct; ?>%"></div>
                                    <div class="progress-bar bg-danger" style="width: <?php echo $total>0?round(($a/$total)*100):0; ?>%"></div>
                                    <div class="progress-bar bg-warning" style="width: <?php echo $total>0?round(($l/$total)*100):0; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.85rem; font-weight: 600;">
                                    <span>P: <span class="text-success"><?php echo $p; ?></span></span>
                                    <span>A: <span class="text-danger"><?php echo $a; ?></span></span>
                                    <span>L: <span class="text-warning"><?php echo $l; ?></span></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php 
                    // 2. Staff Summary
                    $staff_p = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_type='teacher' AND date='$today' AND status='Present'")->fetch_assoc()['c'];
                    $staff_a = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_type='teacher' AND date='$today' AND status='Absent'")->fetch_assoc()['c'];
                    $staff_l = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE user_type='teacher' AND date='$today' AND status='Leave'")->fetch_assoc()['c'];
                    $staff_total = $staff_p + $staff_a + $staff_l;
                ?>
                <h6 class="fw-bold mb-3 text-success border-bottom pb-2">Staff Processed Today</h6>
                <?php if ($staff_total == 0): ?>
                    <div class="alert alert-secondary border-0"><i class="fas fa-info-circle me-2"></i>Staff attendance not marked for today.</div>
                <?php else: ?>
                    <div class="border p-4 rounded-4 bg-success-soft shadow-sm border-success border-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-dark">Staff Overview</h6>
                            <span class="badge bg-success px-3 py-2">Total Staff Marked: <?php echo $staff_total; ?></span>
                        </div>
                        <div class="progress mb-3 rounded-pill" style="height: 12px;">
                            <div class="progress-bar bg-success" style="width: <?php echo round(($staff_p/$staff_total)*100); ?>%"></div>
                            <div class="progress-bar bg-danger" style="width: <?php echo round(($staff_a/$staff_total)*100); ?>%"></div>
                            <div class="progress-bar bg-warning" style="width: <?php echo round(($staff_l/$staff_total)*100); ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted fw-bold">
                            <span><i class="fas fa-circle text-success me-1"></i>Present: <?php echo $staff_p; ?></span>
                            <span><i class="fas fa-circle text-danger me-1"></i>Absent: <?php echo $staff_a; ?></span>
                            <span><i class="fas fa-circle text-warning me-1"></i>Leave: <?php echo $staff_l; ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.bg-success-soft { background-color: rgba(25, 135, 84, 0.05); }
.btn-check:checked + .btn-outline-success { background-color: #198754; color: white; }
.btn-check:checked + .btn-outline-danger { background-color: #dc3545; color: white; }
.btn-check:checked + .btn-outline-warning { background-color: #ffc107; color: text-dark; }
</style>

<?php require_once 'includes/footer.php'; ?>
