<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";

// Ensure student_results table exists out of 600
$conn->query("CREATE TABLE IF NOT EXISTS student_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) NOT NULL,
    urdu INT DEFAULT 0,
    islamiat INT DEFAULT 0,
    english INT DEFAULT 0,
    math INT DEFAULT 0,
    science INT DEFAULT 0,
    social_studies INT DEFAULT 0,
    total_obtained INT DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    grade VARCHAR(5) DEFAULT 'F',
    UNIQUE KEY unique_student_exam (exam_id, roll_no)
)");

// Fetch Available Exams from Exams Table
$exams_res = $conn->query("SELECT id, exam_name, exam_date FROM exams ORDER BY id DESC");
$available_exams = [];
if($exams_res) {
    while($er = $exams_res->fetch_assoc()) $available_exams[] = $er;
}

$selected_class = $_GET['class'] ?? '';
$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$students = [];
$exam_details = null;

// Generate Dummy Students based on Attendance Logic if valid Class/Exam Selected
if ($selected_class >= 1 && $selected_class <= 5 && $selected_exam > 0) {
    // Get Exam Info
    $ex_q = $conn->query("SELECT * FROM exams WHERE id = $selected_exam");
    if($ex_q && $ex_q->num_rows > 0) $exam_details = $ex_q->fetch_assoc();

    if ($exam_details) {
        $c_name = "Class $selected_class";
        for ($i = 1; $i <= 25; $i++) {
            $students[] = [
                'roll_no' => $c_name . "-R" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name'    => "Student $i ($c_name)"
            ];
        }

        // Fetch existing saved marks for this combined set
        $saved_marks = [];
        $res_q = $conn->query("SELECT * FROM student_results WHERE exam_id = $selected_exam AND class_name = '$c_name'");
        if($res_q) {
            while($rq = $res_q->fetch_assoc()) {
                $saved_marks[$rq['roll_no']] = $rq;
            }
        }
    }
}

// 1. SAVE RESULTS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_results'])) {
    $e_id = (int)$_POST['exam_id'];
    $c_class = $conn->real_escape_string($_POST['class_name']);
    
    foreach($_POST['marks'] as $roll => $marks) {
        $s_name = $conn->real_escape_string($marks['name']);
        
        // Cap marks at 100 each
        $urdu = min(100, max(0, (int)$marks['urdu']));
        $islamiat = min(100, max(0, (int)$marks['islamiat']));
        $english = min(100, max(0, (int)$marks['english']));
        $math = min(100, max(0, (int)$marks['math']));
        $science = min(100, max(0, (int)$marks['science']));
        $sst = min(100, max(0, (int)$marks['social_studies']));
        
        $total = $urdu + $islamiat + $english + $math + $science + $sst;
        $pct = ($total / 600) * 100;
        
        if($pct >= 80) $grade = 'A+';
        elseif($pct >= 70) $grade = 'A';
        elseif($pct >= 60) $grade = 'B';
        elseif($pct >= 50) $grade = 'C';
        elseif($pct >= 40) $grade = 'D';
        else $grade = 'F';

        // Insert or Update
        $check = $conn->query("SELECT id FROM student_results WHERE exam_id=$e_id AND roll_no='$roll'");
        if($check->num_rows > 0) {
            $conn->query("UPDATE student_results SET 
                            urdu=$urdu, islamiat=$islamiat, english=$english, 
                            math=$math, science=$science, social_studies=$sst, 
                            total_obtained=$total, percentage=$pct, grade='$grade' 
                          WHERE exam_id=$e_id AND roll_no='$roll'");
        } else {
            $conn->query("INSERT INTO student_results (exam_id, class_name, student_name, roll_no, urdu, islamiat, english, math, science, social_studies, total_obtained, percentage, grade) 
                          VALUES ($e_id, '$c_class', '$s_name', '$roll', $urdu, $islamiat, $english, $math, $science, $sst, $total, $pct, '$grade')");
        }
    }
    
    $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Results Saved & Grades Calculated Successfully!</div>";
    
    // Refresh Saved Marks explicitly after POST to render immediate updates
    $saved_marks = [];
    $res_q = $conn->query("SELECT * FROM student_results WHERE exam_id = $e_id AND class_name = '$c_class'");
     if($res_q) {
         while($rq = $res_q->fetch_assoc()) $saved_marks[$rq['roll_no']] = $rq;
     }
}
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-poll me-2 text-primary"></i>Result Management</h3>
        <p class="text-muted mb-0">Select a Class (1-5), enter standard subject marks, and print smart report cards.</p>
    </div>
</div>

<?php if($msg) echo $msg; ?>

<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4 p-md-5">
                <form method="GET" action="results.php" class="row g-4 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Select Target Class</label>
                        <select name="class" class="form-select form-select-lg bg-light text-dark fw-semibold" required>
                            <option value="">Choose Class...</option>
                            <option value="1" <?php if($selected_class == '1') echo 'selected'; ?>>Class 1</option>
                            <option value="2" <?php if($selected_class == '2') echo 'selected'; ?>>Class 2</option>
                            <option value="3" <?php if($selected_class == '3') echo 'selected'; ?>>Class 3</option>
                            <option value="4" <?php if($selected_class == '4') echo 'selected'; ?>>Class 4</option>
                            <option value="5" <?php if($selected_class == '5') echo 'selected'; ?>>Class 5</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Select Specific Exam</label>
                        <?php if(empty($available_exams)): ?>
                            <div class="alert alert-danger py-2 mb-0 border-0"><i class="fas fa-exclamation-circle me-1"></i>Please schedule an exam in the Exams Module first.</div>
                        <?php else: ?>
                        <select name="exam_id" class="form-select form-select-lg bg-light text-dark fw-semibold" required>
                            <option value="">Choose Scheduled Exam...</option>
                            <?php foreach($available_exams as $ex): ?>
                                <option value="<?php echo $ex['id']; ?>" <?php if($selected_exam == $ex['id']) echo 'selected'; ?>><?php echo htmlspecialchars($ex['exam_name']); ?> (<?php echo date('M Y', strtotime($ex['exam_date'])); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-3 w-100 shadow-sm rounded-3"><i class="fas fa-search me-2"></i>Load Result Sheet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($students) && $exam_details): ?>
<form method="POST" action="results.php?class=<?php echo $selected_class; ?>&exam_id=<?php echo $selected_exam; ?>">
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom pb-4 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-edit me-2 text-primary"></i>Marks Entry - Class <?php echo $selected_class; ?> &mdash; <?php echo htmlspecialchars($exam_details['exam_name']); ?></h5>
        <button type="submit" name="save_results" class="btn btn-success fw-bold shadow-sm px-4"><i class="fas fa-save me-2"></i>Calculate & Save Current Sheet</button>
    </div>
    
    <div class="card-body p-0">
        <input type="hidden" name="exam_id" value="<?php echo $selected_exam; ?>">
        <input type="hidden" name="class_name" value="Class <?php echo $selected_class; ?>">

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-start py-3 fs-6 px-4">Student Name & Roll</th>
                        <th class="py-3" style="width: 90px;" title="Urdu">URD <br><small class="text-muted fw-normal">100</small></th>
                        <th class="py-3" style="width: 90px;" title="Islamiat">ISL <br><small class="text-muted fw-normal">100</small></th>
                        <th class="py-3" style="width: 90px;" title="English">ENG <br><small class="text-muted fw-normal">100</small></th>
                        <th class="py-3" style="width: 90px;" title="Mathematics">MTH <br><small class="text-muted fw-normal">100</small></th>
                        <th class="py-3" style="width: 90px;" title="Science">SCI <br><small class="text-muted fw-normal">100</small></th>
                        <th class="py-3" style="width: 90px;" title="Social Studies">SST <br><small class="text-muted fw-normal">100</small></th>
                        <th class="bg-primary-soft text-primary py-3" style="width: 100px;">Total <br><small class="fw-normal">600</small></th>
                        <th class="bg-primary-soft text-primary py-3" style="width: 80px;">Grade</th>
                        <th class="py-3" style="width: 110px;">Report Card</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): 
                        $roll = $student['roll_no'];
                        $saved = isset($saved_marks[$roll]) ? $saved_marks[$roll] : null;
                    ?>
                    <tr>
                        <td class="text-start fw-bold text-dark fs-6 py-3 px-4">
                            <input type="hidden" name="marks[<?php echo $roll; ?>][name]" value="<?php echo $student['name']; ?>">
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user fs-xs"></i>
                                </div>
                                <div class="text-wrap" style="min-width: 120px;">
                                    <div class="text-dark"><?php echo $student['name']; ?></div>
                                    <small class="text-muted fw-normal letter-spacing-1"><?php echo $roll; ?></small>
                                </div>
                            </div>
                        </td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][urdu]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['urdu'] : '0'; ?>" min="0" max="100"></td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][islamiat]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['islamiat'] : '0'; ?>" min="0" max="100"></td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][english]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['english'] : '0'; ?>" min="0" max="100"></td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][math]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['math'] : '0'; ?>" min="0" max="100"></td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][science]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['science'] : '0'; ?>" min="0" max="100"></td>
                        <td><input type="number" name="marks[<?php echo $roll; ?>][social_studies]" class="form-control text-center mx-auto fw-bold p-1" value="<?php echo $saved ? $saved['social_studies'] : '0'; ?>" min="0" max="100"></td>
                        
                        <td class="fw-bold fs-5 text-dark bg-light"><?php echo $saved ? $saved['total_obtained'] : '-'; ?></td>
                        <td class="bg-light">
                            <?php if($saved): 
                                $g = $saved['grade'];
                                $badge = ($g=='A+'||$g=='A') ? 'bg-success' : (($g=='B'||$g=='C') ? 'bg-primary' : (($g=='F') ? 'bg-danger' : 'bg-warning text-dark'));
                            ?>
                                <span class="badge <?php echo $badge; ?> px-2 py-1 fs-6 shadow-sm"><?php echo $g; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($saved): ?>
                                <a href="print_report.php?exam=<?php echo $selected_exam; ?>&roll=<?php echo urlencode($roll); ?>" target="_blank" class="btn border btn-light text-primary fw-bold shadow-sm btn-sm px-3"><i class="fas fa-print me-1"></i> Print</a>
                            <?php else: ?>
                                <button type="button" class="btn border btn-light text-muted fw-bold shadow-sm btn-sm px-3 disabled" title="Save Results to Print"><i class="fas fa-print me-1"></i> Print</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</form>
<?php endif; ?>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
.letter-spacing-1 { letter-spacing: 1px; }
.fs-xs { font-size: 0.70rem; }
/* Chrome, Safari, Edge, Opera hide number arrows */
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
/* Firefox hide number arrows */
input[type=number] { -moz-appearance: textfield; }
</style>

<?php require_once 'includes/footer.php'; ?>
