<?php
require_once 'conn.php';

$drug_id = $_POST['drug_id'];
$summaryType = $_POST['summaryType'];

if ($summaryType === 'month') {
    $month = $_POST['summaryMonth'];
    $startDate = date('Y-m-01', strtotime($month));
    $endDate = date('Y-m-t', strtotime($month));
} else {
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
}

$sql = "SELECT transaction_type, COUNT(*) as count, SUM(quantity) as total_quantity 
        FROM drug_transactions 
        WHERE drug_id = ? AND transaction_date BETWEEN ? AND ?
        GROUP BY transaction_type";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $drug_id, $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$summary = [
    'issued' => ['count' => 0, 'quantity' => 0],
    'added' => ['count' => 0, 'quantity' => 0]
];

while ($row = $result->fetch_assoc()) {
    $type = $row['transaction_type'] === 'issue' ? 'issued' : 'added';
    $summary[$type]['count'] = $row['count'];
    $summary[$type]['quantity'] = $row['total_quantity'];
}

$summary['summaryType'] = $summaryType;
if ($summaryType === 'month') {
    $summary['summaryPeriod'] = date('F Y', strtotime($month));
} else {
    $summary['startDate'] = date('F Y', strtotime($startDate));
    $summary['endDate'] = date('F Y', strtotime($endDate));
}

echo json_encode($summary);
