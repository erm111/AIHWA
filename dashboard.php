<?php
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit();
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

require_once 'conn.php';

function readExcelFile($filepath) {
    $spreadsheet = IOFactory::load($filepath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    $inventory_data = [];
    $headers = array_shift($rows); // Remove and store the header row
    
    foreach ($rows as $row) {
        $inventory_data[] = [
            'name' => $row[0],
            'quantity' => $row[1],
            'unit_price' => $row[2],
            'total_value' => $row[3],
            'expiry_date' => $row[4]
        ];
    }
    
    return $inventory_data;
}

$inventory_data = readExcelFile('drug_inventory.xlsx');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIHWA Dashboard</title>
    <link rel="icon" href="path/to/hospital-icon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            transition: margin-left .5s;
        }
        .logout-btn {
            position: absolute;
            top: 10px;
            right: 10px;
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
        #main {
            transition: margin-left .5s;
            padding: 16px;
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
            z-index: 2;
        }
        .container-fluid {
            transition: margin-left .5s;
        }
    </style>
</head>
<body>
<div id="sidebar">
    <a href="javascript:void(0)" class="closebtn" onclick="toggleNav()">×</a>
    <a href="drug_update.php" target="_blank"><i class="fas fa-pills"></i> Drug Update</a>
</div>

    
    <button id="sidebarToggle" onclick="toggleNav()"><i class="fas fa-bars"></i></button>
    
    <div id="main">
        <div class="container-fluid">
            <nav class="navbar navbar-light bg-light">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">AIHWA Dashboard</span>
                    <a href="logout.php" class="btn btn-outline-danger logout-btn">Logout</a>
                </div>
            </nav>
            
            <div class="row mt-4">
                <div class="col">
                    <h2>Pharmacy Store Inventory</h2>
                    <table id="inventoryTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Drug Name</th>
                                <th>Quantity</th>
                                <th>Amount</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_data as $item): ?>
                            <tr>
                                <td><?php echo isset($item['name']) ? htmlspecialchars($item['name']) : ''; ?></td>
                                <td><?php echo isset($item['quantity']) ? htmlspecialchars($item['quantity']) : ''; ?></td>
                                <td><?php echo isset($item['unit_price']) ? htmlspecialchars($item['unit_price']) : ''; ?></td>
                                <td><?php echo isset($item['total_value']) ? htmlspecialchars($item['total_value']) : ''; ?></td>
                                <td><?php echo isset($item['expiry_date']) ? htmlspecialchars($item['expiry_date']) : ''; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                responsive: true
            });
        });

        function toggleNav() {
            var sidebar = document.getElementById("sidebar");
            var main = document.getElementById("main");
            var toggleBtn = document.getElementById("sidebarToggle");
            var body = document.body;

            if (sidebar.style.width === "250px") {
                sidebar.style.width = "0";
                main.style.marginLeft = "0";
                body.style.marginLeft = "0";
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                sidebar.style.width = "250px";
                main.style.marginLeft = "250px";
                body.style.marginLeft = "250px";
                toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
            }
        }
    </script>
</body>
</html>
