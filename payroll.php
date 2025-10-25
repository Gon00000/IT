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

if(isset($_POST['generate_payroll'])) {
    $employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
    $pay_period_start = mysqli_real_escape_string($conn, $_POST['pay_period_start']);
    $pay_period_end = mysqli_real_escape_string($conn, $_POST['pay_period_end']);
    $deduction_amount = floatval($_POST['deductions']);
    

    $emp_query = "SELECT salary, hourly_rate FROM employees WHERE id = '$employee_id'";
    $emp_result = mysqli_query($conn, $emp_query);
    $employee = mysqli_fetch_assoc($emp_result);
    

    $att_query = "SELECT 
                    SUM(hours_worked) as total_hours, 
                    SUM(overtime_hours) as total_overtime 
                  FROM attendance 
                  WHERE employee_id = '$employee_id' 
                  AND date BETWEEN '$pay_period_start' AND '$pay_period_end'";
    $att_result = mysqli_query($conn, $att_query);
    $attendance = mysqli_fetch_assoc($att_result);
    
    $total_hours = $attendance['total_hours'] ? floatval($attendance['total_hours']) : 0;
    $overtime_hours = $attendance['total_overtime'] ? floatval($attendance['total_overtime']) : 0;

    $hourly_rate = $employee['hourly_rate'] > 0 ? floatval($employee['hourly_rate']) : 0;
    $basic_salary = floatval($employee['salary']);
    
   
    if($hourly_rate > 0) {
        $basic_salary = $total_hours * $hourly_rate;
   
        $overtime_pay = $overtime_hours * $hourly_rate * 1.5;
    } else {
       
        $calculated_hourly_rate = $basic_salary / (8 * 22);
        $overtime_pay = $overtime_hours * $calculated_hourly_rate * 1.5;
    }
    
  
    $gross_salary = $basic_salary + $overtime_pay;
    $net_salary = $gross_salary - $deduction_amount;
    
   
    $insert_query = "INSERT INTO payroll 
                     (employee_id, pay_period_start, pay_period_end, total_hours, overtime_hours, 
                      basic_salary, overtime_pay, deductions, net_salary, status) 
                     VALUES 
                     ('$employee_id', '$pay_period_start', '$pay_period_end', '$total_hours', '$overtime_hours',
                      '$basic_salary', '$overtime_pay', '$deduction_amount', '$net_salary', 'pending')";
    
    if(mysqli_query($conn, $insert_query)) {
        $_SESSION['success_msg'] = "Payroll generated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: payroll.php");
    exit();
}


if(isset($_POST['update_status'])) {
    $payroll_id = mysqli_real_escape_string($conn, $_POST['payroll_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $payment_date = ($new_status == 'paid') ? date('Y-m-d') : 'NULL';
    
    $update_query = "UPDATE payroll 
                     SET status = '$new_status', 
                         payment_date = " . ($payment_date == 'NULL' ? 'NULL' : "'$payment_date'") . "
                     WHERE id = '$payroll_id'";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['success_msg'] = "Payroll status updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: payroll.php");
    exit();
}


if(isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $delete_query = "DELETE FROM payroll WHERE id = '$delete_id'";
    
    if(mysqli_query($conn, $delete_query)) {
        $_SESSION['success_msg'] = "Payroll record deleted successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . mysqli_error($conn);
    }
    
    header("Location: payroll.php");
    exit();
}


$employees_query = "SELECT * FROM employees WHERE status = 'active' ORDER BY first_name ASC";
$employees_result = mysqli_query($conn, $employees_query);


$payroll_query = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name, 
                  e.department, e.position
                  FROM payroll p
                  LEFT JOIN employees e ON p.employee_id = e.id
                  ORDER BY p.created_at DESC
                  LIMIT 100";
$payroll_result = mysqli_query($conn, $payroll_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management - APS</title>
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
        
        input:focus, select:focus {
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
            min-width: 1200px;
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
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #cce5ff; color: #004085; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 13px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-paid {
            background: #17a2b8;
            color: white;
        }
        
        .btn-paid:hover {
            background: #138496;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Payroll Management System</h1>
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
        
      
        $stats_query = "SELECT 
                        COUNT(*) as total_records,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'paid' THEN net_salary ELSE 0 END) as total_paid
                        FROM payroll";
        $stats_result = mysqli_query($conn, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        ?>
        
     
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Payroll Records</h3>
                <div class="value"><?php echo $stats['total_records'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value" style="color: #ffc107;"><?php echo $stats['pending'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Approved</h3>
                <div class="value" style="color: #17a2b8;"><?php echo $stats['approved'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Paid</h3>
                <div class="value" style="color: #28a745;">₱<?php echo number_format($stats['total_paid'] ?? 0, 2); ?></div>
            </div>
        </div>
        
        
        <div class="form-card">
            <h2>Generate Payroll</h2>
            <form method="POST" action="payroll.php">
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
                                    <?php echo $emp['first_name'] . ' ' . $emp['last_name'] . ' - ' . $emp['position']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="pay_period_start">Pay Period Start *</label>
                        <input type="date" name="pay_period_start" id="pay_period_start" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="pay_period_end">Pay Period End *</label>
                        <input type="date" name="pay_period_end" id="pay_period_end" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="deductions">Deductions (₱)</label>
                        <input type="number" step="0.01" name="deductions" id="deductions" value="0" min="0">
                    </div>
                </div>
                
                <button type="submit" name="generate_payroll" class="btn">Generate Payroll</button>
            </form>
        </div>
        
  
        <div class="table-card">
            <h2 style="margin-bottom: 20px;">Payroll Records</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th>Pay Period</th>
                        <th>Hours</th>
                        <th>Overtime</th>
                        <th>Basic Salary</th>
                        <th>OT Pay</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(mysqli_num_rows($payroll_result) > 0) {
                        while($row = mysqli_fetch_assoc($payroll_result)) { 
                    ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['employee_name']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo date('M d', strtotime($row['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($row['pay_period_end'])); ?></td>
                            <td><?php echo number_format($row['total_hours'] ?? 0, 2); ?>h</td>
                            <td><?php echo number_format($row['overtime_hours'] ?? 0, 2); ?>h</td>
                            <td>₱<?php echo number_format($row['basic_salary'] ?? 0, 2); ?></td>
                            <td>₱<?php echo number_format($row['overtime_pay'] ?? 0, 2); ?></td>
                            <td>₱<?php echo number_format($row['deductions'] ?? 0, 2); ?></td>
                            <td><strong>₱<?php echo number_format($row['net_salary'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($row['status'] == 'pending') { ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payroll_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" name="update_status" class="btn-small btn-approve">Approve</button>
                                    </form>
                                <?php } ?>
                                
                                <?php if($row['status'] == 'approved') { ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payroll_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="status" value="paid">
                                        <button type="submit" name="update_status" class="btn-small btn-paid">Mark Paid</button>
                                    </form>
                                <?php } ?>
                                
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn-small btn-delete">Delete</button>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                        echo '<tr><td colspan="12" style="text-align: center; padding: 30px;">No payroll records found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function confirmDelete(id) {
            if(confirm('Are you sure you want to delete this payroll record?')) {
                window.location.href = 'payroll.php?delete_id=' + id;
            }
        }
        
       
        document.addEventListener('DOMContentLoaded', function() {
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            
            document.getElementById('pay_period_start').value = firstDay.toISOString().split('T')[0];
            document.getElementById('pay_period_end').value = lastDay.toISOString().split('T')[0];
        });
    </script>
</body>
</html>

<?php
mysqli_close($conn);
?>
