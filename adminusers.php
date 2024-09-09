<?php
session_start();
require_once 'conn.php';

// Check if the admin is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch users from the database
$query = "SELECT id, username FROM users";
$result = mysqli_query($conn, $query);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "User deleted successfully";
    $_SESSION['message_type'] = "error";
    header("Location: adminusers.php");
    exit();
}

// Handle user addition
if (isset($_POST['add_user'])) {
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $add_query = "INSERT INTO users (username, password) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $add_query);
    mysqli_stmt_bind_param($stmt, "ss", $new_username, $new_password);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "User added successfully";
    $_SESSION['message_type'] = "success";
    header("Location: adminusers.php");
    exit();
}

// Handle user edit
if (isset($_POST['edit_user'])) {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $edit_query = "UPDATE users SET username = ?, password = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "ssi", $new_username, $new_password, $user_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "User edited successfully";
    $_SESSION['message_type'] = "info";
    header("Location: adminusers.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        tr {
            transition: all 0.3s ease;
        }
        tr:hover {
            background-color: #f5f5f5;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background-color: #2ecc71;
            color: white;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .add-user-form {
            margin-top: 40px;
            padding: 20px;
            background-color: #ecf0f1;
            border-radius: 8px;
        }
        .add-user-form h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .add-user-form input[type="text"],
        .add-user-form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        .add-user-form input[type="text"]:focus,
        .add-user-form input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .add-user-form input[type="submit"] {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .add-user-form input[type="submit"]:hover {
            background-color: #2980b9;
        }
        .message-box {
            position: fixed;
            top: -100px;
            right: 20px;
            padding: 15px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            opacity: 0;
            transition: all 0.5s ease;
        }
        .message-box.show {
            top: 20px;
            opacity: 1;
        }
        .message-box.success { background-color: #2ecc71; }
        .message-box.info { background-color: #3498db; }
        .message-box.error { background-color: #e74c3c; }
        .confirm-dialog {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .confirm-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            text-align: center;
            transform: scale(0.7);
            transition: all 0.3s ease;
        }
        .confirm-dialog.show {
            opacity: 1;
        }
        .confirm-dialog.show .confirm-content {
            transform: scale(1);
        }
        .confirm-buttons button {
            margin: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .confirm-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .modal.show {
            opacity: 1;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 500px;
            transform: scale(0.7);
            transition: all 0.3s ease;
        }
        .modal.show .modal-content {
            transform: scale(1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
        }
        .edit-user-form {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        .form-actions {
            text-align: right;
            margin-top: 20px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        #sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #111;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }
        #sidebar a {
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 18px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }
        #sidebar a:hover {
            color: #f1f1f1;
        }
        #sidebarToggle {
            font-size: 20px;
            cursor: pointer;
            background-color: #111;
            color: white;
            padding: 10px 15px;
            border: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }
        .logout-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
            z-index: 1000;
        }
        .logout-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
<div id="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="#"><i class="fas fa-pills"></i> Check Drug Inventory</a>
    <a href="adminmanageaccount.php"><i class="fas fa-user-cog"></i> Manage Account</a>
    <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


    <div class="container">
        <button id="sidebarToggle" onclick="toggleNav()"><i class="fas fa-bars"></i></button>
        <a href="adminlogout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <h1>User Management</h1>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td>
                    <button class="btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars($user['id']); ?>, '<?php echo htmlspecialchars($user['username']); ?>')">Edit</button>
                    <button class="btn btn-delete" onclick="openDeleteConfirmation(<?php echo htmlspecialchars($user['id']); ?>)">Delete</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="add-user-form">
            <h2>Add New User</h2>
            <form method="POST">
                <input type="text" name="new_username" placeholder="Username" required>
                <input type="password" name="new_password" placeholder="Password" required>
                <input type="submit" name="add_user" value="Add User">
            </form>
        </div>
    </div>

    <div id="messageBox" class="message-box"></div>

    <div id="confirmDialog" class="confirm-dialog">
        <div class="confirm-content">
            <p>Are you sure you want to delete this user?</p>
            <div class="confirm-buttons">
            <button onclick="confirmDelete()">Yes</button>
                <button onclick="closeConfirmDialog()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit User</h2>
            <form method="POST" class="edit-user-form">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="new_username" required>
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password</label>
                    <input type="password" id="edit_password" name="new_password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showMessage(message, type) {
            var messageBox = document.getElementById('messageBox');
            messageBox.textContent = message;
            messageBox.className = 'message-box ' + type;
            messageBox.classList.add('show');
            setTimeout(function() {
                messageBox.classList.remove('show');
            }, 3000);
        }

        function openDeleteConfirmation(userId) {
            var confirmDialog = document.getElementById('confirmDialog');
            confirmDialog.style.display = 'block';
            confirmDialog.dataset.userId = userId;
            setTimeout(() => confirmDialog.classList.add('show'), 10);
        }

        function closeConfirmDialog() {
            var confirmDialog = document.getElementById('confirmDialog');
            confirmDialog.classList.remove('show');
            setTimeout(() => confirmDialog.style.display = 'none', 300);
        }

        function confirmDelete() {
            var userId = document.getElementById('confirmDialog').dataset.userId;
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="delete_user" value="1"><input type="hidden" name="user_id" value="' + userId + '">';
            document.body.appendChild(form);
            form.submit();
        }

        function openEditModal(userId, username) {
            var editModal = document.getElementById('editModal');
            editModal.style.display = 'block';
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            setTimeout(() => editModal.classList.add('show'), 10);
        }

        function closeEditModal() {
            var editModal = document.getElementById('editModal');
            editModal.classList.remove('show');
            setTimeout(() => editModal.style.display = 'none', 300);
        }

        function openNav() {
            document.getElementById("sidebar").style.width = "250px";
            document.getElementById("main").style.marginLeft = "250px";
        }

        function closeNav() {
            document.getElementById("sidebar").style.width = "0";
            document.getElementById("main").style.marginLeft = "0";
        }

        function toggleNav() {
            var sidebar = document.getElementById("sidebar");
            var main = document.getElementById("main");
            var toggleBtn = document.getElementById("sidebarToggle");

            if (sidebar.style.width === "250px") {
                sidebar.style.width = "0";
                main.style.marginLeft = "0";
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                sidebar.style.width = "250px";
                main.style.marginLeft = "250px";
                toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
            }
        }

        // Check for message in session and display it
        <?php
        if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
            echo "showMessage('" . $_SESSION['message'] . "', '" . $_SESSION['message_type'] . "');";
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
    </script>
</body>
</html>
