<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Enable safe fetching of students for specific class exam
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// 1. ADD EXAM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_exam'])) {
    if (!isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
        $msg = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Error: Please add subjects to your classes in the Timetable section before scheduling an exam!</div>";
    } else {
        $exam_name = $conn->real_escape_string($_POST['exam_name']);
        $subject_id = (int)$_POST['subject_id'];
        $exam_date = $conn->real_escape_string($_POST['exam_date']);
        $total_marks = (int)$_POST['total_marks'];
        $passing_marks = (int)$_POST['passing_marks'];
        
        $sql = "INSERT INTO exams (exam_name, subject_id, exam_date, total_marks, passing_marks) 
                VALUES ('$exam_name', $subject_id, '$exam_date', $total_marks, $passing_marks)";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Exam scheduled successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// 2. EDIT EXAM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_exam'])) {
    $e_id = (int)$_POST['edit_exam_id'];
    $exam_name = $conn->real_escape_string($_POST['exam_name']);
    $subject_id = (int)$_POST['subject_id'];
    $exam_date = $conn->real_escape_string($_POST['exam_date']);
    $total_marks = (int)$_POST['total_marks'];
    $passing_marks = (int)$_POST['passing_marks'];
    
    $conn->query("UPDATE exams SET exam_name='$exam_name', subject_id=$subject_id, exam_date='$exam_date', total_marks=$total_marks, passing_marks=$passing_marks WHERE id=$e_id");
    $msg = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Exam updated successfully.</div>";
}

// 3. DELETE EXAM
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_exam'])) {
    $e_id = (int)$_POST['delete_exam_id'];
    $conn->query("DELETE FROM exams WHERE id=$e_id");
    $conn->query("DELETE FROM results WHERE exam_id=$e_id"); // delete associated results
    $msg = "<div class='alert alert-success'><i class='fas fa-trash me-2'></i>Exam and its results deleted successfully.</div>";
}

// 4. ENTER EXAM RESULTS (Student Marks)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_results'])) {
    $e_id = (int)$_POST['save_exam_id'];
    $total_m = (int)$_POST['exam_total_marks'];
    $passing_m = (int)$_POST['exam_passing_marks'];
    
    if (isset($_POST['marks']) && is_array($_POST['marks'])) {
        foreach ($_POST['marks'] as $st_id => $obtained) {
            $st_id = (int)$st_id;
            // Treat empty as 0
            if($obtained === '') continue; 
            $obtained = (int)$obtained;
            
            // Calculate Grade & Pass/Fail status purely based on acquired marks vs passing marks
            if ($obtained < $passing_m) {
                $grade = 'F';
            } else {
                $pct = ($obtained / $total_m) * 100;
                if ($pct >= 90) $grade = 'A+';
                elseif ($pct >= 80) $grade = 'A';
                elseif ($pct >= 70) $grade = 'B';
                elseif ($pct >= 60) $grade = 'C';
                elseif ($pct >= 50) $grade = 'D';
                else $grade = 'E';
            }
            
            // Upsert result
            $check = $conn->query("SELECT id FROM results WHERE exam_id=$e_id AND student_id=$st_id");
            if ($check->num_rows > 0) {
                $conn->query("UPDATE results SET obtained_marks=$obtained, grade='$grade' WHERE exam_id=$e_id AND student_id=$st_id");
            } else {
                $conn->query("INSERT INTO results (exam_id, student_id, obtained_marks, grade) VALUES ($e_id, $st_id, $obtained, '$grade')");
            }
        }
        echo "<script>window.location.href='exams.php?msg=ResultsSaved';</script>";
        exit;
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'ResultsSaved') {
    $msg = "<div class='alert alert-success fw-bold'><i class='fas fa-check-double me-2'></i>Results processed and grades calculated successfully!</div>";
}

// Pre-fetch Subjects with Class Names for Dropdowns correctly
$subj_q = $conn->query("SELECT s.id, s.subject_name, c.class_name FROM subjects s INNER JOIN classes c ON s.class_id = c.id ORDER BY c.class_name, s.subject_name");
$subjectsList = [];
if($subj_q) while($sq = $subj_q->fetch_assoc()) $subjectsList[] = $sq;

if ($action == 'list'): 
    // Fetch Exams with details
    $examsList = [];
    $query = $conn->query("
        SELECT e.*, s.subject_name, c.class_name, c.id as class_id,
        (SELECT COUNT(DISTINCT student_id) FROM results WHERE exam_id=e.id) as students_marked,
        (SELECT COUNT(DISTINCT id) FROM students WHERE class_id=c.id) as total_students,
        (SELECT COUNT(*) FROM results WHERE exam_id=e.id AND grade != 'F') as passed,
        (SELECT COUNT(*) FROM results WHERE exam_id=e.id AND grade = 'F') as failed
        FROM exams e
        INNER JOIN subjects s ON e.subject_id = s.id
        INNER JOIN classes c ON s.class_id = c.id
        ORDER BY e.exam_date DESC
    ");
    if($query) while($r = $query->fetch_assoc()) $examsList[] = $r;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-file-signature me-2 text-primary"></i>Exam & Results Management</h3>
        <p class="text-muted mb-0">Schedule exams, define total/passing marks, and automatically grade students.</p>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addExamModal">
        <i class="fas fa-plus me-2"></i>Create Exam
    </button>
</div>

<?php if($msg) echo $msg; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Exam Detail</th>
                        <th class="py-3">Subject & Class</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Marks Scope</th>
                        <th class="py-3">Result Stats</th>
                        <th class="py-3 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($examsList) > 0): foreach($examsList as $row): 
                        $status_color = "secondary";
                        $status_text = "Pending Marks";
                        
                        if ($row['students_marked'] > 0 && $row['students_marked'] == $row['total_students']) {
                            $status_color = "success"; $status_text = "Results Complete";
                        } elseif ($row['students_marked'] > 0) {
                            $status_color = "warning text-dark"; $status_text = "Results Partial";
                        }
                    ?>
                    <tr>
                        <td class="fw-bold text-dark py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-soft p-2 rounded-circle me-3 text-primary" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-pen-alt"></i></div>
                                <div>
                                    <div class="mb-0 fs-6"><?php echo htmlspecialchars($row['exam_name']); ?></div>
                                    <span class="badge bg-<?php echo $status_color; ?> mt-1 shadow-sm" style="font-size: 0.70rem;"><?php echo $status_text; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="py-3">
                            <span class="badge bg-dark px-2 shadow-sm mb-1"><?php echo htmlspecialchars($row['class_name']); ?></span><br>
                            <span class="text-muted fw-semibold" style="font-size: 0.9rem;"><i class="fas fa-book me-1"></i><?php echo htmlspecialchars($row['subject_name']); ?></span>
                        </td>
                        <td class="py-3 fw-semibold text-muted"><i class="far fa-calendar-alt me-2 text-primary"></i><?php echo date('d M, Y', strtotime($row['exam_date'])); ?></td>
                        <td class="py-3">
                            <div class="d-flex flex-column" style="font-size: 0.85rem;">
                                <span><span class="text-muted">Total: </span><span class="fw-bold"><?php echo $row['total_marks']; ?></span></span>
                                <span><span class="text-muted">Passing: </span><span class="fw-bold text-danger"><?php echo $row['passing_marks']; ?></span></span>
                            </div>
                        </td>
                        <td class="py-3">
                            <?php if ($row['students_marked'] > 0): ?>
                                <div class="progress mb-1 rounded-pill bg-light border" style="height: 6px; width: 100px;">
                                    <?php 
                                        $pass_pct = ($row['students_marked'] > 0) ? ($row['passed'] / $row['students_marked']) * 100 : 0;
                                        $fail_pct = ($row['students_marked'] > 0) ? ($row['failed'] / $row['students_marked']) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-success" style="width: <?php echo $pass_pct; ?>%"></div>
                                    <div class="progress-bar bg-danger" style="width: <?php echo $fail_pct; ?>%"></div>
                                </div>
                                <div class="d-flex gap-2" style="font-size: 0.75rem; font-weight: bold;">
                                    <span class="text-success"><i class="fas fa-check me-1"></i><?php echo $row['passed']; ?></span>
                                    <span class="text-danger"><i class="fas fa-times me-1"></i><?php echo $row['failed']; ?></span>
                                    <span class="text-muted">/ <?php echo $row['total_students']; ?> Total</span>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">No marks recorded</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border fw-bold text-dark px-3 rounded-pill dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown">
                                    Options
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                    <li>
                                        <a class="dropdown-item fw-bold text-primary py-2" href="exams.php?action=enter_marks&exam_id=<?php echo $row['id']; ?>">
                                            <i class="fas fa-poll-h fa-fw me-2"></i> Enter Marks / Results
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#editExamModal<?php echo $row['id']; ?>"><i class="fas fa-edit fa-fw me-2 text-warning"></i> Edit Exam Info</a></li>
                                    <li><a class="dropdown-item fw-semibold text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteExamModal<?php echo $row['id']; ?>"><i class="fas fa-trash fa-fw me-2"></i> Delete Exam</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Edit Exam Modal -->
                    <div class="modal fade" id="editExamModal<?php echo $row['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header bg-light border-0 pt-4 pb-3">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Exam</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <input type="hidden" name="edit_exam_id" value="<?php echo $row['id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Exam Name</label>
                                        <input type="text" name="exam_name" class="form-control bg-light" value="<?php echo htmlspecialchars($row['exam_name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Subject & Class</label>
                                        <select class="form-select bg-light" name="subject_id" required>
                                            <?php foreach($subjectsList as $s): ?>
                                                <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $row['subject_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['class_name'] . ' - ' . $s['subject_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Date</label>
                                        <input type="date" name="exam_date" class="form-control bg-light" value="<?php echo $row['exam_date']; ?>" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-muted">Total Marks</label>
                                            <input type="number" name="total_marks" class="form-control bg-light fw-bold" value="<?php echo $row['total_marks']; ?>" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-muted">Passing Marks</label>
                                            <input type="number" name="passing_marks" class="form-control bg-light fw-bold text-danger" value="<?php echo $row['passing_marks']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="edit_exam" class="btn btn-primary shadow-sm">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Exam Modal -->
                    <div class="modal fade" id="deleteExamModal<?php echo $row['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow rounded-4">
                                <div class="modal-header bg-danger text-white border-0 pt-4 pb-3 rounded-top-4">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Delete Exam</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4 text-center">
                                    <input type="hidden" name="delete_exam_id" value="<?php echo $row['id']; ?>">
                                    <i class="fas fa-trash-alt text-danger fs-1 mb-3"></i>
                                    <h4 class="fw-bold mb-2">Are you sure?</h4>
                                    <p class="text-muted mb-0">You are about to delete the exam <strong><?php echo htmlspecialchars($row['exam_name']); ?></strong>.<br>All associated <strong>marks and results for all students will be permanently deleted</strong>.</p>
                                </div>
                                <div class="modal-footer border-0 pb-4 justify-content-center">
                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_exam" class="btn btn-danger fw-bold px-4 shadow-sm">Yes, Delete Exam</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-file-signature fs-1 text-light mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark">No Exams Scheduled</h5>
                            <p>Click "Create Exam" to schedule a Mid Term, Monthly Test, or Final.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Exam Modal -->
<div class="modal fade" id="addExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-4 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Schedule New Exam</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Exam Name / Type</label>
                        <input type="text" name="exam_name" class="form-control form-control-lg bg-light" placeholder="e.g. 1st Term, Final Test" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Subject & Corresponding Class</label>
                        <?php if (empty($subjectsList)): ?>
                            <div class="alert alert-warning py-2 mb-0">No subjects exist! Please add subjects to classes via Timetable first!</div>
                        <?php else: ?>
                        <select class="form-select form-select-lg bg-light" name="subject_id" required>
                            <option value="">Select Target Subject...</option>
                            <?php foreach($subjectsList as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['class_name'] . ' - ' . $s['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Exam Date</label>
                        <input type="date" name="exam_date" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Total Scope Marks</label>
                        <input type="number" name="total_marks" class="form-control form-control-lg border-primary fw-bold" placeholder="100" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Required Pass Marks</label>
                        <input type="number" name="passing_marks" class="form-control form-control-lg border-danger fw-bold text-danger" placeholder="40" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_exam" class="btn btn-primary fw-bold px-4 shadow-sm">Save & Schedule</button>
            </div>
        </form>
    </div>
</div>

<?php 
// -----------------------------------------------------
// ENTER MARKS / RESULTS VIEW
// -----------------------------------------------------
elseif ($action == 'enter_marks' && $exam_id > 0): 

    // Get exact Exam Details 
    $exam_q = $conn->query("
        SELECT e.*, c.class_name, c.id as c_id, s.subject_name 
        FROM exams e 
        INNER JOIN subjects s ON e.subject_id = s.id 
        INNER JOIN classes c ON s.class_id = c.id 
        WHERE e.id = $exam_id
    ");
    $exam_data = $exam_q->fetch_assoc();
    if(!$exam_data) die("Exam not found!");

    $class_id = $exam_data['c_id'];

    // Get all students of this class & their possible existing marks
    $studentsList = [];
    $stu_q = $conn->query("
        SELECT st.id as student_id, st.name, st.student_id as roll_no, r.obtained_marks, r.grade 
        FROM students st 
        LEFT JOIN results r ON st.id = r.student_id AND r.exam_id = $exam_id 
        WHERE st.class_id = $class_id 
        ORDER BY st.name ASC
    ");
    if($stu_q) while($sq = $stu_q->fetch_assoc()) $studentsList[] = $sq;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-poll me-2 text-primary"></i>Enter Results: <?php echo htmlspecialchars($exam_data['exam_name']); ?></h3>
        <p class="text-muted mb-0">
            <span class="badge bg-dark me-2"><?php echo htmlspecialchars($exam_data['class_name']); ?></span> 
            Log marks for <strong><?php echo htmlspecialchars($exam_data['subject_name']); ?></strong> 
            (Max: <?php echo $exam_data['total_marks']; ?>, Pass threshold: <?php echo $exam_data['passing_marks']; ?>)
        </p>
    </div>
    <a href="exams.php" class="btn btn-outline-secondary fw-bold px-4 py-2 rounded-3">
        <i class="fas fa-arrow-left me-2"></i>Back to Exams
    </a>
</div>

<div class="row g-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom pt-4 pb-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-edit me-2 mt-1"></i>Exam Submissions Matrix</h5>
            </div>
            <div class="card-body p-4 p-md-5">
                
                <?php if (empty($studentsList)): ?>
                    <div class="alert alert-warning border-0 p-4 rounded-4"><i class="fas fa-exclamation-triangle fs-4 me-3 mt-1 float-start text-warning"></i> 
                        <h5 class="fw-bold mb-1">No Students!</h5>
                        <p class="mb-0">There are no students registered in <?php echo htmlspecialchars($exam_data['class_name']); ?> yet. Go to Add Admission to enroll students first.</p>
                    </div>
                <?php else: ?>
                <form action="exams.php" method="POST">
                    <input type="hidden" name="save_exam_id" value="<?php echo $exam_id; ?>">
                    <input type="hidden" name="exam_total_marks" value="<?php echo $exam_data['total_marks']; ?>">
                    <input type="hidden" name="exam_passing_marks" value="<?php echo $exam_data['passing_marks']; ?>">
                    
                    <div class="table-responsive bg-light p-3 rounded-4 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="py-3 px-3">Student Name & Roll No</th>
                                    <th class="py-3 text-center" style="width: 200px;">Marks Obtained</th>
                                    <th class="py-3 text-center" style="width: 150px;">Final Grade</th>
                                    <th class="py-3 text-center">Outcome</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($studentsList as $st): 
                                    // Evaluate design classes based on actual fetched grade
                                    $bg_class = "text-muted";
                                    $outcome = "--";
                                    if ($st['grade'] !== null) {
                                        if ($st['grade'] == 'F') {
                                            $bg_class = "bg-danger text-white";
                                            $outcome = "<span class='badge bg-danger shadow-sm px-3 py-2'><i class='fas fa-times me-1'></i>FAIL</span>";
                                        } else {
                                            $bg_class = "bg-success text-white";
                                            $outcome = "<span class='badge bg-success shadow-sm px-3 py-2'><i class='fas fa-check me-1'></i>PASS</span>";
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="py-3 px-3">
                                        <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($st['name']); ?></div>
                                        <small class="text-muted border bg-white px-2 rounded-pill"><?php echo htmlspecialchars($st['roll_no']); ?></small>
                                    </td>
                                    <td class="py-3 text-center">
                                        <div class="input-group">
                                            <input type="number" name="marks[<?php echo $st['student_id']; ?>]" class="form-control text-center fw-bold fs-5 <?php echo ($st['grade'] !== null) ? 'border-primary' : ''; ?>" 
                                            value="<?php echo $st['obtained_marks'] ?? ''; ?>" min="0" max="<?php echo $exam_data['total_marks']; ?>" placeholder="0">
                                            <span class="input-group-text text-muted bg-white">/ <?php echo $exam_data['total_marks']; ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-center">
                                        <?php if ($st['grade'] !== null): ?>
                                            <div class="rounded-circle <?php echo $bg_class; ?> d-flex justify-content-center align-items-center mx-auto shadow-sm" style="width: 40px; height: 40px; font-weight: bold; font-size: 1.1rem;">
                                                <?php echo $st['grade']; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fs-5">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 text-center">
                                        <?php echo $outcome; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="text-muted"><i class="fas fa-info-circle me-1"></i> Grades (A+, A, B, C, F) are automatically calculated based on entered marks vs passing marks logic.</div>
                        <button type="submit" name="save_results" class="btn btn-primary btn-lg fw-bold shadow-sm px-5">
                            <i class="fas fa-save me-2"></i>Calculate Submissions & Save
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<script>
// Prevent entering marks higher than max total
document.addEventListener("DOMContentLoaded", function() {
    let maxMarks = parseInt("<?php echo $exam_data['total_marks']; ?>");
    document.querySelectorAll("input[type=number]").forEach(input => {
        if(input.name.startsWith("marks")) {
            input.addEventListener("input", function() {
                if(parseInt(this.value) > maxMarks) {
                    this.value = maxMarks; // auto-clamp
                }
            });
        }
    });
});
</script>

<?php endif; ?>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
</style>

<?php require_once 'includes/footer.php'; ?>
