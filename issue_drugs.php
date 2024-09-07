<?php
session_start();
require 'vendor/autoload.php';
require_once 'conn.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: userlogin.php");
    exit();
}

$drug_id = isset($_GET['drug_id']) ? intval($_GET['drug_id']) : 0;

// Fetch drug details
$sql = "SELECT * FROM drugs WHERE drug_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $drug_id);
$stmt->execute();
$drug = $stmt->get_result()->fetch_assoc();

// Handle form submission for issue/add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $action = $_POST['action'];

    // Perform the transaction (issue or add)
    $conn->begin_transaction();

    try {
        $sql = "UPDATE drugs SET quantity = quantity " . ($action === 'issue' ? '-' : '+') . " ? WHERE drug_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quantity, $drug_id);
        $stmt->execute();

        $sql = "INSERT INTO drug_transactions (drug_id, transaction_type, quantity, transaction_date) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $drug_id, $action, $quantity);
        $stmt->execute();

        $conn->commit();
        $message = "Drug " . ($action === 'issue' ? 'issued' : 'added') . " successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Pagination
$records_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Fetch total number of transactions
$total_sql = "SELECT COUNT(*) as total FROM drug_transactions WHERE drug_id = ?";
$total_stmt = $conn->prepare($total_sql);
$total_stmt->bind_param("i", $drug_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_records = $total_result['total'];

// Fetch paginated transaction history
$sql = "SELECT * FROM drug_transactions WHERE drug_id = ? ORDER BY transaction_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $drug_id, $records_per_page, $offset);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_pages = ceil($total_records / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue/Add Drug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <button type="button" class="btn btn-info mb-3" data-bs-toggle="modal" data-bs-target="#summaryModal">
            Summarize Transactions
        </button>

        <h1>Issue/Add Drug: <?php echo htmlspecialchars($drug['drug_name']); ?></h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="quantity" class="form-label">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" required>
            </div>
            <div class="mb-3">
                <label for="action" class="form-label">Action</label>
                <select class="form-select" id="action" name="action" required>
                    <option value="issue">Issue</option>
                    <option value="add">Add</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <h2 class="mt-5">Transaction History</h2>
        <div class="mb-3">
            <label for="monthFilter" class="form-label">Filter by Month:</label>
            <input type="month" id="monthFilter" class="form-control">
            <button id="resetFilter" class="btn btn-secondary mt-2">Reset Filter</button>
        </div>
        <div class="mb-3">
            <label for="recordsPerPage" class="form-label">Records per page:</label>
            <select id="recordsPerPage" class="form-select" style="width: auto; display: inline-block;">
                <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
            </select>
        </div>
        <table class="table table-striped" id="transactionTable">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr data-date="<?php echo date('Y-m', strtotime($transaction['transaction_date'])); ?>">
                        <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['transaction_type']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <nav aria-label="Transaction history pagination">
            <ul class="pagination">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?drug_id=<?php echo $drug_id; ?>&page=<?php echo $page - 1; ?>&limit=<?php echo $records_per_page; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?drug_id=<?php echo $drug_id; ?>&page=<?php echo $i; ?>&limit=<?php echo $records_per_page; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?drug_id=<?php echo $drug_id; ?>&page=<?php echo $page + 1; ?>&limit=<?php echo $records_per_page; ?>">Next</a>
                </li>
            </ul>
        </nav>

        <div id="summaryResults"></div>

        <a href="drug_update.php" class="btn btn-secondary mt-3">Back to Drug List</a>
    </div>

    <div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="summaryModalLabel">Transaction Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="summaryForm">
                        <div class="mb-3">
                            <label for="summaryType" class="form-label">Summary Type</label>
                            <select class="form-select" id="summaryType" name="summaryType">
                                <option value="month">Single Month</option>
                                <option value="range">Date Range</option>
                            </select>
                        </div>
                        <div id="monthSelection" class="mb-3">
                            <label for="summaryMonth" class="form-label">Select Month</label>
                            <input type="month" class="form-control" id="summaryMonth" name="summaryMonth">
                        </div>
                        <div id="rangeSelection" class="mb-3" style="display: none;">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="startDate">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="endDate">
                        </div>
                        <button type="submit" class="btn btn-primary">Generate Summary</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthFilter = document.getElementById('monthFilter');
            const resetFilter = document.getElementById('resetFilter');
            const table = document.getElementById('transactionTable');
            const rows = table.getElementsByTagName('tr');

            monthFilter.addEventListener('change', function() {
                const selectedMonth = this.value;
                for (let i = 1; i < rows.length; i++) {
                    const row = rows[i];
                    const date = row.getAttribute('data-date');
                    if (date === selectedMonth) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });

            resetFilter.addEventListener('click', function() {
                monthFilter.value = '';
                for (let i = 1; i < rows.length; i++) {
                    rows[i].style.display = '';
                }
            });

            document.getElementById('summaryType').addEventListener('change', function() {
                document.getElementById('monthSelection').style.display = this.value === 'month' ? 'block' : 'none';
                document.getElementById('rangeSelection').style.display = this.value === 'range' ? 'block' : 'none';
            });

            document.getElementById('summaryForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('drug_id', <?php echo $drug_id; ?>);

                fetch('get_summary.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        displaySummary(data);
                        $('#summaryModal').modal('hide');
                    })
                    .catch(error => console.error('Error:', error));
            });

            document.getElementById('recordsPerPage').addEventListener('change', function() {
                window.location.href = `?drug_id=<?php echo $drug_id; ?>&page=1&limit=${this.value}`;
            });
        });

        function displaySummary(data) {
            let summaryTitle = '';
            if (data.summaryType === 'month') {
                summaryTitle = `Transaction Summary for ${data.summaryPeriod}`;
            } else {
                summaryTitle = `Transaction Summary from ${data.startDate} to ${data.endDate}`;
            }

            let summaryHtml = `
            <h3>${summaryTitle}</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Transaction Type</th>
                        <th>Total Count</th>
                        <th>Total Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Issued</td>
                        <td>${data.issued.count}</td>
                        <td>${data.issued.quantity}</td>
                    </tr>
                    <tr>
                        <td>Added</td>
                        <td>${data.added.count}</td>
                        <td>${data.added.quantity}</td>
                    </tr>
                </tbody>
            </table>
        `;
            document.getElementById('summaryResults').innerHTML = summaryHtml;
        }
    </script>
</body>

</html>