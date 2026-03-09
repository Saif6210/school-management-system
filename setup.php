<?php
$host = "localhost";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS almadina_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

$conn->select_db("almadina_db");

// 1. Users table (Admin, Teacher, Staff, Student)
$users = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'student', 'accountant') NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active'
)";
if($conn->query($users) === TRUE) echo "Users table created<br>";

// Insert default Admin
$passHash = password_hash('admin', PASSWORD_DEFAULT);
$admin = "INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$passHash', 'admin')";
$conn->query($admin);

// 2. Students Table
$students = "CREATE TABLE IF NOT EXISTS students (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    dob DATE,
    gender ENUM('Male','Female','Other'),
    address TEXT,
    phone VARCHAR(20),
    class_id INT,
    section_id INT,
    admission_date DATE,
    user_id INT
)";
if($conn->query($students) === TRUE) echo "Students table created<br>";

// 3. Teachers Table
$teachers = "CREATE TABLE IF NOT EXISTS teachers (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    qualification VARCHAR(100),
    experience VARCHAR(100),
    subject VARCHAR(100),
    salary DECIMAL(10,2),
    joining_date DATE,
    phone VARCHAR(20),
    user_id INT
)";
$conn->query($teachers);

// 4. Classes Table
$classes = "CREATE TABLE IF NOT EXISTS classes (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL
)";
$conn->query($classes);

// 5. Sections Table
$sections = "CREATE TABLE IF NOT EXISTS sections (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    section_name VARCHAR(10) NOT NULL
)";
$conn->query($sections);

// 6. Subjects Table
$subjects = "CREATE TABLE IF NOT EXISTS subjects (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(50) NOT NULL,
    class_id INT,
    teacher_id INT
)";
$conn->query($subjects);

// 7. Attendance Table (Student & Teacher)
$attendance = "CREATE TABLE IF NOT EXISTS attendance (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, /* References either student or teacher ID or user table */
    user_type ENUM('student', 'teacher') NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Leave') NOT NULL
)";
$conn->query($attendance);

// 8. Exams Table
$exams = "CREATE TABLE IF NOT EXISTS exams (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL, /* Mid Term, Final */
    subject_id INT,
    exam_date DATE,
    total_marks INT,
    passing_marks INT
)";
$conn->query($exams);

// 9. Results Table
$results = "CREATE TABLE IF NOT EXISTS results (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    student_id INT,
    obtained_marks INT,
    grade VARCHAR(5)
)";
$conn->query($results);

// 10. Fees Table
$fees = "CREATE TABLE IF NOT EXISTS fees (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    month VARCHAR(20),
    year INT,
    amount DECIMAL(10,2),
    status ENUM('Paid', 'Pending') DEFAULT 'Pending',
    payment_date DATE
)";
$conn->query($fees);

// 11. Library Table
$library = "CREATE TABLE IF NOT EXISTS library (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_name VARCHAR(100),
    author VARCHAR(100),
    isbn VARCHAR(50),
    status ENUM('Available', 'Issued') DEFAULT 'Available'
)";
$conn->query($library);

$book_issues = "CREATE TABLE IF NOT EXISTS book_issues (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT,
    student_id INT,
    issue_date DATE,
    return_date DATE,
    fine DECIMAL(10,2) DEFAULT 0
)";
$conn->query($book_issues);

// 12. Staff Table
$staff = "CREATE TABLE IF NOT EXISTS staff (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50), /* Accountant, Security, Peon etc */
    salary DECIMAL(10,2),
    phone VARCHAR(20)
)";
$conn->query($staff);

// 13. Timetable Table
$timetable = "CREATE TABLE IF NOT EXISTS timetable (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT,
    section_id INT,
    subject_id INT,
    teacher_id INT,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),
    start_time TIME,
    end_time TIME
)";
$conn->query($timetable);

// 14. Hiring Candidates Table (Admissions/Hiring)
$candidates = "CREATE TABLE IF NOT EXISTS candidates (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    qualification VARCHAR(100),
    experience VARCHAR(100),
    applied_for VARCHAR(50), /* subject */
    status ENUM('Screening', 'Interview', 'Demo', 'Selected', 'Rejected') DEFAULT 'Screening',
    phone VARCHAR(20)
)";
$conn->query($candidates);

echo "<h3>Setup Completed Successfully. <a href='index.php'>Go to Login</a></h3>";
$conn->close();
?>
