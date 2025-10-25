<?php
require_once 'config.php';
require_login();


$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE status='active'")->fetch_assoc()['count'];
$today_present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date=CURDATE() AND status='present'")->fetch_assoc()['count'];
$pending_payroll = $conn->query("SELECT COUNT(*) as count FROM payroll WHERE status='pending'")->fetch_assoc()['count'];


$recent_attendance = $conn->query("
    SELECT a.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    ORDER BY a.date DESC, a.created_at DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance Payroll System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .navbar a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
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
            text-transform: uppercase;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status.present {
            background: #d4edda;
            color: #155724;
        }
        
        .status.absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status.late {
            background: #fff3cd;
            color: #856404;
        }
        
        .menu-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .menu-link {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .menu-link:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Attendance Payroll System</h1>
        <div class="navbar-right">
            <span>Welcome, <?php echo $_SESSION['username']; ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="menu-links">
            <a href="employees_page.php" class="menu-link">👥 Employees</a>
            <a href="attendance.php" class="menu-link">📋 Attendance</a>
            <a href="payroll.php" class="menu-link">💰 Payroll</a>
            <a href="reports.php" class="menu-link">📊 Reports</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Employees</h3>
                <div class="number"><?php echo $total_employees; ?></div>
            </div>
            <div class="stat-card">
                <h3>Present Today</h3>
                <div class="number"><?php echo $today_present; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Payroll</h3>
                <div class="number"><?php echo $pending_payroll; ?></div>
            </div>
        </div>
        
        <div class="card">
            <h2>Recent Attendance</h2>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_attendance->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['employee_name']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-'; ?></td>
                        <td><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-'; ?></td>
                        <td><?php echo $row['hours_worked']; ?></td>
                        <td><span class="status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>