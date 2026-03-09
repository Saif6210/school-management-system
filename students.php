<?php
require_once 'includes/header.php';
require_once 'db.php';

// Handle Add Student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $fname = $conn->real_escape_string($_POST['father_name']);
    $dob = $conn->real_escape_string($_POST['dob']);
    
    $sql = "INSERT INTO students (student_id, name, father_name, dob, admission_date) 
            VALUES ('$student_id', '$name', '$fname', '$dob', CURDATE())";
    if($conn->query($sql)) {
        $msg = "<div class='alert alert-success mt-3 fs-6'><i class='fas fa-check-circle me-2'></i>Student added successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger mt-3 fs-6'><i class='fas fa-exclamation-triangle me-2'></i>Error: " . $conn->error . "</div>";
    }
}

// Handle Edit Student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_student'])) {
    $id = (int)$_POST['id'];
    $student_id = $conn->real_escape_string($_POST['student_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $fname = $conn->real_escape_string($_POST['father_name']);
    $dob = $conn->real_escape_string($_POST['dob']);
    
    $sql = "UPDATE students SET student_id='$student_id', name='$name', father_name='$fname', dob='$dob' WHERE id=$id";
    if($conn->query($sql)) {
        $msg = "<div class='alert alert-success mt-3 fs-6'><i class='fas fa-check-circle me-2'></i>Student updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger mt-3 fs-6'><i class='fas fa-exclamation-triangle me-2'></i>Error: " . $conn->error . "</div>";
    }
}

// Handle Delete Student
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $id = (int)$_POST['id'];
    if($conn->query("DELETE FROM students WHERE id=$id")) {
        $msg = "<div class='alert alert-success mt-3 fs-6'><i class='fas fa-check-circle me-2'></i>Student deleted successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger mt-3 fs-6'><i class='fas fa-exclamation-triangle me-2'></i>Error: " . $conn->error . "</div>";
    }
}

// Fetch Students
$result = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
    <h3 class="fw-bold text-dark mb-0"><i class="fas fa-user-graduate me-2 text-primary"></i>Student Management</h3>
    <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-plus me-2"></i>Add New Student
    </button>
</div>

<?php if(isset($msg)) echo $msg; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">ID</th>
                        <th class="py-3">Student ID</th>
                        <th class="py-3">Name</th>
                        <th class="py-3">Father's Name</th>
                        <th class="py-3">DOB</th>
                        <th class="py-3">Admission Date</th>
                        <th class="py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="py-3"><?php echo $row['id']; ?></td>
                        <td class="py-3"><span class="badge bg-secondary"><?php echo htmlspecialchars($row['student_id']); ?></span></td>
                        <td class="fw-semibold text-dark py-3">
                            <i class="fas fa-user-circle text-muted fs-4 me-2 align-middle"></i>
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>
                        <td class="py-3"><?php echo htmlspecialchars($row['father_name']); ?></td>
                        <td class="py-3"><?php echo $row['dob']; ?></td>
                        <td class="py-3"><?php echo $row['admission_date']; ?></td>
                        <td class="py-3">
                            <button class="btn btn-sm btn-info text-white me-1" onclick='openViewModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)' title="View"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-sm btn-warning text-dark me-1" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, "UTF-8"); ?>)' title="Edit"><i class="fas fa-edit"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_student" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-user-slash fs-1 text-light mb-3"></i>
                            <h5>No students found</h5>
                            <p>Register a new student to see them here.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white text-center pb-4 pt-4 rounded-top-4 border-0 shadow-sm">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Register New Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Student ID</label>
                        <input type="text" name="student_id" class="form-control form-control-lg bg-light" required placeholder="e.g. STD-001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Father's Name</label>
                        <input type="text" name="father_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Date of Birth</label>
                        <input type="date" name="dob" class="form-control form-control-lg" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_student" class="btn btn-primary fw-bold px-4 shadow-sm">Save Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning text-dark text-center pb-4 pt-4 rounded-top-4 border-0 shadow-sm">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <input type="hidden" name="id" id="edit_id">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Student ID</label>
                        <input type="text" name="student_id" id="edit_student_id" class="form-control form-control-lg bg-light" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Father's Name</label>
                        <input type="text" name="father_name" id="edit_father_name" class="form-control form-control-lg" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted">Date of Birth</label>
                        <input type="date" name="dob" id="edit_dob" class="form-control form-control-lg" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_student" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">Update Student</button>
            </div>
        </form>
    </div>
</div>

<!-- View Student Modal -->
<div class="modal fade" id="viewStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-info text-white text-center pb-4 pt-4 rounded-top-4 border-0 shadow-sm">
                <h5 class="modal-title fw-bold"><i class="fas fa-user me-2"></i>Student Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <table class="table table-bordered mb-0">
                    <tbody>
                        <tr><th class="bg-light w-40">System Record ID</th><td id="view_sys_id" class="fw-bold text-muted"></td></tr>
                        <tr><th class="bg-light">Student ID</th><td id="view_student_id" class="fw-bold"></td></tr>
                        <tr><th class="bg-light">Name</th><td id="view_name" class="fw-semibold text-primary"></td></tr>
                        <tr><th class="bg-light">Father's Name</th><td id="view_father_name"></td></tr>
                        <tr><th class="bg-light">Date of Birth</th><td id="view_dob"></td></tr>
                        <tr><th class="bg-light">Admission Date</th><td id="view_admission_date"></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4 w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_student_id').value = data.student_id;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_father_name').value = data.father_name;
    document.getElementById('edit_dob').value = data.dob;
    var editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editModal.show();
}

function openViewModal(data) {
    document.getElementById('view_sys_id').textContent = data.id;
    document.getElementById('view_student_id').textContent = data.student_id;
    document.getElementById('view_name').textContent = data.name;
    document.getElementById('view_father_name').textContent = data.father_name;
    document.getElementById('view_dob').textContent = data.dob;
    document.getElementById('view_admission_date').textContent = data.admission_date;
    var viewModal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
    viewModal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>
