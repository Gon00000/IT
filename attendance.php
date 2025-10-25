<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}


$conn = mysqli_connect("localhost", "root", "", "aps");

if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


if(isset($_POST['submit_attendance'])) {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time_in = mysqli_real_escape_string($conn, $_POST['time_in']);
    $time_out = mysqli_real_escape_string($conn, $_POST['time_out']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);

    $hours_worked = 0;
    if($time_in && $time_out) {
        $start = strtotime($time_in);
        $end = strtotime($time_out);
        $hours_worked = round(($end - $start) / 3600, 2);
    }
    
 
    $overtime_hours = 0;
    if($hours_worked > 8) {
        $overtime_hours = $hours_worked - 8;
        $hours_worked = 8;
    }
    
    $query = "INSERT INTO attendance (employee_id, date, time_in, time_out, hours_worked, overtime_hours, status, remarks) 
              VALUES ('$employee_id', '$date', '$time_in', '$time_out', '$hours_worked', '$overtime_hours', '$status', '$remarks')
              ON DUPLICATE KEY UPDATE 
              time_in = '$time_in', 
              time_out = '$time_out', 
              hours_worked = '$hours_worked',
              overtime_hours = '$overtime_hours',
              status = '$status',
              remarks = '$remarks'";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success_msg'] = "Attendance recorded successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: attendance.php");
    exit();
}


if(isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $delete_query = "DELETE FROM attendance WHERE id = '$delete_id'";
    
    if(mysqli_query($conn, $delete_query)) {
        $_SESSION['success_msg'] = "Attendance record deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error deleting record: " . mysqli_error($conn);
    }
    
    header("Location: attendance.php");
    exit();
}

$employees_query = "SELECT * FROM employees WHERE status = 'active' ORDER BY first_name ASC";
$employees_result = mysqli_query($conn, $employees_query);


$attendance_query = "SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name, e.department 
                     FROM attendance a 
                     LEFT JOIN employees e ON a.employee_id = e.id 
                     ORDER BY a.date DESC, a.time_in DESC 
                     LIMIT 100";
$attendance_result = mysqli_query($conn, $attendance_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - APS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .form-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .table-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-half_day { background: #cce5ff; color: #004085; }
        
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Attendance Management System</h1>
        <div class="nav-links">
              <a href="dashboard.php">Dashboard</a>
            <a href="employees_page.php">Employees</a>
            <a href="attendance.php">Attendance</a>
            <a href="payroll.php">Payroll</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php
        if(isset($_SESSION['success_msg'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_msg'] . '</div>';
            unset($_SESSION['success_msg']);
        }
        
        if(isset($_SESSION['error_msg'])) {
            echo '<div class="alert alert-error">' . $_SESSION['error_msg'] . '</div>';
            unset($_SESSION['error_msg']);
        }
        ?>
        
        <div class="form-card">
            <h2>Record Attendance</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="employee_id">Select Employee *</label>
                        <select name="employee_id" id="employee_id" required>
                            <option value="">-- Choose Employee --</option>
                            <?php 
                            mysqli_data_seek($employees_result, 0);
                            while($emp = mysqli_fetch_assoc($employees_result)) { 
                            ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo $emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['department'] . ')'; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" name="date" id="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="time_in">Time In *</label>
                        <input type="time" name="time_in" id="time_in" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_out">Time Out</label>
                        <input type="time" name="time_out" id="time_out">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select name="status" id="status" required>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="absent">Absent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea name="remarks" id="remarks" rows="3" placeholder="Optional notes..."></textarea>
                </div>
                
                <button type="submit" name="submit_attendance" class="btn">Submit Attendance</button>
            </form>
        </div>
        
    