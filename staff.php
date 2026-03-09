<?php
require_once 'includes/header.php';
require_once 'db.php';

$success_msg = '';
$error_msg = '';

$conn->query("CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    contact VARCHAR(50) NOT NULL
)");

// Ensure 'contact' column exists if the table was created previously without it
$check_column = $conn->query("SHOW COLUMNS FROM staff LIKE 'contact'");
if ($check_column && $check_column->num_rows == 0) {
    $conn->query("ALTER TABLE staff ADD COLUMN contact VARCHAR(50) NOT NULL AFTER salary");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff'])) {
        $name = $conn->real_escape_string($_POST['name']);
        $role = $conn->real_escape_string($_POST['role']);
        $salary = (float)$_POST['salary'];
        $contact = $conn->real_escape_string($_POST['contact']);
        
        if ($conn->query("INSERT INTO staff (name, role, salary, contact) VALUES ('$name', '$role', $salary, '$contact')")) {
            $success_msg = "Staff added successfully!";
        } else {
            $error_msg = "Failed to add staff member.";
        }
    } elseif (isset($_POST['edit_staff'])) {
        $id = (int)$_POST['staff_id'];
        $name = $conn->real_escape_string($_POST['name']);
        $role = $conn->real_escape_string($_POST['role']);
        $salary = (float)$_POST['salary'];
        $contact = $conn->real_escape_string($_POST['contact']);
        
        if ($conn->query("UPDATE staff SET name='$name', role='$role', salary=$salary, contact='$contact' WHERE id=$id")) {
            $success_msg = "Staff updated successfully!";
        } else {
            $error_msg = "Failed to update staff member.";
        }
    } elseif (isset($_POST['delete_staff'])) {
        $id = (int)$_POST['staff_id'];
        if ($conn->query("DELETE FROM staff WHERE id=$id")) {
            $success_msg = "Staff deleted successfully!";
        } else {
            $error_msg = "Failed to delete staff member.";
        }
    }
}
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-users-cog me-2 text-primary"></i>Non-Teaching Staff</h3>
        <p class="text-muted mb-0">Manage security, accountants, and other school personnel.</p>
    </div>
    <button class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#addStaffModal">
        <i class="fas fa-user-plus me-2"></i>Add Staff
    </button>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add Staff Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">Select Role...</option>
                        <option value="Security">Security</option>
                        <option value="Accountant">Accountant</option>
                        <option value="Clerk">Clerk</option>
                        <option value="Janitor">Janitor</option>
                        <option value="Librarian">Librarian</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Salary (Rs.)</label>
                    <input type="number" name="salary" class="form-control" step="0.01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted">Contact Info</label>
                    <input type="text" name="contact" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_staff" class="btn btn-primary fw-bold px-4 shadow-sm">Add Staff</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">Name</th>
                        <th class="py-3">Role</th>
                        <th class="py-3">Salary</th>
                        <th class="py-3">Contact</th>
                        <th class="py-3 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM staff ORDER BY id DESC");
                    if ($result && $result->num_rows > 0):
                        while ($row = $result->fetch_assoc()):
                            $role_icon = 'fa-user';
                            $role_color = 'bg-secondary';
                            switch (strtolower($row['role'])) {
                                case 'security': $role_icon = 'fa-user-shield'; $role_color = 'bg-dark'; break;
                                case 'accountant': $role_icon = 'fa-calculator'; $role_color = 'bg-info text-dark'; break;
                                case 'clerk': $role_icon = 'fa-keyboard'; $role_color = 'bg-primary'; break;
                                case 'janitor': $role_icon = 'fa-broom'; $role_color = 'bg-warning text-dark'; break;
                                case 'librarian': $role_icon = 'fa-book-reader'; $role_color = 'bg-success'; break;
                            }
                    ?>
                    <tr>
                        <td class="py-3 fw-bold text-dark"><i class="fas <?= $role_icon ?> text-muted me-2 fs-5 align-middle"></i><?= htmlspecialchars($row['name']) ?></td>
                        <td class="py-3"><span class="badge <?= $role_color ?> px-3 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($row['role']) ?></span></td>
                        <td class="py-3 fw-bold text-dark">Rs. <?= number_format($row['salary'], 2) ?></td>
                        <td class="py-3 text-muted"><?= htmlspecialchars($row['contact']) ?></td>
                        <td class="py-3 text-end">
                            <button class="btn btn-sm btn-light border rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editStaffModal<?= $row['id'] ?>"><i class="fas fa-edit text-primary"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars($row['name']) ?>?');">
                                <input type="hidden" name="staff_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_staff" class="btn btn-sm btn-light border rounded-circle"><i class="fas fa-trash text-danger"></i></button>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- Edit Staff Modal -->
                    <div class="modal fade" id="editStaffModal<?= $row['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" class="modal-content border-0 shadow rounded-4 text-start">
                                <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                                    <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Staff Member</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4 p-md-5">
                                    <input type="hidden" name="staff_id" value="<?= $row['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Name</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Role</label>
                                        <select name="role" class="form-select" required>
                                            <option value="Security" <?= $row['role'] == 'Security' ? 'selected' : '' ?>>Security</option>
                                            <option value="Accountant" <?= $row['role'] == 'Accountant' ? 'selected' : '' ?>>Accountant</option>
                                            <option value="Clerk" <?= $row['role'] == 'Clerk' ? 'selected' : '' ?>>Clerk</option>
                                            <option value="Janitor" <?= $row['role'] == 'Janitor' ? 'selected' : '' ?>>Janitor</option>
                                            <option value="Librarian" <?= $row['role'] == 'Librarian' ? 'selected' : '' ?>>Librarian</option>
                                            <option value="Other" <?= $row['role'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Salary (Rs.)</label>
                                        <input type="number" name="salary" class="form-control" step="0.01" value="<?= htmlspecialchars($row['salary']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold text-muted">Contact Info</label>
                                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($row['contact']) ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="edit_staff" class="btn btn-primary fw-bold px-4 shadow-sm">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No staff members found. Click "Add Staff" to create one.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
