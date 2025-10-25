<?php
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}


if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header('Location: employees_page.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = clean_input($_POST['first_name']);
    $last_name = clean_input($_POST['last_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $position = clean_input($_POST['position']);
    $department = clean_input($_POST['department']);
    $salary = (float)$_POST['salary'];
    $hire_date = $_POST['hire_date'];
    
    if (isset($_POST['employee_id']) && !empty($_POST['employee_id'])) {
       
        $employee_id = (int)$_POST['employee_id'];
        $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, email=?, phone=?, position=?, department=?, salary=?, hire_date=? WHERE id=?");
        $stmt->bind_param("ssssssdsi", $first_name, $last_name, $email, $phone, $position, $department, $salary, $hire_date, $employee_id);
    } else {
    
        $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, phone, position, department, salary, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssds", $first_name, $last_name, $email, $phone, $position, $department, $salary, $hire_date);
    }
    
    $stmt->execute();
    header('Location: employees_page.php');
    exit();
}

$sql = "SELECT * FROM employees ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Attendance Payroll System</title>
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
        
        .header-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .header-right a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .header-right a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h2 {
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-edit {
            background: #4CAF50;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-delete {
            background: #f44336;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>👥 Employee Management</h1>
        <div class="header-right">
           
              <a href="dashboard.php">Dashboard</a>
            <a href="employees_page.php">Employees</a>
            <a href="attendance.php">Attendance</a>
            <a href="payroll.php">Payroll</a>
            <a href="reports.php">Reports</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2> </h2>
            <button class="btn btn-primary" onclick="openModal()">+ Add New Employee</button>
        </div>
        
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Hire Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                <td>₱<?php echo number_format($row['salary'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['hire_date'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-edit" onclick='editEmployee(<?php echo json_encode($row); ?>)'>Edit</button>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this employee?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">No employees found. Click "Add New Employee" to get started.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
 
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Employee</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" id="employee_id" name="employee_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="position">Position *</label>
                        <input type="text" id="position" name="position" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department *</label>
                        <input type="text" id="department" name="department" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary">Salary *</label>
                        <input type="number" id="salary" name="salary" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">Hire Date *</label>
                        <input type="date" id="hire_date" name="hire_date" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Save Employee</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('employeeModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add New Employee';
            document.querySelector('form').reset();
            document.getElementById('employee_id').value = '';
        }
        
        function closeModal() {
            document.getElementById('employeeModal').style.display = 'none';
        }
        
        function editEmployee(employee) {
            document.getElementById('employeeModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            document.getElementById('employee_id').value = employee.id;
            document.getElementById('first_name').value = employee.first_name;
            document.getElementById('last_name').value = employee.last_name;
            document.getElementById('email').value = employee.email;
            document.getElementById('phone').value = employee.phone;
            document.getElementById('position').value = employee.position;
            document.getElementById('department').value = employee.department;
            document.getElementById('salary').value = employee.salary;
            document.getElementById('hire_date').value = employee.hire_date;
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('employeeModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>