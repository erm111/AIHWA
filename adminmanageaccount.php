
<?php
session_start();
require_once 'conn.php';

// Check if the admin is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_SESSION['admin_id'];
    $new_username = filter_input(INPUT_POST, 'new_username', FILTER_SANITIZE_STRING);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($new_username)) {
        $update_username_query = "UPDATE admins SET username = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_username_query);
        mysqli_stmt_bind_param($stmt, "si", $new_username, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['admin_username'] = $new_username;
            $_SESSION['message'] = "Username updated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating username";
            $_SESSION['message_type'] = "error";
        }
    }

    if (!empty($new_password) && $new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_query = "UPDATE admins SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_password_query);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $admin_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Password updated successfully";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating password";
            $_SESSION['message_type'] = "error";
        }
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match";
        $_SESSION['message_type'] = "error";
    }

    header("Location: adminmanageaccount.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admin Account</title>
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
            max-width: 800px;
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
        form {
            display: flex;
            flex-direction: column;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
        }
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #3498db;
            color: white;
        }
        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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
        .message-box.error { background-color: #e74c3c; }
    </style>
</head>
<body>
<div id="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="adminusers.php"><i class="fas fa-users"></i> Check Users</a>
    <a href="#"><i class="fas fa-pills"></i> Check Drug Inventory</a>
    <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>


    <div class="container">
        <button id="sidebarToggle" onclick="toggleNav()"><i class="fas fa-bars"></i></button>
        <a href="adminlogout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <h1>Manage Admin Account</h1>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="new_username">New Username:</label>
                <input type="text" id="new_username" name="new_username" value="<?php echo $_SESSION['admin_username']; ?>">
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" class="btn">Update Account</button>
        </form>
    </div>

    <div id="messageBox" class="message-box"></div>

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
