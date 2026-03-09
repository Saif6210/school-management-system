<?php
require_once 'includes/header.php';
require_once 'db.php';

$msg = "";

// Ensure fees_collection table exists
$conn->query("CREATE TABLE IF NOT EXISTS fees_collection (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(20) NOT NULL UNIQUE,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(20) NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    fee_month VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(20) NOT NULL,
    bank_name VARCHAR(100) DEFAULT NULL,
    account_details VARCHAR(255) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Paid',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_monthly_fee (roll_no, fee_month)
)");

// Base Settings
$monthly_fee = 5000; // Fixed fee for demo scale
$current_month = date('F Y');

// Handle Fee Collection Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['collect_fee'])) {
    $c_class = $conn->real_escape_string($_POST['class_name']);
    $roll = $conn->real_escape_string($_POST['roll_no']);
    $s_name = $conn->real_escape_string($_POST['student_name']);
    $amt = (float)$_POST['amount'];
    $method = $conn->real_escape_string($_POST['payment_method']);
    
    $bank = isset($_POST['bank_name']) ? $conn->real_escape_string($_POST['bank_name']) : NULL;
    $acc = isset($_POST['account_details']) ? $conn->real_escape_string($_POST['account_details']) : NULL;
    $phone = isset($_POST['parent_phone']) ? $conn->real_escape_string($_POST['parent_phone']) : '';
    
    // Receipt Generator
    $receipt = "FEE-" . date('Ymd') . "-" . rand(1000, 9999);
    
    // Check if already paid this month
    $chk = $conn->query("SELECT id FROM fees_collection WHERE roll_no='$roll' AND fee_month='$current_month'");
    if($chk->num_rows > 0) {
        $msg = "<div class='alert alert-warning mt-2'><i class='fas fa-exclamation-triangle me-2'></i>Fee already collected for <strong>$s_name</strong> for $current_month!</div>";
    } else {
        $sql = "INSERT INTO fees_collection (receipt_no, student_name, roll_no, class_name, fee_month, amount, payment_method, bank_name, account_details) 
                VALUES ('$receipt', '$s_name', '$roll', '$c_class', '$current_month', $amt, '$method', " . ($bank ? "'$bank'" : "NULL") . ", " . ($acc ? "'$acc'" : "NULL") . ")";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success mt-2'><i class='fas fa-check-circle me-2'></i>Fee Collected Successfully! Receipt: <strong>$receipt</strong></div>";
            
            if (!empty($phone)) {
                $sms_text = "Dear Parent, fee of Rs. " . number_format($amt) . " for $s_name ($current_month) has been received. Receipt: $receipt. Thank you.";
                $msg .= "<div class='alert alert-info mt-2 mb-0 border-0 shadow-sm'><i class='fas fa-sms fs-5 me-2 align-middle'></i><strong>SMS Sent to $phone:</strong> <em>\"$sms_text\"</em></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger mt-2'>Error: " . $conn->error . "</div>";
        }
    }
}

// Analytics Variables
$selected_class = $_GET['class'] ?? '';
$students = [];
$total_classes_students = 5 * 25; // 5 classes * 25 students = 125 Total Students active
$overall_expected = $total_classes_students * $monthly_fee;

$overall_collected = 0;
// Fetch total actually collected this month globally
$ov_q = $conn->query("SELECT SUM(amount) as total FROM fees_collection WHERE fee_month='$current_month'");
if($ov_q) {
    $row = $ov_q->fetch_assoc();
    $overall_collected = $row['total'] ? (float)$row['total'] : 0;
}
$overall_pending = $overall_expected - $overall_collected;


$class_expected = 0;
$class_collected = 0;
$class_pending = 0;
$paid_rolls = [];

// Handle Class Specific Logic building students array
if ($selected_class >= 1 && $selected_class <= 5) {
    $c_name = "Class $selected_class";
    $class_expected = 25 * $monthly_fee; // Since 25 students per class max
    
    // Fetch who already paid this month in this class
    $cq = $conn->query("SELECT roll_no, amount, receipt_no, payment_method, bank_name FROM fees_collection WHERE class_name='$c_name' AND fee_month='$current_month'");
    if($cq){
        while($cr = $cq->fetch_assoc()) {
            $paid_rolls[$cr['roll_no']] = $cr;
            $class_collected += $cr['amount'];
        }
    }
    $class_pending = $class_expected - $class_collected;

    // Generate Student Roster for the Selected Class
    for ($i = 1; $i <= 25; $i++) {
        $roll_str = $c_name . "-R" . str_pad($i, 3, '0', STR_PAD_LEFT);
        $students[] = [
            'roll_no' => $roll_str,
            'name'    => "Student $i ($c_name)",
            'is_paid' => isset($paid_rolls[$roll_str]),
            'details' => isset($paid_rolls[$roll_str]) ? $paid_rolls[$roll_str] : null
        ];
    }
} else {
    // If no class selected, load all recent transactions globally
    $global_trans = $conn->query("SELECT * FROM fees_collection ORDER BY id DESC LIMIT 50");
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h3 class="fw-bold text-dark"><i class="fas fa-money-bill-wave me-2 text-primary"></i>Fee Management</h3>
        <p class="text-muted mb-0">Track Overall Revenue, Class-wise Pending Dues, and Collect Fees. Displaying Month: <strong><?php echo $current_month; ?></strong></p>
    </div>
</div>

<?php if($msg) echo $msg; ?>

<!-- Overall Financial Summaries -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card bg-success text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative h-100 p-2">
            <i class="fas fa-wallet position-absolute text-white opacity-25" style="right: -20px; bottom: -20px; font-size: 140px;"></i>
            <div class="card-body position-relative z-1 d-flex flex-column justify-content-center">
                <small class="text-white-50 text-uppercase fw-bold" style="letter-spacing: 1px;">Overall School Collection (<?php echo $current_month; ?>)</small>
                <h1 class="fw-bold mb-0 mt-2 display-5">Rs. <?php echo number_format($overall_collected, 2); ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-danger text-white border-0 shadow-sm rounded-4 overflow-hidden position-relative h-100 p-2">
            <i class="fas fa-exclamation-circle position-absolute text-white opacity-25" style="right: -20px; bottom: -20px; font-size: 140px;"></i>
            <div class="card-body position-relative z-1 d-flex flex-column justify-content-center">
                <small class="text-white-50 text-uppercase fw-bold" style="letter-spacing: 1px;">Total Global Pending Dues</small>
                <h1 class="fw-bold mb-0 mt-2 display-5">Rs. <?php echo number_format($overall_pending, 2); ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center px-4">
                <h5 class="fw-bold mb-0 text-dark">Analyze Class Deficit & Collect Fees</h5>
            </div>
            <div class="card-body p-4 p-md-4">
                <form method="GET" action="fees.php" class="row g-4 align-items-end mb-4">
                    <div class="col-md-8">
                        <label class="form-label fw-bold text-muted text-uppercase" style="letter-spacing: 1px; font-size: 0.8rem;">Select Target Class to View Student Dues</label>
                        <select name="class" class="form-select form-select-lg bg-light text-dark fw-semibold shadow-sm" required onchange="this.form.submit()">
                            <option value="">Choose Class (Overview Mode)...</option>
                            <option value="1" <?php if($selected_class == '1') echo 'selected'; ?>>Class 1</option>
                            <option value="2" <?php if($selected_class == '2') echo 'selected'; ?>>Class 2</option>
                            <option value="3" <?php if($selected_class == '3') echo 'selected'; ?>>Class 3</option>
                            <option value="4" <?php if($selected_class == '4') echo 'selected'; ?>>Class 4</option>
                            <option value="5" <?php if($selected_class == '5') echo 'selected'; ?>>Class 5</option>
                        </select>
                    </div>
                </form>

                <?php if($selected_class): ?>
                    <!-- Target Class Summary Mini-->
                    <div class="row g-3 pt-3 border-top mt-1 mb-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 text-center border">
                                <small class="text-muted fw-bold text-uppercase d-block mb-1">Class <?php echo $selected_class; ?> Expected</small>
                                <h4 class="fw-bold mb-0 text-dark">Rs. <?php echo number_format($class_expected); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-success-soft rounded-3 text-center border border-success border-opacity-25">
                                <small class="text-success fw-bold text-uppercase d-block mb-1">Class Collected</small>
                                <h4 class="fw-bold mb-0 text-success">Rs. <?php echo number_format($class_collected); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-danger-soft rounded-3 text-center border border-danger border-opacity-25">
                                <small class="text-danger fw-bold text-uppercase d-block mb-1">Class Pending</small>
                                <h4 class="fw-bold mb-0 text-danger">Rs. <?php echo number_format($class_pending); ?></h4>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-bottom pb-3 pt-4 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="fas fa-list me-2 text-primary"></i>
            <?php echo $selected_class ? "Class $selected_class Detailed Roster ($current_month)" : "Recent Global Transactions ($current_month)"; ?>
        </h5>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 px-3">Status/Receipt</th>
                        <th class="py-3">Student Identity</th>
                        <th class="py-3">Monthly Charge</th>
                        <th class="py-3">Payment Info</th>
                        <th class="py-3 text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($selected_class && !empty($students)): ?>
                        <!-- Class Roster Mode (Shows who paid vs pending) -->
                        <?php foreach($students as $s): ?>
                            <tr class="<?php echo $s['is_paid'] ? 'table-success-soft border-bottom' : ''; ?>">
                                <td class="py-3 px-3">
                                    <?php if($s['is_paid']): ?>
                                        <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm mb-1"><i class="fas fa-check-circle me-1"></i> PAID</span><br>
                                        <small class="text-muted fw-bold letter-spacing-1"><?php echo $s['details']['receipt_no']; ?></small>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-2 rounded-pill shadow-sm"><i class="fas fa-exclamation-circle me-1"></i> PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-soft rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-primary"></i>
                                        </div>
                                        <div>
                                            <?php echo htmlspecialchars($s['name']); ?><br>
                                            <span class="badge bg-secondary fw-normal letter-spacing-1 mt-1"><?php echo $s['roll_no']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 fw-bold text-dark fs-5">Rs. <?php echo number_format($monthly_fee); ?></td>
                                <td class="py-3">
                                    <?php if($s['is_paid']): ?>
                                        <div class="text-success fw-bold"><i class="fas fa-money-bill-wave me-1"></i> Received <?php echo number_format($s['details']['amount']); ?></div>
                                        <small class="text-muted fw-bold text-uppercase"><?php echo htmlspecialchars($s['details']['payment_method']); ?> 
                                        <?php if($s['details']['bank_name']) echo "- " . htmlspecialchars($s['details']['bank_name']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted fs-6 fst-italic">Awaiting payment...</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-end px-3">
                                    <?php if($s['is_paid']): ?>
                                        <a href="print_receipt.php?receipt=<?php echo urlencode($s['details']['receipt_no']); ?>" target="_blank" class="btn border btn-light text-primary fw-bold shadow-sm rounded-pill px-4"><i class="fas fa-print me-2"></i> Receipt</a>
                                    <?php else: ?>
                                        <button class="btn btn-primary fw-bold shadow-sm rounded-pill px-4" onclick='openCollectModal(<?php echo htmlspecialchars(json_encode(["roll_no" => $s["roll_no"], "name" => $s["name"], "selected_class" => "Class " . $selected_class]), ENT_QUOTES, "UTF-8"); ?>)'><i class="fas fa-hand-holding-usd me-2"></i> Collect</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <!-- Global Overview Mode (Latest transactions of ANY class) -->
                        <?php if($global_trans && $global_trans->num_rows > 0): while($gbl = $global_trans->fetch_assoc()): ?>
                            <tr>
                                <td class="py-3 px-3">
                                    <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm mb-1"><i class="fas fa-check-circle me-1"></i> PAID</span><br>
                                    <small class="text-muted fw-bold letter-spacing-1"><?php echo $gbl['receipt_no']; ?></small>
                                </td>
                                <td class="fw-bold text-dark py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-soft rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user-check text-primary"></i>
                                        </div>
                                        <div>
                                            <?php echo htmlspecialchars($gbl['student_name']); ?><br>
                                            <span class="badge bg-secondary fw-normal letter-spacing-1 mt-1"><?php echo $gbl['class_name']; ?> | <?php echo $gbl['roll_no']; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-muted fs-6"><i class="far fa-calendar-alt me-1"></i> <?php echo $gbl['fee_month']; ?></td>
                                <td class="py-3">
                                    <div class="text-success fw-bold"><i class="fas fa-money-bill-wave me-1"></i> Rs. <?php echo number_format($gbl['amount']); ?></div>
                                    <small class="text-muted fw-bold text-uppercase"><?php echo htmlspecialchars($gbl['payment_method']); ?> <?php if($gbl['bank_name']) echo " - " . htmlspecialchars($gbl['bank_name']); ?></small>
                                </td>
                                <td class="py-3 text-end px-3">
                                    <a href="print_receipt.php?receipt=<?php echo urlencode($gbl['receipt_no']); ?>" target="_blank" class="btn btn-sm border btn-light text-primary fw-bold shadow-sm rounded-pill px-4"><i class="fas fa-print me-2"></i> Receipt</a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-file-invoice-dollar fs-1 text-light mb-3 d-block"></i>
                                    <h5 class="fw-bold text-dark">No Fees Collected Yet</h5>
                                    <p>Select a class above to load students and begin collecting fees for <?php echo $current_month; ?>.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Shared Collection Modal -->
<div class="modal fade" id="collectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="fees.php" class="modal-content border-0 shadow rounded-4 text-start">
            <div class="modal-header bg-primary text-white border-0 pt-4 pb-3 rounded-top-4">
                <h5 class="modal-title fw-bold" id="collectModalTitle"><i class="fas fa-receipt me-2"></i>Collect Fee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 p-md-5">
                <input type="hidden" name="class_name" id="modal_class_name" value="">
                <input type="hidden" name="roll_no" id="modal_roll_no" value="">
                <input type="hidden" name="student_name" id="modal_student_name" value="">
                
                <div class="p-3 bg-light rounded-3 text-center border mb-4">
                    <small class="text-muted text-uppercase fw-bold d-block mb-1">Fee Amount for <?php echo $current_month; ?></small>
                    <h3 class="fw-bold mb-0 text-dark">Rs. <?php echo number_format($monthly_fee); ?></h3>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Amount Being Collected (Rs.)</label>
                    <input type="number" class="form-control form-control-lg bg-light fw-bold" name="amount" value="<?php echo $monthly_fee; ?>" required readonly>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Parent's Phone Number (For SMS)</label>
                    <input type="text" class="form-control form-control-lg bg-white shadow-sm border-primary border-opacity-50" name="parent_phone" placeholder="e.g. 03001234567" required>
                    <small class="text-primary mt-2 d-block fw-semibold"><i class="fas fa-comment-dots me-1"></i>An automated fee receipt SMS will be sent to this number.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-muted">Payment Method</label>
                    <select class="form-select form-select-lg bg-light fw-bold text-dark" name="payment_method" id="payMethod" onchange="toggleBankDetails(this.value)" required>
                        <option value="Cash">💵 Cash Collection</option>
                        <option value="Bank Account">🏦 Bank Account Transfer</option>
                        <option value="Cheque">📜 Bank Cheque</option>
                    </select>
                </div>
                
                <div id="bankDetails" style="display: none;" class="p-3 border border-info rounded-3 bg-info-soft mb-3">
                    <h6 class="fw-bold text-info mb-3"><i class="fas fa-university me-2"></i>Bank Details</h6>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted fs-xs">Bank Name</label>
                        <input type="text" class="form-control shadow-sm" name="bank_name" id="bankName" placeholder="e.g. Meezan Bank / HBL">
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-muted fs-xs">Account Name or Cheque No</label>
                        <input type="text" class="form-control shadow-sm" name="account_details" id="accDetails" placeholder="e.g. Ali Khan Acc: 0142... / Chq: #9822">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 pt-0">
                <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="collect_fee" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="fas fa-save me-2"></i>Confirm & Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCollectModal(data) {
    document.getElementById('modal_class_name').value = data.selected_class;
    document.getElementById('modal_roll_no').value = data.roll_no;
    document.getElementById('modal_student_name').value = data.name;
    document.getElementById('collectModalTitle').innerHTML = '<i class="fas fa-receipt me-2"></i>Collect Fee: ' + data.name;
    
    // Reset inputs
    document.getElementById('payMethod').value = 'Cash';
    toggleBankDetails('Cash');
    
    var modal = new bootstrap.Modal(document.getElementById('collectModal'));
    modal.show();
}

function toggleBankDetails(method) {
    const bankSection = document.getElementById('bankDetails');
    const bNameReq = document.getElementById('bankName');
    const aDetailReq = document.getElementById('accDetails');
    
    if (method === 'Bank Account' || method === 'Cheque') {
        bankSection.style.display = 'block';
        bNameReq.setAttribute('required', 'required');
        aDetailReq.setAttribute('required', 'required');
    } else {
        bankSection.style.display = 'none';
        bNameReq.removeAttribute('required');
        aDetailReq.removeAttribute('required');
        bNameReq.value = '';
        aDetailReq.value = '';
    }
}
</script>

<style>
.bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.3) }
.table-success-soft { background-color: rgba(25, 135, 84, 0.03); }
.letter-spacing-1 { letter-spacing: 1px; }
.fs-xs { font-size: 0.75rem; }
</style>

<?php require_once 'includes/footer.php'; ?>
