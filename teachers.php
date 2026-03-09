<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";

// 1. ADD TEACHER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_teacher'])) {
    $t_name = $conn->real_escape_string($_POST['name']);
    $t_qual = $conn->real_escape_string($_POST['qualification']);
    $t_exp = $conn->real_escape_string($_POST['experience']);
    $t_subj = $conn->real_escape_string($_POST['subject']);
    $t_phone = $conn->real_escape_string($_POST['phone']);
    $t_sal = (float)$_POST['salary'];
    $t_id = "TCH-" . rand(1000, 9999);
    
    $check = $conn->query("SELECT id FROM teachers WHERE phone='$t_phone'");
    if($check->num_rows > 0){
        $msg = "<div class='alert alert-warning mt-2'><i class='fas fa-exclamation-triangle me-2'></i>A teacher with this phone number already exists!</div>";
    } else {
        $sql = "INSERT INTO teachers (teacher_id, name, qualification, experience, subject, salary, joining_date, phone) 
                VALUES ('$t_id', '$t_name', '$t_qual', '$t_exp', '$t_subj', $t_sal, CURDATE(), '$t_phone')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>New Teacher added successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger mt-2'>Error: " . $conn->error . "</div>";
        }
    }
}

// 2. EDIT TEACHER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_teacher'])) {
    $id = (int)$_POST['teacher_id_pk'];
    $name = $conn->real_escape_string($_POST['name']);
    $qualification = $conn->real_escape_string($_POST['qualification']);
    $experience = $conn->real_escape_string($_POST['experience']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $salary = (float)$_POST['salary'];

    $conn->query("UPDATE teachers SET name='$name', qualification='$qualification', experience='$experience', subject='$subject', phone='$phone', salary=$salary WHERE id=$id");
    $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Teacher record updated successfully!</div>";
}

// 3. DELETE TEACHER
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM teachers WHERE id=$id");
    $msg = "<div class='alert alert-success mt-2'><i class='fas fa-trash-alt me-2'></i>Teacher removed successfully!</div>";
}

// Fetch Teachers
$result = $conn->query("SELECT * FROM teachers ORDER BY id DESC");
$teachers = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-chalkboard-teacher me-2 text-success"></i>Teaching Staff Management</h3>
        <p class="text-muted mb-0">Record of all hired teachers at Almadina School. Add, edit, or remove staff below.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <a href="hiring.php" class="btn btn-outline-secondary fw-bold shadow-sm px-4">
            <i class="fas fa-arrow-left me-2"></i>Back to Hiring
        </a>
        <button class="btn btn-success fw-bold shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
            <i class="fas fa-user-plus me-2"></i>Add Teacher
        </button>
    </div>
</div>

<?php if($msg) echo $msg; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Teacher ID</th>
                        <th class="py-3">Staff Member</th>
                        <th class="py-3">Contact</th>
                        <th class="py-3">Subject & Qual.</th>
                        <th class="py-3">Salary</th>
                        <th class="py-3">Joining Date</th>
                        <th class="py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($teachers) > 0): foreach($teachers as $row): ?>
                    <tr>
                        <td class="py-3"><span class="badge bg-secondary px-2 py-1"><?php echo htmlspecialchars($row['teacher_id']); ?></span></td>
                        <td class="fw-bold text-dark py-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary-soft rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                    <i class="fas fa-user-tie text-primary fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['experience'] ?? 'N/A Exp'); ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 text-muted"><i class="fas fa-phone-alt me-1 fs-xs"></i> <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                        <td class="py-3">
                            <div class="mb-1"><span class="badge bg-success-soft text-success px-3 py-1 rounded-pill"><?php echo htmlspecialchars($row['subject']); ?></span></div>
                            <small class="text-muted"><i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($row['qualification']); ?></small>
                        </td>
                        <td class="py-3 fw-semibold text-dark">Rs. <?php echo number_format($row['salary'], 2); ?></td>
                        <td class="py-3 text-muted"><?php echo date('M d, Y', strtotime($row['joining_date'])); ?></td>
                        <td class="py-3 text-center">
                            <button class="btn btn-sm btn-light border text-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>" title="Edit Teacher">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="teachers.php?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($row['name']); ?> from the teaching staff?');" class="btn btn-sm btn-light border text-danger" title="Delete Profile">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-chalkboard-teacher fs-1 text-light mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark">No Teachers Registered</h5>
                            <p>Hire new teachers from the Hiring module or add them directly here.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Teacher Modals (outside table to fix Bootstrap modal rendering) -->
<?php foreach($teachers as $row): ?>
<div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="teachers.php" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-light border-0 pt-4 pb-3">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-user-edit text-primary me-2"></i>Edit Teacher: <?php echo htmlspecialchars($row['name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <input type="hidden" name="teacher_id_pk" value="<?php echo $row['id']; ?>">
                <div class="row g-3">
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Full Name</label>
                        <input type="text" class="form-control bg-light" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Phone Number</label>
                        <input type="text" class="form-control bg-light" name="phone" value="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Qualification</label>
                        <input type="text" class="form-control bg-light" name="qualification" value="<?php echo htmlspecialchars($row['qualification']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Experience</label>
                        <input type="text" class="form-control bg-light" name="experience" value="<?php echo htmlspecialchars($row['experience'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Subject Setup</label>
                        <input type="text" class="form-control bg-light" name="subject" value="<?php echo htmlspecialchars($row['subject']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label fw-semibold text-muted">Monthly Salary (Rs.)</label>
                        <input type="number" class="form-control bg-light" name="salary" value="<?php echo $row['salary']; ?>" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_teacher" class="btn btn-primary fw-bold px-4 shadow-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Add Teacher Modal -->
<div class="modal fade" id="addTeacherModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="teachers.php" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-success text-white border-0 rounded-top-4 pt-4 pb-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add New Teacher / Staff Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5 text-start">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Primary Contact Number</label>
                        <input type="text" name="phone" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Qualification (e.g. Master's in English)</label>
                        <input type="text" name="qualification" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Total Experience</label>
                        <input type="text" name="experience" class="form-control form-control-lg bg-light" placeholder="e.g. 3 Years" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Assigned Subject / Role</label>
                        <input type="text" name="subject" class="form-control form-control-lg bg-light" placeholder="e.g. Science Teacher" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Fixed Monthly Salary (Rs.)</label>
                        <input type="number" name="salary" class="form-control form-control-lg bg-light" placeholder="e.g. 35000" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_teacher" class="btn btn-success fw-bold px-4 shadow-sm">Save Teacher Profile</button>
            </div>
        </form>
    </div>
</div>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.fs-xs { font-size: 0.75rem; }
</style>

<?php require_once 'includes/footer.php'; ?>
