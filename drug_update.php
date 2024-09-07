<?php
session_start();
require 'vendor/autoload.php';
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit();
}

session_regenerate_id(true);

function getDrugs($conn, $limit = 10, $offset = 0, $search = '')
{
    $sql = "SELECT * FROM drugs WHERE drug_name LIKE ? ORDER BY drug_id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $search = "%$search%";
    $stmt->bind_param("sii", $search, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTotalDrugs($conn, $search = '')
{
    $sql = "SELECT COUNT(*) FROM drugs WHERE drug_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $search = "%$search%";
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_row()[0];
}

function deleteDrug($conn, $drug_id)
{
    $sql = "DELETE FROM drugs WHERE drug_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $drug_id);
    return $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_drug_id'])) {
        $delete_drug_id = $_POST['delete_drug_id'];
        if (deleteDrug($conn, $delete_drug_id)) {
            $message = "Drug deleted successfully.";
        } else {
            $error = "Error deleting drug: " . $conn->error;
        }
    } elseif (isset($_POST['edit_drug_id'])) {
        $edit_drug_id = $_POST['edit_drug_id'];
        $edit_drug_name = $_POST['edit_drug_name'];
        $edit_price = $_POST['edit_price'];
        $edit_expiry_date = $_POST['edit_expiry_date'];

        $sql = "UPDATE drugs SET drug_name = ?, price = ?, expiry_date = ? WHERE drug_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdsi", $edit_drug_name, $edit_price, $edit_expiry_date, $edit_drug_id);

        if ($stmt->execute()) {
            $message = "Drug updated successfully.";
        } else {
            $error = "Error updating drug: " . $conn->error;
        }
    } elseif (isset($_POST['new_drug_name'])) {
        $new_drug_name = $_POST['new_drug_name'];
        $new_price = $_POST['new_price'];
        $new_quantity = $_POST['new_quantity'];
        $new_expiry_date = $_POST['new_expiry_date'];

        $sql = "INSERT INTO drugs (drug_name, price, quantity, expiry_date, total) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $total = $new_price * $new_quantity;
        $stmt->bind_param("sdiss", $new_drug_name, $new_price, $new_quantity, $new_expiry_date, $total);

        if ($stmt->execute()) {
            $message = "New drug added successfully.";
        } else {
            $error = "Error adding new drug: " . $conn->error;
        }
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$drugs = getDrugs($conn, $limit, $offset, $search);
$totalDrugs = getTotalDrugs($conn, $search);
$totalPages = ceil($totalDrugs / $limit);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drug Inventory Management</title>
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
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="drug_update.php"><i class="fas fa-pills"></i> Drug Update</a>
    </div>

    <button id="sidebarToggle" onclick="toggleNav()"><i class="fas fa-bars"></i></button>

    <div id="main">
        <div class="container-fluid">
            <nav class="navbar navbar-light bg-light">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">Drug Inventory Management</span>
                    <a href="logout.php" class="btn btn-outline-danger logout-btn">Logout</a>
                </div>
            </nav>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col">
                    <button class="btn btn-primary mb-3" onclick="openAddDrugModal()">Add New Drug</button>

                    <form class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search drugs" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                        </div>
                    </form>

                    <h2>Drug Inventory</h2>
                    <div class="mb-3">
                        <label for="sortOrder" class="me-2">Sort by:</label>
                        <select id="sortOrder" class="form-select form-select-sm d-inline-block w-auto">
                            <option value="original">Original Order</option>
                            <option value="alphabetical">Alphabetical</option>
                        </select>
                    </div>

                    <table id="drugTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Drug ID</th>
                                <th>Drug Name</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Expiry Date</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($drugs as $drug): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($drug['drug_id']); ?></td>
                                    <td><?php echo htmlspecialchars($drug['drug_name']); ?></td>
                                    <td><?php echo htmlspecialchars($drug['price']); ?></td>
                                    <td><?php echo htmlspecialchars($drug['quantity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($drug['expiry_date'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($drug['total'] ?? ''); ?></td>
                                    <td>
                                        <a href="issue_drugs.php?drug_id=<?php echo $drug['drug_id']; ?>" class="btn btn-sm btn-primary">Action</a>
                                        <button class="btn btn-sm btn-warning" onclick="editDrug(<?php echo $drug['drug_id']; ?>, '<?php echo addslashes($drug['drug_name']); ?>', <?php echo $drug['price']; ?>, '<?php echo $drug['expiry_date']; ?>')">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteDrug(<?php echo $drug['drug_id']; ?>)">Delete</button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>


                    <div class="form-group">
                        <label for="limit" class="me-2">Records per page:</label>
                        <select id="limit" class="form-select form-select-sm" style="width: auto;" onchange="changeLimit(this.value)">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="editDrugModal" tabindex="-1" aria-labelledby="editDrugModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDrugModalLabel">Edit Drug</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDrugForm" method="POST">
                        <input type="hidden" id="edit_drug_id" name="edit_drug_id">
                        <div class="mb-3">
                            <label for="edit_drug_name" class="form-label">Drug Name</label>
                            <input type="text" class="form-control" id="edit_drug_name" name="edit_drug_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price" name="edit_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="edit_expiry_date" name="edit_expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addDrugModal" tabindex="-1" aria-labelledby="addDrugModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDrugModalLabel">Add New Drug</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addDrugForm" method="POST">
                        <div class="mb-3">
                            <label for="new_drug_name" class="form-label">Drug Name</label>
                            <input type="text" class="form-control" id="new_drug_name" name="new_drug_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="new_price" name="new_price" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="new_quantity" name="new_quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="new_expiry_date" name="new_expiry_date" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Drug</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        var table = $('#drugTable').DataTable({
            responsive: true,
            "pageLength": <?php echo $limit; ?>,
            "searching": false,
            "ordering": true,
            "order": [],
            "info": false,
            "paging": false
        });

        $('#sortOrder').on('change', function() {
            if (this.value === 'alphabetical') {
                table.order([1, 'asc']).draw();
            } else {
                table.order([0, 'asc']).draw();
            }
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

        function changeLimit(limit) {
            window.location.href = `?page=1&limit=${limit}&search=<?php echo urlencode($search); ?>`;
        }

        // function openTransactionModal(drugId, action) {
        //     document.getElementById('drug_id').value = drugId;
        //     document.getElementById('action').value = action;
        //     document.getElementById('transactionModalLabel').textContent = action === 'issue' ? 'Issue Drug' : 'Add Drug';
        //     var modal = new bootstrap.Modal(document.getElementById('transactionModal'));
        //     modal.show();
        // }

        function editDrug(drugId, drugName, price, expiryDate) {
            document.getElementById('edit_drug_id').value = drugId;
            document.getElementById('edit_drug_name').value = drugName;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_expiry_date').value = expiryDate;
            var modal = new bootstrap.Modal(document.getElementById('editDrugModal'));
            modal.show();
        }

        function deleteDrug(drugId) {
            if (confirm('Are you sure you want to delete this drug?')) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_drug_id" value="' + drugId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        function openAddDrugModal() {
            var modal = new bootstrap.Modal(document.getElementById('addDrugModal'));
            modal.show();
        }
    </script>
</body>

</html>