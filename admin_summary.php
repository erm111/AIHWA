<?php
session_start();
require_once 'conn.php';

// Check if the admin is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php");
    exit();
}

// Fetch drug inventory data
$query = "SELECT * FROM drugs";
$result = mysqli_query($conn, $query);
$drugs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Calculate summary statistics
$total_drugs = count($drugs);
$total_quantity = array_sum(array_column($drugs, 'quantity'));
$total_value = array_sum(array_map(function($drug) {
    return $drug['price'] * $drug['quantity'];
}, $drugs));

// Handle search for low stock drugs
$low_stock_search = isset($_GET['low_stock_search']) ? $_GET['low_stock_search'] : '';
$low_stock = array_filter($drugs, function($drug) use ($low_stock_search) {
    return $drug['quantity'] < 10 && (empty($low_stock_search) || stripos($drug['drug_name'], $low_stock_search) !== false);
});

// Handle search for expiring soon drugs
$expiring_search = isset($_GET['expiring_search']) ? $_GET['expiring_search'] : '';
$expiring_soon = array_filter($drugs, function($drug) use ($expiring_search) {
    $expiry_date = new DateTime($drug['expiry_date']);
    $today = new DateTime();
    $diff = $today->diff($expiry_date);
    return $diff->days <= 30 && $diff->invert == 0 && (empty($expiring_search) || stripos($drug['drug_name'], $expiring_search) !== false);
});

// Get most used drugs
$most_used_query = "SELECT drugs.drug_name, COUNT(drug_transactions.drug_id) as usage_count 
                    FROM drug_transactions 
                    JOIN drugs ON drug_transactions.drug_id = drugs.drug_id
                    WHERE transaction_type = 'issued' 
                    GROUP BY drug_transactions.drug_id 
                    ORDER BY usage_count DESC 
                    LIMIT 5";
$most_used_result = mysqli_query($conn, $most_used_query);
$most_used_drugs = mysqli_fetch_all($most_used_result, MYSQLI_ASSOC);

// Get recently added drugs
$recently_added_query = "SELECT drugs.drug_name, MAX(drug_transactions.transaction_date) as added_date 
                         FROM drug_transactions 
                         JOIN drugs ON drug_transactions.drug_id = drugs.drug_id
                         WHERE transaction_type = 'added' 
                         GROUP BY drug_transactions.drug_id 
                         ORDER BY added_date DESC 
                         LIMIT 5";
$recently_added_result = mysqli_query($conn, $recently_added_query);
$recently_added_drugs = mysqli_fetch_all($recently_added_result, MYSQLI_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Summary - Drug Inventory</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            padding-top: 20px;
            margin-left: 250px;
        }
        .card-panel {
            border-radius: 8px;
        }
        .summary-card {
            text-align: center;
        }
        .summary-card i {
            font-size: 48px;
            margin-bottom: 10px;
        }
        table {
            background-color: white;
        }
        #sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #2c3e50;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
        }
        #sidebar a {
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 18px;
            color: #ecf0f1;
            display: block;
            transition: 0.3s;
        }
        #sidebar a:hover {
            color: #3498db;
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <a href="admindashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="adminusers.php"><i class="fas fa-users"></i> Check Users</a>
        <a href="admin_drugcheck.php"><i class="fas fa-pills"></i> Check Drug Inventory</a>
        <a href="adminmanageaccount.php"><i class="fas fa-user-cog"></i> Manage Account</a>
        <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="container">
        <h2 class="center-align">Drug Inventory Summary</h2>

        <div class="row">
            <div class="col s12 m4">
                <div class="card-panel summary-card">
                    <i class="material-icons">medication</i>
                    <h4><?php echo $total_drugs; ?></h4>
                    <p>Total Drugs</p>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card-panel summary-card">
                    <i class="material-icons">inventory_2</i>
                    <h4><?php echo $total_quantity; ?></h4>
                    <p>Total Quantity</p>
                </div>
            </div>
            <div class="col s12 m4">
                <div class="card-panel summary-card">
                    <i class="material-icons">attach_money</i>
                    <h4>$<?php echo number_format($total_value, 2); ?></h4>
                    <p>Total Inventory Value</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col s12 m6">
                <h4>Low Stock Drugs</h4>
                <form action="" method="GET">
                    <div class="input-field">
                        <input type="text" id="low_stock_search" name="low_stock_search" value="<?php echo htmlspecialchars($low_stock_search); ?>">
                        <label for="low_stock_search">Search Low Stock Drugs</label>
                        <button class="btn waves-effect waves-light" type="submit">Search</button>
                    </div>
                </form>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $drug): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                            <td><?php echo $drug['quantity']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col s12 m6">
                <h4>Expiring Soon</h4>
                <form action="" method="GET">
                    <div class="input-field">
                        <input type="text" id="expiring_search" name="expiring_search" value="<?php echo htmlspecialchars($expiring_search); ?>">
                        <label for="expiring_search">Search Expiring Drugs</label>
                        <button class="btn waves-effect waves-light" type="submit">Search</button>
                    </div>
                </form>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiring_soon as $drug): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                            <td><?php echo $drug['expiry_date']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <h4>Most Used Drugs</h4>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Usage Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($most_used_drugs as $drug): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                            <td><?php echo $drug['usage_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col s12">
                <h4>Recently Added Drugs</h4>
                <table class="striped">
                    <thead>
                        <tr>
                            <th>Drug Name</th>
                            <th>Added Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recently_added_drugs as $drug): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                            <td><?php echo $drug['added_date']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>