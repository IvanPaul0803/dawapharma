<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>
<?php include('./constant/layout/sidebar.php');?>
<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->
<?php 
include('./constant/connect.php');

// Determine date range for filtering
$dateQuery = $connect->query("SELECT MIN(exp_date) AS min_d, MAX(exp_date) AS max_d FROM expired_drugs");
$dateRow = ($dateQuery && $dateQuery->num_rows > 0) ? $dateQuery->fetch_assoc() : null;

$defaultFrom = ($dateRow && $dateRow['min_d']) ? $dateRow['min_d'] : '2021-01-01';
$today = date('Y-m-d');
$defaultTo = ($dateRow && $dateRow['max_d'] && $dateRow['max_d'] > $today) ? $dateRow['max_d'] : $today;

$fromDate = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : $defaultFrom;
$toDate   = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : $defaultTo;

$fromDateEsc = $connect->real_escape_string($fromDate);
$toDateEsc   = $connect->real_escape_string($toDate);

// Normalized query: pulls product details (name, buy price) from product table,
// supplier name from brands table, and calculates expected loss dynamically.
$sql = "SELECT 
            e.id AS expired_id,
            e.product_id,
            p.product_name,
            e.batch_no,
            e.exp_date,
            e.quantity AS drug_qty,
            p.rate AS buy_price,
            b.brand_name AS supplier,
            (e.quantity * CAST(p.rate AS DECIMAL(10,2))) AS expected_loss,
            e.status AS exp_status
        FROM expired_drugs e
        INNER JOIN product p ON e.product_id = p.product_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE e.exp_date >= '$fromDateEsc' AND e.exp_date <= '$toDateEsc'
        ORDER BY e.exp_date ASC";

$result = $connect->query($sql);
?>

<div class="page-wrapper">

    <div class="row page-titles">
        <div class="col-md-5 align-self-center">
            <h3 class="text-primary">List Expiring Drugs</h3> 
        </div>
        <div class="col-md-7 align-self-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Profile</a></li>
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active">Expiring Drugs</li>
            </ol>
        </div>
    </div>

    <div class="container-fluid">

        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center m-b-15">
                    <h4 class="card-title m-b-0">Expiring/Expired Drugs</h4>
                    <button type="button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#addExpiredModal">
                        <i class="fa fa-plus"></i> Add Expired Drug
                    </button>
                </div>

                <div id="expiring-messages"></div>

                <form class="form-inline m-b-20" method="get" action="list-expiring-drugs.php">
                    <div class="form-group m-r-20">
                        <label class="m-r-10"><strong>From :</strong></label>
                        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($fromDate); ?>">
                    </div>
                    <div class="form-group m-r-20">
                        <label class="m-r-10"><strong>To :</strong></label>
                        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($toDate); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-flat m-r-10">Filter</button>
                    <a href="list-expiring-drugs.php" class="btn btn-secondary btn-flat">Reset</a>
                </form>

                <div class="table-responsive m-t-10">
                    <table id="myTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Drug Name</th>
                                <th>Batch No</th>
                                <th>Expiry Date</th>
                                <th>Drug Qty</th>
                                <th>Buy Price</th>
                                <th>Supplier</th>
                                <th>Expected Loss</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $qty = intval($row['drug_qty']);
                                $buyPrice = floatval($row['buy_price']);
                                $expectedLoss = $row['expected_loss'] !== null ? floatval($row['expected_loss']) : ($qty * $buyPrice);
                                $isResolved = intval($row['exp_status']) === 1;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['batch_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['exp_date']); ?></td>
                                    <td><?php echo $qty; ?></td>
                                    <td><?php echo htmlspecialchars($row['buy_price']); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier'] ? $row['supplier'] : 'N/A'); ?></td>
                                    <td><strong><?php echo number_format($expectedLoss); ?></strong></td>
                                    <td>
                                        <span class="badge expStatusBadge <?php echo $isResolved ? 'badge-success' : 'badge-warning'; ?>"
                                              id="expStatusBadge-<?php echo $row['expired_id']; ?>"
                                              style="border-radius:30px; padding:6px 14px; font-size:12px;">
                                            <?php echo $isResolved ? 'Resolved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" class="editExpStatusBtn"
                                           data-id="<?php echo $row['expired_id']; ?>"
                                           data-name="<?php echo htmlspecialchars($row['product_name']); ?>"
                                           data-status="<?php echo $isResolved ? 1 : 0; ?>"
                                           title="Edit Status">
                                            <button type="button" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></button>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Edit Status Modal -->
<div class="modal fade" id="editExpStatusModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="editExpStatusForm">
        <div class="modal-header">
          <h5 class="modal-title">Update Status &ndash; <span id="editExpStatusProductName"></span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="expired_id" id="editExpStatusId">
          <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="exp_status" id="editExpStatusSelect">
              <option value="0">Pending</option>
              <option value="1">Resolved</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Expired Drug Modal -->
<div class="modal fade" id="addExpiredModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addExpiredDrugForm">
        <div class="modal-header">
          <h5 class="modal-title">Add Expired Drug Entry</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Select Product (Pulls Name, Rate &amp; Supplier)</label>
            <select class="form-control" name="product_id" id="add_product_id" required>
                <option value="">-- Select Product --</option>
                <?php 
                $pSql = "SELECT p.product_id, p.product_name, p.rate, b.brand_name 
                         FROM product p 
                         LEFT JOIN brands b ON p.brand_id = b.brand_id 
                         WHERE p.status = 1 
                         ORDER BY p.product_name ASC";
                $pRes = $connect->query($pSql);
                if ($pRes && $pRes->num_rows > 0) {
                    while ($pRow = $pRes->fetch_assoc()) {
                        echo "<option value='".$pRow['product_id']."'>".htmlspecialchars($pRow['product_name'])." (Price: ".$pRow['rate'].", Supplier: ".($pRow['brand_name'] ? $pRow['brand_name'] : 'N/A').")</option>";
                    }
                }
                ?>
            </select>
            <small class="form-text text-muted">Normalized schema: Product master details are pulled automatically.</small>
          </div>
          <div class="form-group">
            <label>Batch No</label>
            <input type="text" class="form-control" name="batch_no" id="add_batch_no" placeholder="e.g. 10200375" required>
          </div>
          <div class="form-group">
            <label>Expiry Date</label>
            <input type="date" class="form-control" name="exp_date" id="add_exp_date" required>
          </div>
          <div class="form-group">
            <label>Drug Quantity</label>
            <input type="number" class="form-control" name="quantity" id="add_quantity" min="1" placeholder="e.g. 10" required>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="status" id="add_status">
              <option value="1" selected>Resolved</option>
              <option value="0">Pending</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save Entry</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {

    // Edit status click handler
    $(document).on('click', '.editExpStatusBtn', function() {
        $('#editExpStatusId').val($(this).data('id'));
        $('#editExpStatusProductName').text($(this).data('name'));
        $('#editExpStatusSelect').val($(this).data('status'));
        $('#editExpStatusModal').modal('show');
    });

    // Update status form submission via AJAX
    $('#editExpStatusForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'php_action/updateExpStatus.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    var badge = $('#expStatusBadge-' + resp.expired_id);
                    if (resp.exp_status == 1) {
                        badge.text('Resolved').removeClass('badge-warning').addClass('badge-success');
                    } else {
                        badge.text('Pending').removeClass('badge-success').addClass('badge-warning');
                    }
                    // Update trigger button status data
                    $('.editExpStatusBtn[data-id="' + resp.expired_id + '"]').data('status', resp.exp_status);
                    $('#editExpStatusModal').modal('hide');
                    $('#expiring-messages').html('<div class="alert alert-success">Status updated successfully!</div>');
                    setTimeout(function(){ $('#expiring-messages').empty(); }, 3000);
                } else {
                    $('#expiring-messages').html('<div class="alert alert-danger">' + resp.message + '</div>');
                }
            },
            error: function() {
                $('#expiring-messages').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
            }
        });
    });

    // Add expired drug form submission via AJAX
    $('#addExpiredDrugForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'php_action/createExpiredDrug.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    $('#addExpiredModal').modal('hide');
                    location.reload();
                } else {
                    alert(resp.message);
                }
            },
            error: function() {
                alert('Something went wrong while saving the record.');
            }
        });
    });
});
</script>

<?php include('./constant/layout/footer.php');?>
<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuriK.infospace@gmail.com  
 Visit website : www.mayurik.com -->
