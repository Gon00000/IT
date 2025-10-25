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


$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? $_GET['department'] : '';


$dept_query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department";
$dept_result = mysqli_query($conn, $dept_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - APS</title>
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
        
        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-print {
            background: #28a745;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .report-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 12px 25px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .tab:hover {
            border-color: #667eea;
        }
        
        .report-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        
        .report-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .report-header p {
            color: #666;
            font-size: 14px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
        }
        
        .summary-row {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        @media print {
            .header, .filter-card, .report-tabs, .btn {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .container {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Reports & Analytics</h1>
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

        <div class="filter-card">
            <h3 style="margin-bottom: 15px;">Report Filters</h3>
            <form method="GET" action="reports_php.php">
                <div class="filter-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php while($dept = mysqli_fetch_assoc($dept_result)) { ?>
                                <option value="<?php echo $dept['department']; ?>" 
                                    <?php echo ($department == $dept['department']) ? 'selected' : ''; ?>>
                                    <?php echo $dept['department']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <button type="submit" class="btn">Apply Filters</button>
                <button type="button" onclick="window.print()" class="btn btn-print">🖨️ Print Report</button>
            </form>
        </div>
        
    
        <div class="report-tabs">
            <a href="?type=summary&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&department=<?php echo $department; ?>" 
               class="tab <?php echo ($report_type == 'summary') ? 'active' : ''; ?>">
                Summary Report
            </a>
            <a href="?type=payroll&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&department=<?php echo $department; ?>" 
               class="tab <?php echo ($report_type == 'payroll') ? 'active' : ''; ?>">
                Payroll Report
            </a>
            <a href="?type=attendance&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&department=<?php echo $department; ?>" 
               class="tab <?php echo ($report_type == 'attendance') ? 'active' : ''; ?>">
                Attendance Report
            </a>
            <a href="?type=employee&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&department=<?php echo $department; ?>" 
               class="tab <?php echo ($report_type == 'employee') ? 'active' : ''; ?>">
                Employee Report
            </a>
        </div>

        <?php
     
        if($report_type == 'summary') {
           
            $dept_filter = $department ? "AND e.department = '$department'" : "";
            
            $total_employees = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT COUNT(*) as count FROM employees e WHERE status='active' $dept_filter"))['count'];
            
            $total_attendance = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT COUNT(*) as count FROM attendance a 
                 LEFT JOIN employees e ON a.employee_id = e.id 
                 WHERE a.date BETWEEN '$start_date' AND '$end_date' $dept_filter"))['count'];
            
            $total_payroll = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT SUM(p.net_salary) as total FROM payroll p 
                 LEFT JOIN employees e ON p.employee_id = e.id 
                 WHERE p.pay_period_start BETWEEN '$start_date' AND '$end_date' $dept_filter"))['total'] ?? 0;
            
            $total_hours = mysqli_fetch_assoc(mysqli_query($conn, 
                "SELECT SUM(a.hours_worked) as total FROM attendance a 
                 LEFT JOIN employees e ON a.employee_id = e.id 
                 WHERE a.date BETWEEN '$start_date' AND '$end_date' $dept_filter"))['total'] ?? 0;
        ?>
        
        <div class="report-card">
            <div class="report-header">
                <h2>Summary Report</h2>
                <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
                <?php if($department) { ?>
                    <p>Department: <?php echo $department; ?></p>
                <?php } ?>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <h3>Total Employees</h3>
                    <div class="value"><?php echo $total_employees; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Attendance Records</h3>
                    <div class="value"><?php echo $total_attendance; ?></div>
                </div>
                <div class="stat-box">
                    <h3>Total Hours Worked</h3>
                    <div class="value"><?php echo number_format($total_hours, 2); ?></div>
                </div>
                <div class="stat-box">
                    <h3>Total Payroll</h3>
                    <div class="value">₱<?php echo number_format($total_payroll, 2); ?></div>
                </div>
            </div>
            
            <h3>Department Breakdown</h3>
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Total Hours</th>
                        <th>Total Payroll</th>
                        <th>Avg Salary</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dept_query = "SELECT 
                                    e.department,
                                    COUNT(DISTINCT e.id) as emp_count,
                                    SUM(a.hours_worked) as total_hours,
                                    SUM(p.net_salary) as total_salary
                                   FROM employees e
                                   LEFT JOIN attendance a ON e.id = a.employee_id 
                                       AND a.date BETWEEN '$start_date' AND '$end_date'
                                   LEFT JOIN payroll p ON e.id = p.employee_id 
                                       AND p.pay_period_start BETWEEN '$start_date' AND '$end_date'
                                   WHERE e.status = 'active' $dept_filter
                                   GROUP BY e.department";
                    $dept_result = mysqli_query($conn, $dept_query);
                    
                    $grand_total_sal = 0;
                    while($row = mysqli_fetch_assoc($dept_result)) {
                        $avg_salary = $row['emp_count'] > 0 ? ($row['total_salary'] ?? 0) / $row['emp_count'] : 0;
                        $grand_total_sal += ($row['total_salary'] ?? 0);
                    ?>
                        <tr>
                            <td><?php echo $row['department'] ?? 'Not Assigned'; ?></td>
                            <td><?php echo $row['emp_count']; ?></td>
                            <td><?php echo number_format($row['total_hours'] ?? 0, 2); ?>h</td>
                            <td>₱<?php echo number_format($row['total_salary'] ?? 0, 2); ?></td>
                            <td>₱<?php echo number_format($avg_salary, 2); ?></td>
                        </tr>
                    <?php } ?>
                    <tr class="summary-row">
                        <td colspan="3">TOTAL</td>
                        <td>₱<?php echo number_format($grand_total_sal, 2); ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php } ?>
        
        <?php
     
        if($report_type == 'payroll') {
            $dept_filter = $department ? "AND e.department = '$department'" : "";
            
            $payroll_query = "SELECT 
                                p.*,
                                CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                                e.department,
                                e.position
                              FROM payroll p
                              LEFT JOIN employees e ON p.employee_id = e.id
                              WHERE p.pay_period_start BETWEEN '$start_date' AND '$end_date' $dept_filter
                              ORDER BY p.pay_period_start DESC, e.last_name ASC";
            $payroll_result = mysqli_query($conn, $payroll_query);
        ?>
        
        <div class="report-card">
            <div class="report-header">
                <h2>Payroll Report</h2>
                <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Pay Period</th>
                        <th>Basic Salary</th>
                        <th>OT Pay</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_basic = 0;
                    $total_ot = 0;
                    $total_deduct = 0;
                    $total_net = 0;
                    
                    while($row = mysqli_fetch_assoc($payroll_result)) {
                        $total_basic += $row['basic_salary'] ?? 0;
                        $total_ot += $row['overtime_pay'] ?? 0;
                        $total_deduct += $row['deductions'] ?? 0;
                        $total_net += $row['net_salary'] ?? 0;
                    ?>
                        <tr>
                            <td><?php echo $row['employee_name']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo date('M d', strtotime($row['pay_period_start'])) . ' - ' . date('M d, Y', strtotime($row['pay_period_end'])); ?></td>
                            <td>₱<?php echo number_format($row['basic_salary'] ?? 0, 2); ?></td>
                            <td>₱<?php echo number_format($row['overtime_pay'] ?? 0, 2); ?></td>
                            <td>₱<?php echo number_format($row['deductions'] ?? 0, 2); ?></td>
                            <td><strong>₱<?php echo number_format($row['net_salary'] ?? 0, 2); ?></strong></td>
                            <td><?php echo ucfirst($row['status']); ?></td>
                        </tr>
                    <?php } ?>
                    <tr class="summary-row">
                        <td colspan="4">TOTAL</td>
                        <td>₱<?php echo number_format($total_basic, 2); ?></td>
                        <td>₱<?php echo number_format($total_ot, 2); ?></td>
                        <td>₱<?php echo number_format($total_deduct, 2); ?></td>
                        <td><strong>₱<?php echo number_format($total_net, 2); ?></strong></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php } ?>
        
        <?php
       
        if($report_type == 'attendance') {
            $dept_filter = $department ? "AND e.department = '$department'" : "";
            
            $attendance_query = "SELECT 
                                    CONCAT(e.first_name, ' ', e.last_name) as employee_name,
                                    e.department,
                                    COUNT(a.id) as total_days,
                                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                                    SUM(a.hours_worked) as total_hours,
                                    SUM(a.overtime_hours) as total_overtime
                                 FROM employees e
                                 LEFT JOIN attendance a ON e.id = a.employee_id 
                                     AND a.date BETWEEN '$start_date' AND '$end_date'
                                 WHERE e.status = 'active' $dept_filter
                                 GROUP BY e.id
                                 ORDER BY e.last_name ASC";
            $attendance_result = mysqli_query($conn, $attendance_query);
        ?>
        
        <div class="report-card">
            <div class="report-header">
                <h2>Attendance Report</h2>
                <p>Period: <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Total Hours</th>
                        <th>Overtime</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_present = 0;
                    $grand_absent = 0;
                    $grand_late = 0;
                    $grand_hours = 0;
                    $grand_ot = 0;
                    
                    while($row = mysqli_fetch_assoc($attendance_result)) {
                        $grand_present += $row['present'];
                        $grand_absent += $row['absent'];
                        $grand_late += $row['late'];
                        $grand_hours += $row['total_hours'] ?? 0;
                        $grand_ot += $row['total_overtime'] ?? 0;
                    ?>
                        <tr>
                            <td><?php echo $row['employee_name']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td><?php echo $row['total_days']; ?></td>
                            <td><?php echo $row['present']; ?></td>
                            <td><?php echo $row['absent']; ?></td>
                            <td><?php echo $row['late']; ?></td>
                            <td><?php echo number_format($row['total_hours'] ?? 0, 2); ?>h</td>
                            <td><?php echo number_format($row['total_overtime'] ?? 0, 2); ?>h</td>
                        </tr>
                    <?php } ?>
                    <tr class="summary-row">
                        <td colspan="3">TOTAL</td>
                        <td><?php echo $grand_present; ?></td>
                        <td><?php echo $grand_absent; ?></td>
                        <td><?php echo $grand_late; ?></td>
                        <td><?php echo number_format($grand_hours, 2); ?>h</td>
                        <td><?php echo number_format($grand_ot, 2); ?>h</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php } ?>
        
        <?php
      
        if($report_type == 'employee') {
            $dept_filter = $department ? "AND department = '$department'" : "";
            
            $employee_query = "SELECT 
                                *,
                                CONCAT(first_name, ' ', last_name) as full_name,
                                DATEDIFF(CURDATE(), hire_date) as days_employed
                               FROM employees
                               WHERE status = 'active' $dept_filter
                               ORDER BY hire_date DESC";
            $employee_result = mysqli_query($conn, $employee_query);
        ?>
        
        <div class="report-card">
            <div class="report-header">
                <h2>Employee Report</h2>
                <p>Active Employees as of <?php echo date('M d, Y'); ?></p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Hire Date</th>
                        <th>Days Employed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_salary = 0;
                    $emp_count = 0;
                    
                    while($row = mysqli_fetch_assoc($employee_result)) {
                        $total_salary += $row['salary'] ?? 0;
                        $emp_count++;
                    ?>
                        <tr>
                            <td><?php echo $row['full_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['position']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td>₱<?php echo number_format($row['salary'] ?? 0, 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['hire_date'])); ?></td>
                            <td><?php echo $row['days_employed']; ?> days</td>
                        </tr>
                    <?php } ?>
                    <tr class="summary-row">
                        <td colspan="5">TOTAL (<?php echo $emp_count; ?> employees)</td>
                        <td>₱<?php echo number_format($total_salary, 2); ?></td>
                        <td colspan="2">Avg: ₱<?php echo $emp_count > 0 ? number_format($total_salary / $emp_count, 2) : '0.00'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php } ?>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>
