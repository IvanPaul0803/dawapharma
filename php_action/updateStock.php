<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array('status' => 'error', 'message' => 'Invalid request');

$productId  = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$adjustType = isset($_POST['adjust_type']) ? $_POST['adjust_type'] : '';
$adjustQty  = isset($_POST['adjust_qty']) ? intval($_POST['adjust_qty']) : 0;

if ($productId > 0 && $adjustQty > 0 && in_array($adjustType, array('add', 'remove'))) {

    $sql = "SELECT quantity FROM product WHERE product_id = {$productId}";
    $result = $connect->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentQty = intval($row['quantity']);

        if ($adjustType === 'add') {
            $newQty = $currentQty + $adjustQty;
        } else {
            $newQty = $currentQty - $adjustQty;
            if ($newQty < 0) {
                $newQty = 0;
            }
        }

        $sqlUpdate = "UPDATE product SET quantity = {$newQty} WHERE product_id = {$productId}";

        if ($connect->query($sqlUpdate) === TRUE) {
            $response['status'] = 'success';
            $response['message'] = 'Stock updated successfully';
            $response['new_quantity'] = $newQty;
        } else {
            $response['message'] = 'Error while updating stock';
        }
    } else {
        $response['message'] = 'Product not found';
    }
} else {
    $response['message'] = 'Please provide a valid quantity';
}

$connect->close();

echo json_encode($response);
