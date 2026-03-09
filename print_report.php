<?php
require_once 'db.php';

$exam_id = isset($_GET['exam']) ? (int)$_GET['exam'] : 0;
$roll_no = isset($_GET['roll']) ? $conn->real_escape_string($_GET['roll']) : '';

if($exam_id == 0 || empty($roll_no)) {
    die("Invalid Print Request. Missing Student Roll No or Exam ID.");
}

// Fetch Result Data Form Student_Results
$res_query = $conn->query("SELECT r.*, e.exam_name, e.exam_date 
                           FROM student_results r 
                           JOIN exams e ON r.exam_id = e.id 
                           WHERE r.exam_id = $exam_id AND r.roll_no = '$roll_no'");

if(!$res_query || $res_query->num_rows == 0) {
    die("<h3>No Results Found for this Student in this Exam. Ensure marks are saved.</h3>");
}

$data = $res_query->fetch_assoc();

// School Details (Static config for Print Template)
$school_name = "AlMadina Public Model School";
$school_address = "Main Campus, Chak no.62SB,Sargodha, Pakistan";
$school_phone = "0348-1580379";
$school_email = "info@almadinaschool.edu.pk";
$school_motto = "Knowledge is Light";

// Calculate passing status generically based on total
$pct = $data['percentage'];
$isPass = ($pct >= 40) ? true : false;
$status_text = $isPass ? "PASS" : "FAIL";
$status_color = $isPass ? "#198754" : "#dc3545"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - <?php echo htmlspecialchars($data['student_name']); ?></title>
    <!-- Use Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <!-- FontAwesome for Print Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0d6efd;
            --dark: #212529;
            --light: #f8f9fa;
            --border: #dee2e6;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #e9ecef;
            color: var(--dark);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .report-card {
            background: white;
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 Height */
            padding: 50px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            border-top: 15px solid var(--primary);
        }
        
        /* Header Section */
        .school-header {
            text-align: center;
            border-bottom: 3px double var(--border);
            padding-bottom: 25px;
            margin-bottom: 35px;
        }
        .school-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
            margin: 0 0 5px 0;
            letter-spacing: 1px;
        }
        .school-motto {
            font-size: 1.1rem;
            font-style: italic;
            color: #6c757d;
            margin: 0 0 10px 0;
        }
        .school-contacts {
            font-size: 0.85rem;
            color: #495057;
        }
        
        /* Title */
        .report-title-box {
            text-align: center;
            margin-bottom: 35px;
        }
        .report-title {
            display: inline-block;
            background: var(--dark);
            color: white;
            padding: 8px 30px;
            font-size: 1.2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-radius: 50px;
        }
        
        /* Exam & Student Details Grid */
        .student-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: var(--light);
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 35px;
            border-left: 5px solid var(--primary);
        }
        .detail-item {
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        .detail-label {
            font-weight: 700;
            color: #6c757d;
            width: 120px;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
        .detail-value {
            font-weight: 600;
            color: var(--dark);
            flex: 1;
            border-bottom: 1px dotted #adb5bd;
            padding-bottom: 2px;
        }
        
        /* Marks Table Setup */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 12px 15px;
            text-align: center;
            font-size: 0.95rem;
        }
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        .subject-name {
            text-align: left;
            font-weight: 600;
            width: 40%;
        }
        .total-row td {
            font-weight: 700;
            background: var(--light);
            font-size: 1.05rem;
        }
        
        /* Summary Grid */
        .summary-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px dashed var(--border);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 60px;
        }
        .summary-item {
            text-align: center;
        }
        .s-label {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .s-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .grade-badge {
            background: var(--primary);
            color: white;
            padding: 5px 20px;
            border-radius: 5px;
        }
        .status-badge {
            color: <?php echo $status_color; ?>;
            border-bottom: 3px solid <?php echo $status_color; ?>;
            padding-bottom: 2px;
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 50px;
        }
        .sig-line {
            width: 200px;
            border-top: 2px solid var(--dark);
            text-align: center;
            padding-top: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark);
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
            box-shadow: 0 4px 15px rgba(13, 110, 253, 0.4);
            transition: all 0.3s ease;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.6);
        }
        
        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem;
            font-family: 'Playfair Display', serif;
            color: rgba(0,0,0,0.03);
            white-space: nowrap;
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
            .report-card { 
                box-shadow: none; 
                border-top: 10px solid var(--primary) !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
                width: 100%;
                padding: 0;
            }
            .print-btn-container { display: none; }
        }
    </style>
</head>
<body>

    <div class="print-btn-container">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Report Card</button>
    </div>

    <div class="report-card">
        <div class="watermark"><?php echo $school_name; ?></div>
        
        <div class="school-header" style="position:relative; z-index:1;">
            <h1 class="school-name"><?php echo $school_name; ?></h1>
            <p class="school-motto">"<?php echo $school_motto; ?>"</p>
            <p class="school-contacts">
                <i class="fas fa-map-marker-alt"></i> <?php echo $school_address; ?> &nbsp;|&nbsp; 
                <i class="fas fa-phone"></i> <?php echo $school_phone; ?> &nbsp;|&nbsp; 
                <i class="fas fa-envelope"></i> <?php echo $school_email; ?>
            </p>
        </div>
        
        <div class="report-title-box">
            <span class="report-title">Academic Report Card</span>
        </div>
        
        <div class="student-details">
            <div class="detail-item">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['student_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Exam Session:</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['exam_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Roll Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['roll_no']); ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Class:</span>
                <span class="detail-value"><?php echo htmlspecialchars($data['class_name']); ?></span>
            </div>
            <div class="detail-item" style="grid-column: 1 / -1;">
                <span class="detail-label" style="width: 130px;">Exam Date:</span>
                <span class="detail-value"><?php echo date('F d, Y', strtotime($data['exam_date'])); ?></span>
            </div>
        </div>
        
        <table style="position:relative; z-index:1;">
            <thead>
                <tr>
                    <th class="subject-name">Subject Title</th>
                    <th>Total Marks</th>
                    <th>Obtained Marks</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="subject-name">Urdu Language</td>
                    <td>100</td>
                    <td><?php echo $data['urdu']; ?></td>
                </tr>
                <tr>
                    <td class="subject-name">Islamiat (Islamic Studies)</td>
                    <td>100</td>
                    <td><?php echo $data['islamiat']; ?></td>
                </tr>
                <tr>
                    <td class="subject-name">English Language</td>
                    <td>100</td>
                    <td><?php echo $data['english']; ?></td>
                </tr>
                <tr>
                    <td class="subject-name">Mathematics</td>
                    <td>100</td>
                    <td><?php echo $data['math']; ?></td>
                </tr>
                <tr>
                    <td class="subject-name">General Science</td>
                    <td>100</td>
                    <td><?php echo $data['science']; ?></td>
                </tr>
                <tr>
                    <td class="subject-name">Social Studies</td>
                    <td>100</td>
                    <td><?php echo $data['social_studies']; ?></td>
                </tr>
                <tr class="total-row">
                    <td class="subject-name" style="text-align: right; padding-right: 20px;">GRAND TOTAL</td>
                    <td>600</td>
                    <td style="color: var(--primary);"><?php echo $data['total_obtained']; ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="summary-box" style="position:relative; z-index:1;">
            <div class="summary-item">
                <span class="s-label">Percentage</span>
                <span class="s-value"><?php echo $data['percentage']; ?>%</span>
            </div>
            <div class="summary-item">
                <span class="s-label">Result Status</span>
                <span class="s-value status-badge"><?php echo $status_text; ?></span>
            </div>
            <div class="summary-item">
                <span class="s-label">Final Grade</span>
                <span class="s-value grade-badge"><?php echo $data['grade']; ?></span>
            </div>
        </div>
        
        <div class="signatures">
            <div class="sig-line">Class Teacher Signature</div>
            <div class="sig-line">Principal Signature</div>
            <div class="sig-line">Parent / Guardian Signature</div>
        </div>
        
    </div>
</body>
</html>
