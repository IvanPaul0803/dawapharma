<?php 

require_once 'core.php';

if($_POST) {

    $startDate = $_POST['startDate'];
    $endDate   = $_POST['endDate'];

    // Fetch all order_item rows within date range
    $sql   = "SELECT oi.*, p.product_name, p.rate AS cost_price
              FROM order_item oi
              LEFT JOIN product p ON p.product_id = oi.productName
              WHERE oi.added_date >= '$startDate' AND oi.added_date <= '$endDate'
              ORDER BY oi.id ASC";
    $query = $connect->query($sql);

    $grandTotal  = 0;
    $grandProfit = 0;
    $rows        = [];

    while ($row = $query->fetch_assoc()) {
        $qty        = floatval($row['quantity']);
        $price      = floatval($row['rate']);
        $total      = floatval($row['total']);
        $costPrice  = floatval($row['cost_price']);
        $profit     = $total - ($costPrice * $qty);

        $grandTotal  += $total;
        $grandProfit += $profit;

        $rows[] = [
            'name'   => $row['product_name'] ?? 'Unknown',
            'qty'    => $qty,
            'price'  => $price,
            'total'  => $total,
            'profit' => $profit,
        ];
    }

    // ── Build the print-ready HTML ────────────────────────────────────────────
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            color: #000;
            background: #fff;
            padding: 30px;
        }

        /* ── Header ─────────────────────────── */
        .pharmacy-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .pharmacy-header h1 {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .pharmacy-header p {
            font-size: 13px;
            margin-bottom: 4px;
        }

        /* ── Report title ────────────────────── */
        .report-title {
            text-align: center;
            margin: 18px 0 6px;
        }
        .report-title h2 {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-title h3 {
            font-size: 15px;
            font-weight: bold;
            margin-top: 8px;
        }

        /* ── Main table ──────────────────────── */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        /* "Pharmacy Items" section header */
        .report-table .section-header td {
            background: #fff;
            font-weight: bold;
            font-size: 13px;
            padding: 6px 8px;
            border: 1px solid #333;
        }

        /* Column headers – blue background */
        .report-table .col-header th {
            background-color: #7ab4e8;
            font-weight: bold;
            text-align: center;
            padding: 7px 6px;
            border: 1px solid #333;
            font-size: 13px;
        }
        .report-table .col-header th:nth-child(2) {
            text-align: left;
        }

        /* Data rows */
        .report-table tbody tr td {
            padding: 6px 8px;
            border: 1px solid #333;
            font-size: 13px;
        }
        .report-table tbody tr td:first-child,
        .report-table tbody tr td:nth-child(3),
        .report-table tbody tr td:nth-child(4),
        .report-table tbody tr td:nth-child(5),
        .report-table tbody tr td:nth-child(6) {
            text-align: center;
        }

        /* Sub-Total row */
        .row-subtotal td {
            padding: 7px 8px;
            border: 1px solid #333;
            font-weight: bold;
            font-size: 13px;
        }
        .row-subtotal td:nth-child(1) {
            text-align: right;
        }
        .row-subtotal td:nth-child(2),
        .row-subtotal td:nth-child(3) {
            text-align: center;
        }

        /* Black separator bar */
        .row-separator td {
            background: #000;
            height: 12px;
            border: none;
        }

        /* Grand-Total row – light green */
        .row-grandtotal td {
            background: #c6efce;
            font-weight: bold;
            font-size: 14px;
            padding: 9px 8px;
            border: 1px solid #333;
        }
        .row-grandtotal td:first-child {
            text-align: left;
        }
        .row-grandtotal td:nth-child(2),
        .row-grandtotal td:nth-child(3) {
            text-align: center;
        }

        @media print {
            body { padding: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- ── Pharmacy Header ─────────────────────────────────────── -->
    <div class="pharmacy-header">
        <h1>Dawa Pharma</h1>
        <p>Pharmacy Management System</p>
        <p>Tel: +255 657 209 956 &nbsp;|&nbsp; info@dawapharma.com</p>
    </div>

    <!-- ── Report Title ────────────────────────────────────────── -->
    <div class="report-title">
        <h2>Sales Report</h2>
        <h3>FROM : <?php echo htmlspecialchars($startDate); ?> &nbsp;&nbsp;TO : <?php echo htmlspecialchars($endDate); ?></h3>
    </div>

    <!-- ── Report Table ────────────────────────────────────────── -->
    <table class="report-table">

        <!-- Section header -->
        <thead>
            <tr class="section-header">
                <td colspan="6">Pharmacy Items</td>
            </tr>
            <tr class="col-header">
                <th style="width:5%">SN</th>
                <th style="width:35%">Item Name</th>
                <th style="width:10%">Qty</th>
                <th style="width:15%">Price</th>
                <th style="width:17%">Total</th>
                <th style="width:18%">Profit</th>
            </tr>
        </thead>

        <!-- Data rows -->
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:15px; color:#888;">
                    No sales records found for the selected date range.
                </td>
            </tr>
            <?php else: ?>
            <?php $sn = 1; foreach ($rows as $r): ?>
            <tr>
                <td><?php echo $sn++; ?></td>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo number_format($r['qty'], 1); ?></td>
                <td><?php echo number_format($r['price'], 1); ?></td>
                <td><?php echo number_format($r['total'], 1); ?></td>
                <td><?php echo number_format($r['profit'], 1); ?></td>
            </tr>
            <?php endforeach; ?>

            <!-- Sub-Total row -->
            <tr class="row-subtotal">
                <td colspan="4" style="text-align:right; padding-right:12px;">Sub-Total</td>
                <td><?php echo number_format($grandTotal, 1); ?></td>
                <td><?php echo number_format($grandProfit, 1); ?></td>
            </tr>

            <!-- Black separator -->
            <tr class="row-separator">
                <td colspan="6"></td>
            </tr>

            <!-- Grand-Total row -->
            <tr class="row-grandtotal">
                <td colspan="4">Grand-Total</td>
                <td><?php echo number_format($grandTotal, 1); ?></td>
                <td><?php echo number_format($grandProfit, 1); ?></td>
            </tr>
            <?php endif; ?>
        </tbody>

    </table>

</body>
</html>
    <?php

} // end if $_POST
?>