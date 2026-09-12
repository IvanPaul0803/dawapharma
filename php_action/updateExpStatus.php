<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Invalid request');

$expiredId = isset($_POST['expired_id']) ? intval($_POST['expired_id']) : (isset($_POST['product_id']) ? intval($_POST['product_id']) : 0);
$expStatus = isset($_POST['exp_status']) ? intval($_POST['exp_status']) : -1;

if ($expiredId > 0 && in_array($expStatus, array(0, 1))) {
    $sqlUpdate = "UPDATE expired_drugs SET status = {$expStatus} WHERE id = {$expiredId}";

    if ($connect->query($sqlUpdate) === TRUE) {
        $response['status'] = 'success';
        $response['message'] = 'Status updated successfully';
        $response['expired_id'] = $expiredId;
        $response['product_id'] = $expiredId;
        $response['exp_status'] = $expStatus;
    } else {
        $response['message'] = 'Error while updating status: ' . $connect->error;
    }
} else {
    $response['message'] = 'Please provide a valid status';
}

$connect->close();

echo json_encode($response);
