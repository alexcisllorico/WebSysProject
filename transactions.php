<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add transaction
    if (isset($_POST['add_transaction'])) {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $type = $_POST['type'];
        $notes = trim($_POST['notes']);
        
        // Basic validation
        if ($product_id <= 0 || $quantity <= 0) {
            $error = "Invalid product or quantity";
        } else {
            // Get product information
            $stmt = $conn->prepare("SELECT name, quantity FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();
                $current_quantity = $product['quantity'];
                $product_name = $product['name'];
                
                // Check if there's enough stock for outgoing transactions
                if ($type === 'out' && $quantity > $current_quantity) {
                    $error = "Not enough stock available. Current stock: " . $current_quantity;
                } else {
                    // Update product quantity
                    $new_quantity = ($type === 'in') ? $current_quantity + $quantity : $current_quantity - $quantity;
                    $update_stmt = $conn->prepare("UPDATE products SET quantity = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $product_id);
                    
                    if ($update_stmt->execute()) {
                        // Record transaction
                        $trans_stmt = $conn->prepare("INSERT INTO transactions (product_id, product_name, type, quantity, notes, user_id, transaction_date) 
                                                    VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $trans_stmt->bind_param("issisi", $product_id, $product_name, $type, $quantity, $notes, $_SESSION['user_id']);
                        
                        if ($trans_stmt->execute()) {
                            $success = ucfirst($type) . "coming transaction recorded successfully";
                            // Redirect after short delay
                            header("Refresh: 1; URL=transactions.php");
                        } else {
                            $error = "Error recording transaction: " . $conn->error;
                        }
                    } else {
                        $error = "Error updating product quantity: " . $conn->error;
                    }
                }
            } else {
                $error = "Product not found";
            }
        }
    }
}

// Get products for dropdown
$products = array();
$product_sql = "SELECT id, name, sku, quantity FROM products ORDER BY name ASC";
$product_result = $conn->query($product_sql);

if ($product_result->num_rows > 0) {
    while ($row = $product_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get transactions for listing
$transactions = array();
$sql = "SELECT t.*, u.username FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id ";

// Filter by product if specified
if ($product_id > 0) {
    $sql .= "WHERE t.product_id = " . $product_id . " ";
}

$sql .= "ORDER BY t.transaction_date DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}

// Get product info if filtering by product
$filtered_product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $filtered_product = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/transactions.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h1>
                        <?php if ($action === 'add'): ?>
                            Record <?php echo ucfirst($type); ?>coming Stock
                        <?php elseif ($filtered_product): ?>
                            Transactions for <?php echo htmlspecialchars($filtered_product['name']); ?>
                        <?php else: ?>
                            Transactions
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($action === 'list'): ?>
                    <div class="header-actions">
                        <a href="transactions.php?action=add&type=in" class="btn btn-success mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-down-to-line"><path d="M12 3v14"/><path d="m5 10 7 7 7-7"/><path d="M19 21H5"/></svg>
                            Stock In
                        </a>
                        <a href="transactions.php?action=add&type=out" class="btn btn-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-from-line"><path d="m12 21 7-7-7-7"/><path d="M5 14h14"/><path d="M19 3H5"/></svg>
                            Stock Out
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                    <!-- Transactions List View -->
                    <div class="card">
                        <div class="card-header">
                            <div class="search-container">
                                <input type="text" id="transaction-search" placeholder="Search transactions..." class="search-input">
                                <div class="filter-container">
                                    <select id="type-filter" class="filter-select">
                                        <option value="">All Types</option>
                                        <option value="in">Stock In</option>
                                        <option value="out">Stock Out</option>
                                    </select>
                                    <input type="date" id="date-filter" class="filter-date" placeholder="Filter by date">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="transactions-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date & Time</th>
                                        <th>Product</th>
                                        <th>Type</th>
                                        <th>Quantity</th>
                                        <th>User</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($transactions) > 0): ?>
                                        <?php foreach ($transactions as $transaction): ?>
                                            <tr>
                                                <td>#<?php echo $transaction['id']; ?></td>
                                                <td><?php echo date('M d, Y g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $transaction['type'] === 'in' ? 'badge-success' : 'badge-warning'; ?>">
                                                        <?php echo $transaction['type'] === 'in' ? 'Stock In' : 'Stock Out'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $transaction['quantity']; ?></td>
                                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                                <td class="notes-cell"><?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No transactions found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'add' && ($type === 'in' || $type === 'out')): ?>
                    <!-- Add Transaction Form -->
                    <div class="card">
                        <form method="POST" action="" class="form">
                            <input type="hidden" name="type" value="<?php echo $type; ?>">
                            
                            <div class="form-group">
                                <label for="product_id">Product <span class="text-danger">*</span></label>
                                <select id="product_id" name="product_id" required class="form-select">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>" data-quantity="<?php echo $product['quantity']; ?>">
                                            <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ') - ' . $product['quantity'] . ' in stock'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity <span class="text-danger">*</span></label>
                                <input type="number" id="quantity" name="quantity" min="1" required>
                                <?php if ($type === 'out'): ?>
                                    <small class="text-muted">Available: <span id="available-quantity">0</span></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Optional notes about this transaction"></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <a href="transactions.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="add_transaction" class="btn <?php echo $type === 'in' ? 'btn-success' : 'btn-warning'; ?>">
                                    <?php echo $type === 'in' ? 'Record Stock In' : 'Record Stock Out'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/transactions.js"></script>
</body>
</html>