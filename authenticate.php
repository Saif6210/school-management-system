<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password, role FROM users WHERE username = '$username' AND status = 'active'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['error_msg'] = "Invalid password.";
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['error_msg'] = "User not found or inactive.";
        header("Location: index.php");
        exit();
    }
}
?>
