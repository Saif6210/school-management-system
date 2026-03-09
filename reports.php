<?php
require_once 'includes/header.php';
require_once 'db.php';

$current_month = date('F Y');
// Prepare data for Fee Defaulters Report
// Students who do NOT have a record in fees_collection for the current month are defaulters
$defaultersQuery = "SELECT s.name, c.class_name as class, s.student_id as roll_no 
                    FROM students s 
                    LEFT JOIN classes c ON s.class_id = c.id 
                    WHERE s.student_id NOT IN (
                        SELECT roll_no FROM fees_collection WHERE fee_month = '$current_month'
                    )";
$defaultersResult = $conn->query($defaultersQuery);

// Prepare data for Result Analysis Report
$resultsQuery = "
    SELECT 
        s.student_id as roll_no, 
        s.name, 
        c.class_name as class, 
        subj.subject_name as subject, 
        r.obtained_marks, 
        e.total_marks, 
        r.grade as status 
    FROM results r 
    JOIN students s ON r.student_id = s.id 
    LEFT JOIN classes c ON s.class_id = c.id 
    JOIN exams e ON r.exam_id = e.id 
    LEFT JOIN subjects subj ON e.subject_id = subj.id 
    ORDER BY s.class_id ASC, s.student_id ASC
";
$resultsResult = $conn->query($resultsQuery);

// Prepare data for Attendance Report
// Note: Schema uses user_id, user_type='student', date, status
$attendanceQuery = "SELECT s.name, c.class_name as class, a.date, a.status 
                    FROM attendance a 
                    JOIN students s ON a.user_id = s.id 
                    LEFT JOIN classes c ON s.class_id = c.id 
                    WHERE a.user_type = 'student'
                    ORDER BY a.date DESC, s.class_id ASC LIMIT 100";
$attendanceResult = $conn->query($attendanceQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-chart-line me-2 text-primary"></i>Analytics & Reports</h3>
        <p class="text-muted mb-0">Generate comprehensive school reports and summaries.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover overflow-hidden">
            <div class="bg-primary" style="height: 5px; width: 100%;"></div>
            <div class="card-body p-5 text-center">
                <div class="icon-box bg-primary-soft text-primary p-4 rounded-circle d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                    <i class="fas fa-chart-pie fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-3">Attendance Reports</h5>
                <p class="text-muted mb-4 px-2">View aggregate percentage of attendance grouped by class or teacher.</p>
                <button class="btn btn-outline-primary fw-bold rounded-pill px-4 py-2 border-2 shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#attendanceReportModal"><i class="fas fa-file-download me-2"></i>View Report</button>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover overflow-hidden">
            <div class="bg-success" style="height: 5px; width: 100%;"></div>
            <div class="card-body p-5 text-center">
                <div class="icon-box bg-success-soft text-success p-4 rounded-circle d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                    <i class="fas fa-file-invoice-dollar fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-3">Fee Defaulters</h5>
                <p class="text-muted mb-4 px-2">List of all students who have not cleared their dues for the selected term.</p>
                <button class="btn btn-outline-success fw-bold rounded-pill px-4 py-2 border-2 shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#defaultersReportModal"><i class="fas fa-file-download me-2"></i>View Report</button>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover overflow-hidden">
            <div class="bg-warning" style="height: 5px; width: 100%;"></div>
            <div class="card-body p-5 text-center">
                <div class="icon-box bg-warning-soft text-warning p-4 rounded-circle d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                    <i class="fas fa-award fs-1 text-dark"></i>
                </div>
                <h5 class="fw-bold text-dark mb-3">Result Analysis</h5>
                <p class="text-muted mb-4 px-2">Class-wise performance and pass/fail metrics from the latest exams.</p>
                <button class="btn btn-outline-warning text-dark fw-bold rounded-pill px-4 py-2 border-2 shadow-sm w-100" data-bs-toggle="modal" data-bs-target="#resultsReportModal"><i class="fas fa-file-download me-2"></i>View Report</button>
            </div>
        </div>
    </div>
</div>

<style>
.transition-hover { transition: transform 0.2s, box-shadow 0.2s; }
.transition-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<!-- Attendance Report Modal -->
<div class="modal fade" id="attendanceReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-chart-pie me-2"></i>Attendance Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3">Date</th>
                                <th class="py-3">Student Name</th>
                                <th class="py-3">Class</th>
                                <th class="py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendanceResult && $attendanceResult->num_rows > 0): ?>
                                <?php while($row = $attendanceResult->fetch_assoc()): ?>
                                <tr>
                                    <td class="py-3 fw-bold text-dark"><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td class="py-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['class']) ?></td>
                                    <td class="py-3">
                                        <?php if ($row['status'] === 'Present'): ?>
                                            <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Present</span>
                                        <?php elseif ($row['status'] === 'Absent'): ?>
                                            <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm">Absent</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No attendance records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Fee Defaulters Report Modal -->
<div class="modal fade" id="defaultersReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-success text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice-dollar me-2"></i>Fee Defaulters Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3">Student Name</th>
                                <th class="py-3">Roll No</th>
                                <th class="py-3">Class</th>
                                <th class="py-3">Monthly Fee</th>
                                <th class="py-3">Pending Dues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($defaultersResult && $defaultersResult->num_rows > 0): ?>
                                <?php while($row = $defaultersResult->fetch_assoc()): ?>
                                <tr>
                                    <td class="py-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['roll_no']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['class']) ?></td>
                                    <td class="py-3 fw-bold text-dark">Rs. 5,000.00</td>
                                    <td class="py-3 fw-bold text-danger">Rs. 5,000.00 <span class="badge bg-danger ms-2">Unpaid (<?= $current_month ?>)</span></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No fee defaulters found. Awesome!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Result Analysis Report Modal -->
<div class="modal fade" id="resultsReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-warning text-dark border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-award me-2"></i>Result Analysis Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="py-3">Student Name</th>
                                <th class="py-3">Roll No</th>
                                <th class="py-3">Class</th>
                                <th class="py-3">Subject</th>
                                <th class="py-3">Marks</th>
                                <th class="py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultsResult && $resultsResult->num_rows > 0): ?>
                                <?php while($row = $resultsResult->fetch_assoc()): ?>
                                <tr>
                                    <td class="py-3 fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['roll_no']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['class']) ?></td>
                                    <td class="py-3 text-muted"><?= htmlspecialchars($row['subject']) ?></td>
                                    <td class="py-3 fw-bold text-dark"><?= $row['obtained_marks'] ?> / <?= $row['total_marks'] ?></td>
                                    <td class="py-3">
                                        <?php if ($row['status'] === 'F'): ?>
                                            <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm">Fail (F)</span>
                                        <?php else: ?>
                                            <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">Pass (<?= htmlspecialchars($row['status']) ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No result records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
