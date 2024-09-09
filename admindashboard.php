
<!DOCTYPE html>
<html lang="en">
<?php
session_start();


// Check if the admin is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php");
    exit();
}

$timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: adminlogin.php?timeout=1");
    exit();
}

// Update last activity time stamp
$_SESSION['last_activity'] = time();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        #sidebar {
            height: 100%;
            width: 250px;
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
        #main {
            transition: margin-left .5s;
            padding: 16px;
            margin-left: 250px;
        }
        #openNav {
            font-size: 20px;
            cursor: pointer;
            background-color: #111;
            color: white;
            padding: 10px 15px;
            border: none;
        }
        .logout-btn {
    position: absolute;
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

@media screen and (max-width: 768px) {
    .logout-btn {
        top: 5px;
        right: 5px;
        padding: 8px 12px;
        font-size: 12px;
    }
}

@media screen and (max-width: 480px) {
    .logout-btn {
        position: fixed;
        top: auto;
        bottom: 10px;
        right: 10px;
        padding: 10px;
        font-size: 12px;
    }
}

        @media screen and (max-height: 450px) {
            #sidebar {padding-top: 15px;}
            #sidebar a {font-size: 18px;}
        }
    </style>
</head>
</head>
<body>
<div id="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">×</a>
    <a href="adminusers.php" target="_blank"><i class="fas fa-users"></i> Check Users</a>
    <a href="admin_drugcheck.php"><i class="fas fa-pills"></i> Check Drug Inventory</a>
    <a href="adminmanageaccount.php" target="_blank"><i class="fas fa-user-cog"></i> Manage Account</a>
    <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div id="main">
    <button id="sidebarToggle" onclick="toggleNav()"><i class="fas fa-bars"></i></button>
    <a href="adminlogout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <h2>AIHWA Admin Dashboard</h2>
    <!-- Add your main content here -->
</div>
      <script>
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
</script>


</body>
</html>
