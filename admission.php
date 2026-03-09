<?php
require_once 'includes/header.php';
require_once 'db.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$student_id_db = isset($_GET['student_id_db']) ? (int)$_GET['student_id_db'] : 0;
$msg = "";

// Check if classes table is populated, if not, give a warning or automatically create some classes.
$check_classes = $conn->query("SELECT COUNT(*) AS cnt FROM classes");
$row = $check_classes->fetch_assoc();
if ($row['cnt'] == 0) {
    // Insert some default classes
    $default_classes = ["Nursery", "Prep", "Class 1", "Class 2", "Class 3", "Class 4", "Class 5"];
    foreach ($default_classes as $dc) {
        $conn->query("INSERT INTO classes (class_name) VALUES ('$dc')");
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['step1'])) {
        $student_id = $conn->real_escape_string($_POST['student_id']);
        $name = $conn->real_escape_string($_POST['name']);
        $father_name = $conn->real_escape_string($_POST['father_name']);
        $dob = $conn->real_escape_string($_POST['dob']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);
        $class_id = (int)$_POST['class_id'];
        
        $sql = "INSERT INTO students (student_id, name, father_name, dob, gender, phone, address, class_id, admission_date) 
                VALUES ('$student_id', '$name', '$father_name', '$dob', '$gender', '$phone', '$address', $class_id, CURDATE())";
        if ($conn->query($sql)) {
            $inserted_id = $conn->insert_id;
            echo "<script>window.location.href='admission.php?step=2&student_id_db=".$inserted_id."';</script>";
            exit;
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST['step2'])) {
        $student_id_db = (int)$_POST['student_id_db'];
        // Simulate file upload logic here
        $files_uploaded = false;
        if (isset($_FILES['bform']) && $_FILES['bform']['error'] == 0) {
            $files_uploaded = true;
        }
        echo "<script>window.location.href='admission.php?step=3&student_id_db=".$student_id_db."';</script>";
        exit;
    } elseif (isset($_POST['step3'])) {
        $student_id_db = (int)$_POST['student_id_db'];
        $test_marks = (int)$_POST['test_marks'];
        $remarks = $conn->real_escape_string($_POST['remarks']);
        // Here we could store the test marks into a separate table or update the student's status.
        echo "<script>window.location.href='admission.php?step=4&student_id_db=".$student_id_db."';</script>";
        exit;
    } elseif (isset($_POST['step4'])) {
        $student_id_db = (int)$_POST['student_id_db'];
        $amount = (float)$_POST['amount'];
        $month = date('F');
        $year = date('Y');
        
        $sql = "INSERT INTO fees (student_id, month, year, amount, status, payment_date) 
                VALUES ($student_id_db, '$month', $year, $amount, 'Paid', CURDATE())";
        if ($conn->query($sql)) {
            echo "<script>
                    alert('Admission completed successfully for this student! Fee submitted.'); 
                    window.location.href='students.php';
                  </script>";
            exit;
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// Fetch classes for dropdown
$classes_result = $conn->query("SELECT * FROM classes");

// Fetch student data if student_id_db exists
$student_data = null;
if ($student_id_db > 0) {
    $res = $conn->query("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = $student_id_db");
    if ($res && $res->num_rows > 0) {
        $student_data = $res->fetch_assoc();
    }
}

// Fee Evaluation Logic for Step 4
$fee_amount = 0;
if ($student_data) {
    if (!empty($student_data['class_name'])) {
        $cname = strtolower($student_data['class_name']);
        if (strpos($cname, 'nursery') !== false || strpos($cname, 'prep') !== false || strpos($cname, 'kg') !== false) {
            $fee_amount = 1500;
        } elseif (strpos($cname, '1') !== false || strpos($cname, '2') !== false || strpos($cname, '3') !== false) {
            $fee_amount = 2000;
        } else {
            $fee_amount = 2500;
        }
    } else {
        $fee_amount = 2000; 
    }
}
?>

<div class="mb-4">
    <h3 class="fw-bold text-dark"><i class="fas fa-file-signature me-2 text-primary"></i>Admission Process</h3>
    <p class="text-muted">Follow the steps to admit a new student into Al.Madina Public Model School.</p>
</div>

<?php if($msg) echo $msg; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 text-dark">Admission Wizard</h5>
                
                <div class="timeline position-relative ps-2">
                    <div class="border-start border-2 border-primary position-absolute h-100" style="left: 17px; z-index: 0;"></div>
                    
                    <!-- Step 1 Indicator -->
                    <div class="d-flex align-items-start mb-4 position-relative z-1 <?php echo ($step < 1) ? 'opacity-50' : ''; ?>">
                        <div class="<?php echo ($step >= 1) ? 'bg-primary shadow-sm shadow-primary' : 'bg-secondary'; ?> text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; flex-shrink: 0;">1</div>
                        <div>
                            <h6 class="fw-bold mb-1 <?php echo ($step == 1) ? 'text-primary' : ''; ?>">Fill Admission Form</h6>
                            <small class="text-muted">Collect basic student details.</small>
                        </div>
                    </div>
                    
                    <!-- Step 2 Indicator -->
                    <div class="d-flex align-items-start mb-4 position-relative z-1 <?php echo ($step < 2) ? 'opacity-50' : ''; ?>">
                        <div class="<?php echo ($step >= 2) ? 'bg-primary shadow-sm shadow-primary' : 'bg-secondary'; ?> text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; flex-shrink: 0;">2</div>
                        <div>
                            <h6 class="fw-bold mb-1 <?php echo ($step == 2) ? 'text-primary' : ''; ?>">Submit Documents</h6>
                            <small class="text-muted">Birth Cert, B-Form, Photos.</small>
                        </div>
                    </div>
                    
                    <!-- Step 3 Indicator -->
                    <div class="d-flex align-items-start mb-4 position-relative z-1 <?php echo ($step < 3) ? 'opacity-50' : ''; ?>">
                        <div class="<?php echo ($step >= 3) ? 'bg-primary shadow-sm shadow-primary' : 'bg-secondary'; ?> text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; flex-shrink: 0;">3</div>
                        <div>
                            <h6 class="fw-bold mb-1 <?php echo ($step == 3) ? 'text-primary' : ''; ?>">Admission Test & Interview</h6>
                            <small class="text-muted">Evaluate student's level.</small>
                        </div>
                    </div>
                    
                    <!-- Step 4 Indicator -->
                    <div class="d-flex align-items-start position-relative z-1 <?php echo ($step < 4) ? 'opacity-50' : ''; ?>">
                        <div class="<?php echo ($step >= 4) ? 'bg-primary shadow-sm shadow-primary' : 'bg-secondary'; ?> text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; flex-shrink: 0;">4</div>
                        <div>
                            <h6 class="fw-bold mb-1 <?php echo ($step == 4) ? 'text-primary' : ''; ?>">Fee Submission & Reg.</h6>
                            <small class="text-muted">Finalize registration.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                
                <?php if ($step == 1): ?>
                <!-- STEP 1 FORM -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-soft p-3 rounded-circle me-3">
                        <i class="fas fa-user-edit fs-4 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Step 1: Application Form</h5>
                </div>
                
                <form action="admission.php?step=1" method="POST">
                    <input type="hidden" name="step1" value="1">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <h6 class="text-muted border-bottom pb-2 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Student Information</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Student Application ID</label>
                            <input type="text" class="form-control form-control-lg bg-light" name="student_id" value="<?php echo 'ADM-'.rand(1000,9999); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Class Applied For</label>
                            <select class="form-select form-select-lg bg-light" name="class_id" required>
                                <option value="">Select Class...</option>
                                <?php while($crow = $classes_result->fetch_assoc()): ?>
                                    <option value="<?php echo $crow['id']; ?>"><?php echo htmlspecialchars($crow['class_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control form-control-lg bg-light" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" class="form-control form-control-lg bg-light" name="dob" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Gender</label>
                            <select class="form-select form-select-lg bg-light" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12 mt-4">
                            <h6 class="text-muted border-bottom pb-2 fw-semibold text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Guardian & Contact Info</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Father's Name</label>
                            <input type="text" class="form-control form-control-lg bg-light" name="father_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Number</label>
                            <input type="text" class="form-control form-control-lg bg-light" name="phone" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Residential Address</label>
                            <textarea class="form-control form-control-lg bg-light" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-5 py-3 shadow-sm rounded-3">
                                Save & Proceed Next <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <?php elseif ($step == 2): ?>
                <!-- STEP 2 FORM -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-soft p-3 rounded-circle me-3">
                        <i class="fas fa-file-upload fs-4 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Step 2: Submit Documents</h5>
                </div>
                
                <div class="alert alert-info border-0 rounded-4">
                    <i class="fas fa-info-circle me-2"></i>Please upload the required documents for <strong><?php echo htmlspecialchars($student_data['name']); ?></strong> (Class: <?php echo htmlspecialchars($student_data['class_name'] ?? ''); ?>)
                </div>
                
                <form action="admission.php?step=2" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="step2" value="1">
                    <input type="hidden" name="student_id_db" value="<?php echo $student_id_db; ?>">
                    
                    <div class="row g-4 mt-2">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">B-Form / Birth Certificate</label>
                            <input class="form-control form-control-lg bg-light" type="file" name="bform" accept="image/*,.pdf">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Father's CNIC Copy</label>
                            <input class="form-control form-control-lg bg-light" type="file" name="cnic" accept="image/*,.pdf">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Student Photos (Passport Size)</label>
                            <input class="form-control form-control-lg bg-light" type="file" name="photos" accept="image/*">
                        </div>
                        
                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-5 py-3 shadow-sm rounded-3">
                                Upload & Proceed Next <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <?php elseif ($step == 3): ?>
                <!-- STEP 3 FORM -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-soft p-3 rounded-circle me-3">
                        <i class="fas fa-tasks fs-4 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Step 3: Admission Test & Interview</h5>
                </div>
                
                <div class="alert alert-warning border-0 rounded-4">
                    <i class="fas fa-edit me-2"></i>Evaluating <strong><?php echo htmlspecialchars($student_data['name']); ?></strong> for admission in <strong><?php echo htmlspecialchars($student_data['class_name'] ?? ''); ?></strong>.
                </div>
                
                <form action="admission.php?step=3" method="POST">
                    <input type="hidden" name="step3" value="1">
                    <input type="hidden" name="student_id_db" value="<?php echo $student_id_db; ?>">
                    
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Test Subject</label>
                            <input type="text" class="form-control form-control-lg bg-light" value="English, Math, Urdu" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Marks Obtained</label>
                            <input type="number" class="form-control form-control-lg bg-light" name="test_marks" placeholder="e.g. 85 / 100" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Interviewer / Teacher Remarks</label>
                            <textarea class="form-control form-control-lg bg-light" name="remarks" rows="3" placeholder="Notes regarding the student's performance..." required></textarea>
                        </div>
                        
                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-5 py-3 shadow-sm rounded-3">
                                Submit Score & Proceed Next <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <?php elseif ($step == 4): ?>
                <!-- STEP 4 FORM -->
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-soft p-3 rounded-circle me-3">
                        <i class="fas fa-money-bill-wave fs-4 text-primary"></i>
                    </div>
                    <h5 class="fw-bold mb-0">Step 4: Fee Submission & Confirmation</h5>
                </div>
                
                <div class="alert alert-success border-0 rounded-4">
                    <i class="fas fa-check-circle me-2"></i>Admission approved for <strong><?php echo htmlspecialchars($student_data['name']); ?></strong> into <strong><?php echo htmlspecialchars($student_data['class_name'] ?? ''); ?></strong>. Proceed to collect admission and monthly fees.
                </div>
                
                <form action="admission.php?step=4" method="POST">
                    <input type="hidden" name="step4" value="1">
                    <input type="hidden" name="student_id_db" value="<?php echo $student_id_db; ?>">
                    
                    <div class="card bg-light border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4 text-center">
                            <h6 class="text-muted text-uppercase fw-bold letter-spacing-1 mb-2">Calculated Fee for <?php echo htmlspecialchars($student_data['class_name'] ?? ''); ?></h6>
                            <h2 class="display-4 fw-bold text-dark mb-0">Rs. <?php echo $fee_amount; ?></h2>
                        </div>
                    </div>
                    
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Fee Amount to Deposit (Rs)</label>
                            <input type="number" class="form-control form-control-lg bg-white shadow-sm" name="amount" value="<?php echo $fee_amount; ?>" required>
                            <small class="text-muted">You can manually adjust this if discounts apply.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Method</label>
                            <select class="form-select form-select-lg bg-white shadow-sm">
                                <option>Cash</option>
                                <option>Bank Transfer</option>
                                <option>Cheque</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mt-5 text-end">
                            <button type="submit" class="btn btn-success fw-bold px-5 py-3 shadow-sm rounded-3 w-100">
                                <i class="fas fa-check-circle me-2"></i>Pay Fee & Complete Registration
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
