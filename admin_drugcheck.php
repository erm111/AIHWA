<?php
session_start();
require_once 'conn.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_username'])) {
    header("Location: adminlogin.php");
    exit();
}

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'drug_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Pagination
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch total number of drugs
$total_query = "SELECT COUNT(*) as total FROM drugs WHERE drug_name LIKE ?";
$stmt = mysqli_prepare($conn, $total_query);
$searchParam = "%$search%";
mysqli_stmt_bind_param($stmt, "s", $searchParam);
mysqli_stmt_execute($stmt);
$total_result = mysqli_stmt_get_result($stmt);
$total_drugs = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_drugs / $records_per_page);

// Fetch drug inventory data
$query = "SELECT * FROM drugs WHERE drug_name LIKE ? ORDER BY $sort $order LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sii", $searchParam, $records_per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$drugs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Function to create sorting links
function sortLink($field, $label) {
    global $sort, $order, $search, $page;
    $newOrder = ($sort === $field && $order === 'ASC') ? 'DESC' : 'ASC';
    $icon = ($sort === $field) ? ($order === 'ASC' ? '▲' : '▼') : '';
    return "<a href='?sort=$field&order=$newOrder&search=$search&page=$page'>$label $icon</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Inventory - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f5f5f5; }
        .container { padding-top: 20px; margin-left: 250px; }
        .search-form { margin-bottom: 20px; }
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
        #sidebar a:hover { color: #3498db; }
        .pagination { text-align: center; margin-top: 20px; }
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
        <h2>Drug Inventory</h2>
        
        <form class="search-form" method="GET">
            <div class="input-field">
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <label for="search">Search Drugs</label>
            </div>
            <button class="btn waves-effect waves-light" type="submit">Search</button>
        </form>

        <table class="striped">
            <thead>
                <tr>
                    <th><?php echo sortLink('drug_name', 'Drug Name'); ?></th>
                    <th><?php echo sortLink('quantity', 'Quantity'); ?></th>
                    <th><?php echo sortLink('expiry_date', 'Expiry Date'); ?></th>
                    <th><?php echo sortLink('price', 'Price'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($drugs as $drug): ?>
                <tr>
                    <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                    <td><?php echo $drug['quantity']; ?></td>
                    <td><?php echo $drug['expiry_date']; ?></td>
                    <td>$<?php echo number_format($drug['price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="btn">Previous</a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="btn">Next</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
