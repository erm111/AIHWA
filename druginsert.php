<?php
include 'conn.php';

$file = fopen("drug names.txt", "r");

$stmt = $conn->prepare("INSERT INTO drugs (drug_name, price, quantity, expiry_date, total) VALUES (?, 0.00, 0, CURDATE(), 0)");

while (!feof($file)) {
    $drug_name = trim(fgets($file));
    if (!empty($drug_name)) {
        $stmt->bind_param("s", $drug_name);
        $stmt->execute();
    }
}

fclose($file);
$stmt->close();
$conn->close();

echo "All drugs inserted successfully!";
?>