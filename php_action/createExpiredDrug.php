<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Invalid request');

if ($_POST) {
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $batchNo   = isset($_POST['batch_no']) ? trim($_POST['batch_no']) : '';
    $expDate   = isset($_POST['exp_date']) ? trim($_POST['exp_date']) : '';
    $quantity  = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $status    = isset($_POST['status']) ? intval($_POST['status']) : 0;

    if ($productId > 0 && !empty($batchNo) && !empty($expDate) && $quantity >= 0) {
        $batchNoEsc = $connect->real_escape_string($batchNo);
        $expDateEsc = $connect->real_escape_string($expDate);

        $sql = "INSERT INTO expired_drugs (product_id, batch_no, exp_date, quantity, status) 
                VALUES ('$productId', '$batchNoEsc', '$expDateEsc', '$quantity', '$status')";

        if ($connect->query($sql) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Expired drug record added successfully';
        } else {
            $response['message'] = 'Database error: ' . $connect->error;
        }
    } else {
        $response['message'] = 'Please fill in all required fields properly.';
    }
}

$connect->close();

echo json_encode($response);
