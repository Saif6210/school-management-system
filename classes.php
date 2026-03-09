<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Ensure `timetable` table can handle "Rest Time" easily
$check_break = $conn->query("SHOW COLUMNS FROM timetable LIKE 'is_break'");
if($check_break->num_rows == 0){
    $conn->query("ALTER TABLE timetable ADD COLUMN is_break TINYINT(1) DEFAULT 0 AFTER teacher_id");
    $conn->query("ALTER TABLE timetable ADD COLUMN break_title VARCHAR(100) NULL AFTER is_break");
}

// 1. ADD CLASS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_class'])) {
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $conn->query("INSERT INTO classes (class_name) VALUES ('$class_name')");
    $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Class added successfully.</div>";
}

// 2. EDIT CLASS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_class'])) {
    $c_id = (int)$_POST['edit_class_id'];
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $conn->query("UPDATE classes SET class_name='$class_name' WHERE id=$c_id");
    $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Class updated successfully.</div>";
}

// 3. DELETE CLASS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_class'])) {
    $c_id = (int)$_POST['delete_class_id'];
    $conn->query("DELETE FROM classes WHERE id=$c_id");
    // Also delete associated timetable entries
    $conn->query("DELETE FROM timetable WHERE class_id=$c_id");
    $msg = "<div class='alert alert-success'><i class='fas fa-trash me-2'></i>Class deleted successfully.</div>";
}

// 4. ADD TIMETABLE ENTRY
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_timetable'])) {
    $c_id = (int)$_POST['class_id'];
    $day = $conn->real_escape_string($_POST['day_of_week']);
    $start = $conn->real_escape_string($_POST['start_time']);
    $end = $conn->real_escape_string($_POST['end_time']);
    $entry_type = $_POST['entry_type']; // 'class' or 'break'
    
    if($entry_type == 'break') {
        $break_title = $conn->real_escape_string($_POST['break_title']);
        $sql = "INSERT INTO timetable (class_id, day_of_week, start_time, end_time, is_break, break_title) 
                VALUES ($c_id, '$day', '$start', '$end', 1, '$break_title')";
    } else {
        $subject = $conn->real_escape_string($_POST['subject_name']);
        $teacher_id = (int)$_POST['teacher_id'];
        
        // Ensure subject exists or add it
        $check_sub = $conn->query("SELECT id FROM subjects WHERE subject_name='$subject' AND class_id=$c_id");
        if($check_sub->num_rows > 0){
            $sub_id = $check_sub->fetch_assoc()['id'];
        } else {
            $conn->query("INSERT INTO subjects (subject_name, class_id, teacher_id) VALUES ('$subject', $c_id, $teacher_id)");
            $sub_id = $conn->insert_id;
        }
        
        $sql = "INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, is_break) 
                VALUES ($c_id, $sub_id, $teacher_id, '$day', '$start', '$end', 0)";
    }
    
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Timetable entry added successfully.</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

// 5. DELETE TIMETABLE ENTRY
if (isset($_GET['delete_tt'])) {
    $tt_id = (int)$_GET['delete_tt'];
    $c_id = (int)$_GET['class_id'];
    $conn->query("DELETE FROM timetable WHERE id=$tt_id");
    echo "<script>window.location.href='classes.php?action=timetable&class_id=$c_id&msg=deleted';</script>";
    exit;
}
if(isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $msg = "<div class='alert alert-success'><i class='fas fa-trash me-2'></i>Timetable entry deleted successfully.</div>";
}
?>

<?php if ($action == 'list'): 
    // Fetch Classes
    $result = $conn->query("SELECT * FROM classes ORDER BY class_name ASC");
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-chalkboard me-2 text-primary"></i>Class Management</h3>
        <p class="text-muted mb-0">Manage classes, sections, and assigned subjects.</p>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addClassModal">
        <i class="fas fa-plus me-2"></i>Add Class
    </button>
</div>

<?php if($msg) echo $msg; ?>

<div class="row g-4">
    <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
        $c_id = $row['id'];
        
        // Fetch subjects for this class to show a preview
        $subs = [];
        $sub_q = $conn->query("
            SELECT s.subject_name, t.name as teacher_name 
            FROM subjects s 
            LEFT JOIN teachers t ON s.teacher_id = t.id 
            WHERE s.class_id=$c_id LIMIT 3
        ");
        if($sub_q) while($sq = $sub_q->fetch_assoc()) $subs[] = $sq;
    ?>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['class_name']); ?></h4>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item fw-semibold text-primary" href="#" data-bs-toggle="modal" data-bs-target="#editClassModal<?php echo $c_id; ?>"><i class="fas fa-edit me-2"></i>Edit Class</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-semibold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteClassModal<?php echo $c_id; ?>"><i class="fas fa-trash me-2"></i>Delete</a></li>
                        </ul>
                    </div>
                </div>
                
                <h6 class="text-muted fw-semibold mb-3 fs-7 text-uppercase" style="letter-spacing: 1px;">Subjects Preview</h6>
                <?php if(count($subs) > 0): ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach($subs as $sub): ?>
                        <li class="mb-2 d-flex align-items-center bg-light p-2 rounded-3">
                            <div class="bg-white p-2 rounded-circle shadow-sm me-3"><i class="fas fa-book text-primary fs-6"></i></div>
                            <span class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                            <span class="ms-auto text-muted small fw-semibold"><?php echo htmlspecialchars($sub['teacher_name'] ?? 'Unassigned'); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i>No subjects added yet. Add them via Timetable.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-primary-soft border-0 py-3 text-center rounded-bottom-4">
                <a href="classes.php?action=timetable&class_id=<?php echo $c_id; ?>" class="text-decoration-none fw-bold text-primary w-100 d-block">Manage Timetable <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal<?php echo $c_id; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-light border-0 pt-4 pb-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="edit_class_id" value="<?php echo $c_id; ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">Class Name</label>
                        <input type="text" name="class_name" class="form-control form-control-lg bg-light" value="<?php echo htmlspecialchars($row['class_name']); ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_class" class="btn btn-primary fw-bold px-4 shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Class Modal -->
    <div class="modal fade" id="deleteClassModal<?php echo $c_id; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow rounded-4">
                <div class="modal-header bg-danger text-white border-0 pt-4 pb-3 rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Class</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <input type="hidden" name="delete_class_id" value="<?php echo $c_id; ?>">
                    <i class="fas fa-trash-alt text-danger fs-1 mb-3"></i>
                    <h4 class="fw-bold mb-2">Are you sure?</h4>
                    <p class="text-muted">You are about to delete <strong><?php echo htmlspecialchars($row['class_name']); ?></strong>. This will also remove all its timetable entries. This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-0 pb-4 justify-content-center">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_class" class="btn btn-danger fw-bold px-4 shadow-sm">Yes, Delete Class</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php endwhile; else: ?>
    <div class="col-12 text-center py-5">
        <div class="bg-white p-5 rounded-4 shadow-sm mx-auto" style="max-width: 500px;">
            <i class="fas fa-chalkboard fs-1 text-light mb-4 d-block" style="font-size: 80px !important;"></i>
            <h4 class="fw-bold text-dark">No Classes Configured</h4>
            <p class="text-muted mb-4">You have not set up any academic classes in the school yet.</p>
            <button class="btn btn-primary px-4 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal"><i class="fas fa-plus me-2"></i>Create New Class</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Class</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Class Name</label>
                    <input type="text" name="class_name" class="form-control form-control-lg bg-light" placeholder="e.g. Class 6" required>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_class" class="btn btn-primary fw-bold px-4 shadow-sm">Save Class</button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action == 'timetable' && $class_id > 0): 
    $c_data = $conn->query("SELECT * FROM classes WHERE id=$class_id")->fetch_assoc();
    $teachers_q = $conn->query("SELECT * FROM teachers ORDER BY name ASC");
    $teachers = [];
    if($teachers_q) while($t = $teachers_q->fetch_assoc()) $teachers[] = $t;
    
    // Fetch Timetable
    $tt_q = $conn->query("
        SELECT tt.*, s.subject_name, t.name as teacher_name 
        FROM timetable tt 
        LEFT JOIN subjects s ON tt.subject_id = s.id 
        LEFT JOIN teachers t ON tt.teacher_id = t.id 
        WHERE tt.class_id = $class_id 
        ORDER BY FIELD(tt.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), tt.start_time ASC
    ");
    $schedule = [];
    if($tt_q) {
        while($tr = $tt_q->fetch_assoc()) {
            $schedule[$tr['day_of_week']][] = $tr;
        }
    }
    
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-calendar-alt me-2 text-primary"></i>Timetable: <?php echo htmlspecialchars($c_data['class_name']); ?></h3>
        <p class="text-muted mb-0">Manage periods, assignments, and rest times for this class.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <button class="btn btn-warning fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addBreakModal">
            <i class="fas fa-coffee me-2"></i>Add Rest Time
        </button>
        <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addPeriodModal">
            <i class="fas fa-plus me-2"></i>Add Subject Period
        </button>
        <a href="classes.php" class="btn btn-outline-secondary fw-bold px-4 py-2 rounded-3">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

<?php if($msg) echo $msg; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        
        <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill" id="timetableTabs">
            <?php $active = true; foreach($days as $day): ?>
            <li class="nav-item">
                <a class="nav-link fw-bold rounded-pill <?php echo $active ? 'active shadow-sm' : ''; ?>" data-bs-toggle="tab" href="#day-<?php echo $day; ?>"><?php echo $day; ?></a>
            </li>
            <?php $active = false; endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php $active = true; foreach($days as $day): ?>
            <div class="tab-pane fade <?php echo $active ? 'show active' : ''; ?>" id="day-<?php echo $day; ?>">
                
                <?php if(empty($schedule[$day])): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fs-1 text-light mb-3"></i>
                        <h5 class="fw-bold text-muted">No schedule for <?php echo $day; ?></h5>
                        <p class="text-muted">Click the buttons above to add periods or rest times.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Time</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3">Subject / Details</th>
                                    <th class="py-3">Assigned Teacher</th>
                                    <th class="py-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($schedule[$day] as $entry): ?>
                                <tr class="<?php echo $entry['is_break'] ? 'bg-warning-soft' : ''; ?>">
                                    <td class="py-3 px-4 fw-bold text-dark">
                                        <i class="far fa-clock text-primary me-2"></i>
                                        <?php echo date('h:i A', strtotime($entry['start_time'])) . ' - ' . date('h:i A', strtotime($entry['end_time'])); ?>
                                    </td>
                                    
                                    <?php if($entry['is_break']): ?>
                                        <td class="py-3"><span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-coffee me-1"></i> Rest / Break</span></td>
                                        <td class="py-3 fw-bold text-dark"><?php echo htmlspecialchars($entry['break_title']); ?></td>
                                        <td class="py-3 text-muted">--</td>
                                    <?php else: ?>
                                        <td class="py-3"><span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill"><i class="fas fa-book me-1"></i> Academic</span></td>
                                        <td class="py-3 fw-bold text-dark"><?php echo htmlspecialchars($entry['subject_name']); ?></td>
                                        <td class="py-3"><i class="fas fa-user-tie text-muted me-2"></i><?php echo htmlspecialchars($entry['teacher_name'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    
                                    <td class="py-3 text-end">
                                        <a href="classes.php?action=timetable&class_id=<?php echo $class_id; ?>&delete_tt=<?php echo $entry['id']; ?>" class="btn btn-sm btn-outline-danger shadow-sm" onclick="return confirm('Delete this period?');"><i class="fas fa-times"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
            </div>
            <?php $active = false; endforeach; ?>
        </div>

    </div>
</div>

<!-- Add Subject Period Modal -->
<div class="modal fade" id="addPeriodModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-book-open me-2"></i>Add Subject Period</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="entry_type" value="class">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Day of Week</label>
                        <select class="form-select bg-light" name="day_of_week" required>
                            <?php foreach($days as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="time" class="form-control bg-light" name="start_time" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End Time</label>
                        <input type="time" class="form-control bg-light" name="end_time" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Subject Name</label>
                        <input type="text" class="form-control bg-light" name="subject_name" placeholder="e.g. Mathematics" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Assign Teacher</label>
                        <select class="form-select bg-light" name="teacher_id" required>
                            <option value="">Select Teacher...</option>
                            <?php foreach($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']) . " (" . htmlspecialchars($t['subject']) . ")"; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_timetable" class="btn btn-primary fw-bold px-4 shadow-sm">Save Period</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Break Rest Time Modal -->
<div class="modal fade" id="addBreakModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning text-dark border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-coffee me-2"></i>Add Rest / Break Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                <input type="hidden" name="entry_type" value="break">
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Day of Week</label>
                        <select class="form-select bg-light" name="day_of_week" required>
                            <?php foreach($days as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="time" class="form-control bg-light" name="start_time" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">End Time</label>
                        <input type="time" class="form-control bg-light" name="end_time" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Break Title</label>
                        <input type="text" class="form-control bg-light" name="break_title" placeholder="e.g. Lunch Break, Assembly" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_timetable" class="btn btn-warning fw-bold px-4 shadow-sm text-dark">Save Break</button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<style>
.transition-hover { transition: transform 0.2s, box-shadow 0.2s; }
.transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
.fs-7 { font-size: 0.8rem; }
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.nav-pills .nav-link { color: #495057; padding: 0.75rem 1rem; }
.nav-pills .nav-link.active { background-color: #0d6efd; color: white; }
</style>

<?php require_once 'includes/footer.php'; ?>
