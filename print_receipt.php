<?php
require_once 'db.php';

$receipt_no = isset($_GET['receipt']) ? $conn->real_escape_string($_GET['receipt']) : '';

if(empty($receipt_no)) {
    die("Invalid Print Request. Missing Receipt Number.");
}

// Fetch Transaction Data
$res_query = $conn->query("SELECT * FROM fees_collection WHERE receipt_no = '$receipt_no'");

if(!$res_query || $res_query->num_rows == 0) {
    die("<h3>No matching Fee Receipt Found in the system.</h3>");
}

$data = $res_query->fetch_assoc();

// School Details (Static config for Print Template)
$school_name = "AlMadina Public Model School";
$school_address = "Main Campus, Chak no.62SB, Sargodha, Pakistan";
$school_phone = "0348-1580379";
$school_email = "info@almadinaschool.edu.pk";
$school_motto = "Knowledge is Light";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?php echo htmlspecialchars($data['receipt_no']); ?></title>
    <!-- Use Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Print Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #198754; /* Success Green for money */
            --dark: #212529;
            --light: #f8f9fa;
            --border: #ced4da;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #e9ecef;
            color: var(--dark);
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }
        .receipt-card {
            background: white;
            width: 148mm; /* A5 width, perfect for receipts */
            min-height: 210mm; /* A5 Height */
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            border-top: 15px solid var(--primary);
        }
        
        /* Header Section */
        .school-header {
            text-align: center;
            border-bottom: 2px dashed var(--border);
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .school-name {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 0 0 5px 0;
            font-weight: 700;
        }
        .school-address {
            font-size: 0.8rem;
            color: #495057;
            margin-bottom: 10px;
        }
        
        /* Title & Receipt Info */
        .receipt-title {
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 25px;
            color: var(--dark);
        }
        
        .receipt-meta {
            display: flex;
            justify-content: space-between;
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }
        .meta-col {
            display: flex;
            flex-direction: column;
        }
        .meta-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .meta-value {
            font-family: 'Space Mono', monospace;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--primary);
        }
        .meta-date {
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Student Grid */
        .student-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .detail-item {
            display: flex;
            align-items: center;
        }
        .detail-label {
            font-weight: 700;
            color: #6c757d;
            width: 130px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .detail-value {
            font-weight: 600;
            color: var(--dark);
            flex: 1;
            border-bottom: 1px dotted var(--border);
            padding-bottom: 3px;
            font-size: 1rem;
        }
        
        /* Payment Summary Block */
        .payment-block {
            border: 2px solid var(--dark);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 40px;
        }
        .pb-header {
            background: var(--dark);
            color: white;
            padding: 10px 15px;
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .pb-body {
            padding: 20px 15px;
        }
        .pb-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        .pb-total {
            display: flex;
            justify-content: space-between;
            border-top: 2px dashed var(--border);
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            padding-top: 60px;
            margin-top: auto;
        }
        .sig-box {
            text-align: center;
        }
        .sig-line {
            width: 150px;
            border-top: 1px solid var(--dark);
            padding-top: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--dark);
            text-transform: uppercase;
        }
        
        /* Footer Msg */
        .footer-msg {
            text-align: center;
            font-size: 0.75rem;
            color: #adb5bd;
            margin-top: 30px;
            font-style: italic;
        }

        /* Print Button Utility */
        .print-btn-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }
        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1rem;
            font-family: inherit;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
            transition: all 0.3s ease;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(25, 135, 84, 0.6);
        }
        
        .watermark-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12rem;
            color: rgba(25, 135, 84, 0.03);
            pointer-events: none;
            z-index: 0;
        }

        /* Essential Print CSS */
        @media print {
            body { 
                background: white; 
                padding: 0;
                margin: 0;
            }
            .receipt-card { 
                box-shadow: none; 
                border-top: 10px solid var(--primary) !important;
                width: 100%;
                padding: 0;
            }
            .print-btn-container { display: none; }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print me-2"></i> Print Official Receipt</button>
    </div>

    <div class="receipt-card">
        <i class="fas fa-wallet watermark-logo"></i>
        
        <div class="school-header" style="position:relative; z-index:1;">
            <h1 class="school-name"><?php echo $school_name; ?></h1>
            <div class="school-address">
                <?php echo $school_address; ?> <br>
                Phone: <?php echo $school_phone; ?>
            </div>
        </div>
        
        <div class="receipt-title">Official Fee Receipt</div>
        
        <div class="receipt-meta" style="position:relative; z-index:1;">
            <div class="meta-col">
                <span class="meta-label">Receipt Number</span>
                <span class="meta-value"><?php echo $data['receipt_no']; ?></span>
            </div>
            <div class="meta-col" style="text-align: right;">
                <span class="meta-label">Payment Date</span>
                <span class="meta-date"><?php echo date('d M, Y', strtotime($data['payment_date'])); ?></span>
                <span class="meta-label" style="margin-top:2px;">Time: <?php echo date('h:i A', strtotime($data['payment_date'])); ?></span>
            </div>
        </div>
        
        <div class="student-details" style="position:relative; z-index:1;">
            <div class="detail-item">
                <span class="detail-label">Student Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['student_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Roll / Reg. No</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['roll_no']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Academic Class</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['class_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Fee Month</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['fee_month']); ?></span>
            </div>
        </div>
        
        <div class="payment-block" style="position:relative; z-index:1;">
            <div class="pb-header">Transaction Details</div>
            <div class="pb-body">
                <div class="pb-row">
                    <span style="font-weight: 600; color: #6c757d;">Monthly Tuition Fee</span>
                    <span style="font-weight: 600;">Rs. <?php echo number_format($data['amount'], 2); ?></span>
                </div>
                
                <div class="pb-row" style="margin-top: 15px;">
                    <span style="font-weight: 600; color: #6c757d;">Payment Mode</span>
                    <span style="font-weight: bold; background: var(--light); padding: 2px 10px; border-radius: 4px; border: 1px solid var(--border);">
                        <?php echo htmlspecialchars($data['payment_method']); ?>
                    </span>
                </div>
                
                <?php if($data['payment_method'] != 'Cash' && !empty($data['bank_name'])): ?>
                <div class="pb-row" style="font-size: 0.85rem; color: #495057; background: #fff3cd; padding: 10px; border-radius: 5px; margin-top: 10px; border: 1px solid #ffe69c;">
                    <div>
                        <strong>Bank Name:</strong> <?php echo htmlspecialchars($data['bank_name']); ?><br>
                        <strong>Ref / Acc:</strong> <?php echo htmlspecialchars($data['account_details']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="pb-total">
                    <span>Total Received</span>
                    <span>Rs. <?php echo number_format($data['amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line">Accountant Sign</div>
            </div>
            <div class="sig-box">
                <div class="sig-line">Parent Sign</div>
            </div>
        </div>
        
        <div class="footer-msg">
            This is an automatically generated receipt. Fees once paid are non-refundable. <br> Thank you for studying at <?php echo $school_name; ?>.
        </div>
        
    </div>
</body>
</html>
