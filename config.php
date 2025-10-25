<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'APS');


try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
  
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8");
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}


session_start();

date_default_timezone_set('Asia/Manila');

function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}


function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}


function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}


function require_login() {
    if (!is_logged_in()) {
        header('Location: index.html');
        exit();
    }
}
?>