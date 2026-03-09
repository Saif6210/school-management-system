<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";

// Robustness: Add detailed columns if they don't exist
$check_col = $conn->query("SHOW COLUMNS FROM candidates LIKE 'cv_path'");
if($check_col->num_rows == 0){
    $conn->query("ALTER TABLE candidates ADD COLUMN cv_path VARCHAR(255) NULL AFTER phone");
}
$check_int = $conn->query("SHOW COLUMNS FROM candidates LIKE 'interview_marks'");
if($check_int->num_rows == 0){
    $conn->query("ALTER TABLE candidates ADD COLUMN interviewer_name VARCHAR(100) NULL AFTER status");
    $conn->query("ALTER TABLE candidates ADD COLUMN interview_marks INT NULL AFTER interviewer_name");
    $conn->query("ALTER TABLE candidates ADD COLUMN interview_remarks TEXT NULL AFTER interview_marks");
    
    $conn->query("ALTER TABLE candidates ADD COLUMN demo_class_id INT NULL AFTER interview_remarks");
    $conn->query("ALTER TABLE candidates ADD COLUMN demo_topic VARCHAR(100) NULL AFTER demo_class_id");
    $conn->query("ALTER TABLE candidates ADD COLUMN demo_marks INT NULL AFTER demo_topic");
    $conn->query("ALTER TABLE candidates ADD COLUMN demo_remarks TEXT NULL AFTER demo_marks");
    
    $conn->query("ALTER TABLE candidates ADD COLUMN proposed_salary DECIMAL(10,2) NULL AFTER demo_remarks");
}

// 1. Add Candidate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_candidate'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $applied_for = $conn->real_escape_string($_POST['applied_for']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = 'Screening'; 
    
    // Handle CV
    $cv_path = "";
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
        $upload_dir = 'uploads/cvs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
        $cv_name = "CV_" . time() . "_" . rand(100, 999) . "." . $file_ext;
        if (move_uploaded_file($_FILES['cv']['tmp_name'], $upload_dir . $cv_name)) {
            $cv_path = $upload_dir . $cv_name;
        }
    }
    
    $sql = "INSERT INTO candidates (name, qualification, experience, applied_for, phone, status, cv_path) 
            VALUES ('$name', '$qualification', '$experience', '$applied_for', '$phone', '$status', '$cv_path')";
    
    if ($conn->query($sql)) {
        $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Candidate added successfully.</div>";
    } else {
        $msg = "<div class='alert alert-danger mt-2'>Error: " . $conn->error . "</div>";
    }
}

// 2. Conduct Interview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conduct_interview'])) {
    $c_id = (int)$_POST['candidate_id'];
    $interviewer_name = $conn->real_escape_string($_POST['interviewer_name']);
    $interview_marks = (int)$_POST['interview_marks'];
    $interview_remarks = $conn->real_escape_string($_POST['interview_remarks']);
    
    $conn->query("UPDATE candidates SET status='Interview', interviewer_name='$interviewer_name', interview_marks=$interview_marks, interview_remarks='$interview_remarks' WHERE id=$c_id");
    $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Interview recorded successfully.</div>";
}

// 3. Conduct Demo Class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['conduct_demo'])) {
    $c_id = (int)$_POST['candidate_id'];
    $demo_class_id = (int)$_POST['demo_class_id'];
    $demo_topic = $conn->real_escape_string($_POST['demo_topic']);
    $demo_marks = (int)$_POST['demo_marks'];
    $demo_remarks = $conn->real_escape_string($_POST['demo_remarks']);
    
    $conn->query("UPDATE candidates SET status='Demo', demo_class_id=$demo_class_id, demo_topic='$demo_topic', demo_marks=$demo_marks, demo_remarks='$demo_remarks' WHERE id=$c_id");
    $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Demo class recorded successfully.</div>";
}

// 4. Final Decision (Select/Reject)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_decision'])) {
    $c_id = (int)$_POST['candidate_id'];
    $new_status = $conn->real_escape_string($_POST['final_status']);
    $proposed_salary = isset($_POST['proposed_salary']) ? (float)$_POST['proposed_salary'] : 0;
    
    $conn->query("UPDATE candidates SET status='$new_status', proposed_salary=$proposed_salary WHERE id=$c_id");
    
    // Transfer to Teachers Table
    if ($new_status == 'Selected') {
        $cand_data = $conn->query("SELECT * FROM candidates WHERE id=$c_id")->fetch_assoc();
        if ($cand_data) {
            $t_id = "TCH-" . rand(1000, 9999);
            $t_name = $cand_data['name'];
            $t_qual = $cand_data['qualification'];
            $t_exp = $cand_data['experience'];
            $t_subj = $cand_data['applied_for'];
            $t_phone = $cand_data['phone'];
            $t_sal = ($cand_data['proposed_salary'] > 0) ? $cand_data['proposed_salary'] : 15000;
            
            $check = $conn->query("SELECT id FROM teachers WHERE phone='$t_phone' AND name='$t_name'");
            if($check->num_rows == 0){
                $conn->query("INSERT INTO teachers (teacher_id, name, qualification, experience, subject, salary, joining_date, phone) 
                              VALUES ('$t_id', '$t_name', '$t_qual', '$t_exp', '$t_subj', $t_sal, CURDATE(), '$t_phone')");
            }
        }
        $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Candidate hired and transferred to Teaching Staff!</div>";
    } else {
        $msg = "<div class='alert alert-warning mt-2'><i class='fas fa-info-circle me-2'></i>Candidate rejected.</div>";
    }
}

// Fetch Candidates
$result = $conn->query("SELECT * FROM candidates ORDER BY id DESC");

// Fetch Classes for Demo
$classes_res = $conn->query("SELECT * FROM classes");
$classes = [];
if($classes_res) {
    while($cr = $classes_res->fetch_assoc()) $classes[] = $cr;
}

// Fetch Pipeline Stats
$stats = ['Screening' => 0, 'Interview' => 0, 'Demo' => 0, 'Selected' => 0, 'Rejected' => 0];
$stat_q = $conn->query("SELECT status, COUNT(*) as cnt FROM candidates GROUP BY status");
if($stat_q) {
    while($sr = $stat_q->fetch_assoc()) {
        if(isset($stats[$sr['status']])) $stats[$sr['status']] = $sr['cnt'];
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-briefcase me-2 text-primary"></i>Teacher Hiring Pipeline</h3>
        <p class="text-muted mb-0">Manage job applications step-by-step: Screening > Interview > Demo > Selection.</p>
    </div>
    <a href="teachers.php" class="btn btn-outline-primary fw-bold shadow-sm px-4">
        <i class="fas fa-chalkboard-teacher me-2"></i>View Staff
    </a>
</div>

<?php if($msg) echo $msg; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-4 pb-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Active Candidates</h5>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCandidateModal"><i class="fas fa-plus me-1"></i> Add Candidate</button>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Candidate Info</th>
                        <th class="py-3">Position</th>
                        <th class="py-3">CV & Contact</th>
                        <th class="py-3">Current Status</th>
                        <th class="py-3 text-end">Action Flow</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-user-tie text-secondary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($row['name']); ?></h6>
                                    <small class="text-muted"><i class="fas fa-graduation-cap me-1"></i><?php echo htmlspecialchars($row['qualification']); ?> | <i class="fas fa-history me-1"></i><?php echo htmlspecialchars($row['experience']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3"><span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill"><?php echo htmlspecialchars($row['applied_for']); ?></span></td>
                        <td class="py-3">
                            <div class="mb-1"><small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?php echo htmlspecialchars($row['phone']); ?></small></div>
                            <?php if(!empty($row['cv_path']) && file_exists($row['cv_path'])): ?>
                                <a href="<?php echo $row['cv_path']; ?>" target="_blank" class="badge bg-info-soft text-info text-decoration-none"><i class="fas fa-file-pdf me-1"></i> View CV</a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted">No CV attached</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3">
                            <?php 
                            $bg = 'bg-secondary';
                            if($row['status'] == 'Interview') $bg = 'bg-info text-dark shadow-sm';
                            if($row['status'] == 'Demo') $bg = 'bg-warning text-dark shadow-sm';
                            if($row['status'] == 'Selected') $bg = 'bg-success shadow-sm';
                            if($row['status'] == 'Rejected') $bg = 'bg-danger shadow-sm';
                            ?>
                            <span class="badge px-3 py-2 rounded-pill <?php echo $bg; ?>"><?php echo $row['status']; ?></span>
                        </td>
                        <td class="py-3 text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border fw-bold text-dark px-3 rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-tasks me-1 text-primary"></i> Process
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="z-index: 1050;">
                                    <li><h6 class="dropdown-header text-uppercase letter-spacing-1">Hiring Steps</h6></li>
                                    <li>
                                        <a class="dropdown-item fw-semibold py-2 <?php echo ($row['status'] == 'Screening' || $row['status'] == 'Interview') ? 'text-primary' : ''; ?>" href="javascript:void(0)" onclick='openInterviewModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-users fa-fw me-2"></i> 1. Conduct Interview
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item fw-semibold py-2 <?php echo ($row['status'] == 'Interview' || $row['status'] == 'Demo') ? 'text-warning' : ''; ?>" href="javascript:void(0)" onclick='openDemoModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-chalkboard fa-fw me-2"></i> 2. Assign Demo Class
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item fw-semibold py-2 text-success" href="javascript:void(0)" onclick='openDecisionModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)'>
                                            <i class="fas fa-gavel fa-fw me-2"></i> 3. Final Decision
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    

                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fs-1 text-light mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark">No Candidates Found</h5>
                            <p>There are no candidates in the pipeline yet.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Shared Modals Outside Loop -->
<div class="modal fade" id="interviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="hiring.php" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-info-soft border-0 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-users text-info me-2"></i>Conduct Interview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <input type="hidden" name="candidate_id" id="interview_candidate_id" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Interviewer Panel / Name</label>
                    <input type="text" class="form-control form-control-lg bg-light" name="interviewer_name" required placeholder="e.g. Principal Ali">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Interview Performance Marks (Out of 10)</label>
                    <input type="number" class="form-control form-control-lg bg-light" name="interview_marks" min="0" max="10" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Interview Remarks / Assessment</label>
                    <textarea class="form-control form-control-lg bg-light" name="interview_remarks" rows="3" required placeholder="Communication skills, confidence, subject knowledge..."></textarea>
                </div>
                <div class="alert alert-info border-0 rounded-3 mb-0">
                    <i class="fas fa-info-circle me-1"></i> Saving this will mark candidate status as <strong>Interview</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="conduct_interview" class="btn btn-info text-white fw-bold px-4 shadow-sm">Save Interview</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="demoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="hiring.php" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-warning-soft border-0 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-chalkboard text-warning me-2"></i>Assign Demo Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <input type="hidden" name="candidate_id" id="demo_candidate_id" value="">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Target Class</label>
                    <?php if(empty($classes)): ?>
                        <div class="alert alert-danger py-2 mb-0">No classes exist! Go to Class Management first.</div>
                    <?php else: ?>
                        <select class="form-select form-select-lg bg-light" name="demo_class_id" required>
                            <option value="">Select Class...</option>
                            <?php foreach($classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Assigned Topic</label>
                    <input type="text" class="form-control form-control-lg bg-light" name="demo_topic" required placeholder="e.g. Algebra Basics">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Demo Class Marks (Out of 10)</label>
                    <input type="number" class="form-control form-control-lg bg-light" name="demo_marks" min="0" max="10" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Class Control / Teaching Evaluation Remarks</label>
                    <textarea class="form-control form-control-lg bg-light" name="demo_remarks" rows="3" required placeholder="Student engagement, board writing, topic delivery..."></textarea>
                </div>
                <div class="alert alert-warning border-0 rounded-3 mb-0">
                    <i class="fas fa-info-circle me-1"></i> Saving this will mark candidate status as <strong>Demo</strong>.
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="conduct_demo" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">Save Demo Evaluation</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="decisionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="hiring.php" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success-soft border-0 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-gavel text-success me-2"></i>Final Decision</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <input type="hidden" name="candidate_id" id="decision_candidate_id" value="">
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Interview Score</small>
                            <h4 class="fw-bold mb-0 text-info" id="interview_score_display">N/A</h4>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border h-100">
                            <small class="text-muted text-uppercase fw-bold d-block mb-1">Demo Score</small>
                            <h4 class="fw-bold mb-0 text-warning" id="demo_score_display">N/A</h4>
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Select Final Action</label>
                    <select class="form-select form-select-lg bg-light fw-bold" name="final_status" onchange="toggleSalaryVisibility(this.value)" required>
                        <option value="">Choose Decision...</option>
                        <option value="Selected">✅ Select & Hire</option>
                        <option value="Rejected">❌ Reject Candidate</option>
                    </select>
                </div>
                <div class="mb-2" id="salaryField" style="display: none;">
                    <label class="form-label fw-semibold text-muted">Finalized Salary Amount (Rs.)</label>
                    <input type="number" class="form-control form-control-lg bg-white shadow-sm border-success border-2" name="proposed_salary" placeholder="e.g. 25000">
                    <small class="text-success fw-semibold mt-1 d-block"><i class="fas fa-check-circle me-1"></i>Hiring will automatically create a Teacher Profile with this salary.</small>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="make_decision" class="btn btn-success fw-bold px-4 shadow-sm">Confirm Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
function openInterviewModal(data) {
    document.getElementById('interview_candidate_id').value = data.id;
    document.querySelector('#interviewModal .modal-title').innerHTML = '<i class="fas fa-users text-info me-2"></i>Conduct Interview: ' + data.name;
    document.querySelector('#interviewModal input[name="interviewer_name"]').value = data.interviewer_name || '';
    document.querySelector('#interviewModal input[name="interview_marks"]').value = data.interview_marks || '';
    document.querySelector('#interviewModal textarea[name="interview_remarks"]').value = data.interview_remarks || '';
    var modal = new bootstrap.Modal(document.getElementById('interviewModal'));
    modal.show();
}

function openDemoModal(data) {
    document.getElementById('demo_candidate_id').value = data.id;
    document.querySelector('#demoModal .modal-title').innerHTML = '<i class="fas fa-chalkboard text-warning me-2"></i>Assign Demo Class: ' + data.name;
    document.querySelector('#demoModal select[name="demo_class_id"]').value = data.demo_class_id || '';
    document.querySelector('#demoModal input[name="demo_topic"]').value = data.demo_topic || '';
    document.querySelector('#demoModal input[name="demo_marks"]').value = data.demo_marks || '';
    document.querySelector('#demoModal textarea[name="demo_remarks"]').value = data.demo_remarks || '';
    var modal = new bootstrap.Modal(document.getElementById('demoModal'));
    modal.show();
}

function openDecisionModal(data) {
    document.getElementById('decision_candidate_id').value = data.id;
    document.querySelector('#decisionModal .modal-title').innerHTML = '<i class="fas fa-gavel text-success me-2"></i>Final Decision: ' + data.name;
    document.getElementById('interview_score_display').textContent = data.interview_marks ? data.interview_marks + '/10' : 'N/A';
    document.getElementById('demo_score_display').textContent = data.demo_marks ? data.demo_marks + '/10' : 'N/A';
    
    let currentStatus = data.status;
    if (currentStatus !== 'Selected' && currentStatus !== 'Rejected') {
        currentStatus = '';
    }
    document.querySelector('#decisionModal select[name="final_status"]').value = currentStatus;
    document.querySelector('#decisionModal input[name="proposed_salary"]').value = data.proposed_salary || '';
    
    toggleSalaryVisibility(currentStatus);
    var modal = new bootstrap.Modal(document.getElementById('decisionModal'));
    modal.show();
}

// Show/hide salary field based on selection
function toggleSalaryVisibility(val) {
    var field = document.getElementById('salaryField');
    if(val === 'Selected') {
        field.style.display = 'block';
    } else {
        field.style.display = 'none';
        field.querySelector('input').value = '';
    }
}
</script>

<!-- Add Candidate Modal -->
<div class="modal fade" id="addCandidateModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-primary text-white pt-4 pb-4 border-0 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add New Candidate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5 text-start">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Contact Number</label>
                        <input type="text" name="phone" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Qualification</label>
                        <input type="text" name="qualification" class="form-control form-control-lg bg-light" placeholder="e.g. BS Mathematics" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Experience</label>
                        <input type="text" name="experience" class="form-control form-control-lg bg-light" placeholder="e.g. 2 Years" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Applying For Subject / Class</label>
                        <input type="text" name="applied_for" class="form-control form-control-lg bg-light" placeholder="e.g. Math for Class 5" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Upload CV (Optional)</label>
                        <input type="file" name="cv" class="form-control form-control-lg bg-light" accept=".pdf, image/*">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_candidate" class="btn btn-primary fw-bold px-4 shadow-sm">Save Candidate</button>
            </div>
        </form>
    </div>
</div>

<style>
.letter-spacing-1 { letter-spacing: 1px; }
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
</style>

<?php require_once 'includes/footer.php'; ?>
