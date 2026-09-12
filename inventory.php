<?php include('./constant/layout/head.php');?>
<?php include('./constant/layout/header.php');?>

<?php include('./constant/layout/sidebar.php');?>
<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->
<?php include('./constant/connect.php');

// Low stock threshold used to flag "Bad" stock status
$lowStockThreshold = 20;

$sql = "SELECT product_id, product_name, product_image, rate, mrp, quantity, brand_id, bno, expdate, categories_id, active, status FROM product WHERE status = 1 ORDER BY product_id DESC";
$result = $connect->query($sql);
?>
       <div class="page-wrapper">

            <div class="row page-titles">
                <div class="col-md-5 align-self-center">
                    <h3 class="text-primary">List Inventory Items</h3> </div>
                <div class="col-md-7 align-self-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Profile</a></li>
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Inventory</li>
                    </ol>
                </div>
            </div>


            <div class="container-fluid">

                <div class="card">
                    <div class="card-body">

                        <h4 class="card-title">Inventory items</h4>

                        <div id="inventory-messages"></div>

                        <div class="d-flex justify-content-end m-b-20">
                            <a href="add-product.php">
                                <button class="btn btn-success btn-round" style="border-radius:30px;">
                                    <i class="fa fa-medkit"></i>&nbsp; New Product
                                </button>
                            </a>
                        </div>

                        <div class="table-responsive m-t-10">
                            <table id="myTable" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Drug Name</th>
                                        <th>Batch No</th>
                                        <th>Drug Expiry</th>
                                        <th>Buy Price</th>
                                        <th>Sale Price</th>
                                        <th>Drug Qty</th>
                                        <th>Drugs Sold</th>
                                        <th>Stock Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                if ($result && $result->num_rows > 0) {
                                foreach ($result as $row) {

                                    // Manufacturer / Brand name, shown alongside the drug name
                                    $brandName = '';
                                    $sqlBrand = "SELECT brand_name FROM brands WHERE brand_id='" . intval($row['brand_id']) . "'";
                                    $resBrand = $connect->query($sqlBrand);
                                    if ($resBrand && $resBrand->num_rows > 0) {
                                        $rowBrand = $resBrand->fetch_assoc();
                                        $brandName = $rowBrand['brand_name'];
                                    }

                                    // Total units of this drug sold across all invoices
                                    $soldQty = 0;
                                    $sqlSold = "SELECT SUM(quantity) AS total_sold FROM order_item WHERE productName='" . intval($row['product_id']) . "'";
                                    $resSold = $connect->query($sqlSold);
                                    if ($resSold && $resSold->num_rows > 0) {
                                        $rowSold = $resSold->fetch_assoc();
                                        $soldQty = $rowSold['total_sold'] ? $rowSold['total_sold'] : 0;
                                    }

                                    $stockStatusGood = ($row['quantity'] > $lowStockThreshold);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                            <?php if ($brandName) { ?>
                                                <span class="text-muted">(<?php echo htmlspecialchars($brandName); ?>)</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['bno']); ?></td>
                                        <td><?php echo htmlspecialchars($row['expdate']); ?></td>
                                        <td><?php echo htmlspecialchars($row['rate']); ?></td>
                                        <td><?php echo htmlspecialchars($row['mrp']); ?></td>
                                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($soldQty); ?></td>
                                        <td>
                                            <?php if ($stockStatusGood) { ?>
                                                <span class="badge badge-success" style="border-radius:30px; padding:6px 14px;">Good</span>
                                            <?php } else { ?>
                                                <span class="badge badge-warning" style="border-radius:30px; padding:6px 14px;">Bad</span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <a href="javascript:void(0)" class="adjustStockBtn"
                                               data-id="<?php echo $row['product_id']; ?>"
                                               data-name="<?php echo htmlspecialchars($row['product_name']); ?>"
                                               data-qty="<?php echo $row['quantity']; ?>"
                                               title="Adjust Stock">
                                                <button type="button" class="btn btn-xs btn-outline-secondary"><i class="fa fa-bars"></i></button>
                                            </a>
                                            <a href="editproduct.php?id=<?php echo $row['product_id']; ?>" title="Edit">
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

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="adjustStockForm">
        <div class="modal-header">
          <h5 class="modal-title">Adjust Stock &ndash; <span id="adjustStockProductName"></span></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="product_id" id="adjustStockProductId">
          <div class="form-group">
            <label>Current Quantity</label>
            <input type="text" class="form-control" id="adjustStockCurrentQty" disabled>
          </div>
          <div class="form-group">
            <label>Adjustment Type</label>
            <select class="form-control" name="adjust_type" id="adjustStockType">
              <option value="add">Stock In (Add)</option>
              <option value="remove">Stock Out (Remove)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input type="number" min="1" class="form-control" name="adjust_qty" id="adjustStockQty" required>
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

<script>
$(document).ready(function() {

    $('.adjustStockBtn').on('click', function() {
        $('#adjustStockProductId').val($(this).data('id'));
        $('#adjustStockProductName').text($(this).data('name'));
        $('#adjustStockCurrentQty').val($(this).data('qty'));
        $('#adjustStockForm')[0].reset();
        $('#adjustStockProductId').val($(this).data('id'));
        $('#adjustStockModal').modal('show');
    });

    $('#adjustStockForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'php_action/updateStock.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.status === 'success') {
                    $('#adjustStockModal').modal('hide');
                    location.reload();
                } else {
                    $('#inventory-messages').html('<div class="alert alert-danger">' + resp.message + '</div>');
                }
            },
            error: function() {
                $('#inventory-messages').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
            }
        });
    });
});
</script>

<?php include('./constant/layout/footer.php');?>
<!--  Author Name: Mayuri K. 
 for any PHP, Codeignitor, Laravel OR Python work contact me at mayuri.infospace@gmail.com  
 Visit website : www.mayurik.com -->
