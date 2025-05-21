<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/db_connect.php';

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Add or Update product
if (isset($_POST['save_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $sku = trim($_POST['sku']);
    $price = floatval($_POST['price']);
    $cost = floatval($_POST['cost']);
    $min_stock = intval($_POST['min_stock']);
    $supplier = trim($_POST['supplier']);
    $description = trim($_POST['description']);
    
    // Basic validation
        if (empty($name) || empty($category) || empty($sku)) {
            $error = "Required fields cannot be empty";
        } else {
            // Check if updating or adding
            if ($id > 0) {
                // Update existing product
            $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, sku = ?, price = ?, cost = ?, 
                                    min_stock_level = ?, supplier = ?, description = ?, updated_at = NOW() 
                                    WHERE id = ?");
            $price_var = $price;
            $cost_var = $cost;
            $min_stock_var = $min_stock;
            $stmt->bind_param("sssddsssi", $name, $category, $sku, $price_var, $cost_var, $min_stock_var, $supplier, $description, $id);
            
            if ($stmt->execute()) {
                $success = "Product updated successfully";
                // Redirect after short delay
                header("Refresh: 1; URL=products.php");
            } else {
                $error = "Error updating product: " . $conn->error;
            }
            } else {
                // Check if SKU already exists
                $sku_check_stmt = $conn->prepare("SELECT id FROM products WHERE sku = ?");
                $sku_check_stmt->bind_param("s", $sku);
                $sku_check_stmt->execute();
                $sku_check_result = $sku_check_stmt->get_result();
                
                if ($sku_check_result->num_rows > 0) {
                    $error = "SKU already exists. Please use a unique SKU.";
                } else {
                    // Add new product
                    $stmt = $conn->prepare("INSERT INTO products (name, category, sku, price, cost, min_stock_level, supplier, description, created_at, updated_at) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $price_var = $price;
                    $cost_var = $cost;
                    $min_stock_var = $min_stock;
                    $stmt->bind_param("sssddiss", $name, $category, $sku, $price_var, $cost_var, $min_stock_var, $supplier, $description);
                    
                    if ($stmt->execute()) {
                        // Record transaction for new stock
                        $product_id = $conn->insert_id;
                        $quantity = 0;
                        $trans_stmt = $conn->prepare("INSERT INTO transactions (product_id, product_name, type, quantity, user_id, transaction_date) 
                                                    VALUES (?, ?, 'in', ?, ?, NOW())");
                        $trans_stmt->bind_param("isii", $product_id, $name, $quantity, $_SESSION['user_id']);
                        $trans_stmt->execute();
                        
                        $success = "Product added successfully";
                        // Redirect after short delay
                        header("Refresh: 1; URL=products.php");
                    } else {
                        $error = "Error adding product: " . $conn->error;
                    }
                }
            }
        }
}
    
    // Delete product
    if (isset($_POST['delete_product'])) {
        $delete_id = intval($_POST['delete_id']);
        
        // First check if product exists
        $check = $conn->prepare("SELECT name FROM products WHERE id = ?");
        $check->bind_param("i", $delete_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 1) {
            $product = $result->fetch_assoc();
            
            // Delete related transactions first
            $trans_stmt = $conn->prepare("DELETE FROM transactions WHERE product_id = ?");
            $trans_stmt->bind_param("i", $delete_id);
            $trans_stmt->execute();

            // Delete product
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                $success = "Product deleted successfully";
                // Redirect after short delay
                header("Refresh: 1; URL=products.php");
            } else {
                $error = "Error deleting product: " . $conn->error;
            }
        } else {
            $error = "Product not found";
        }
    }
}

// Get product data for edit or view
$product = null;
if ($id > 0 && ($action === 'edit' || $action === 'view')) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $product = $result->fetch_assoc();
    } else {
        $error = "Product not found";
        $action = 'list'; // Fallback to list
    }
}

// Get all products for listing
$products = array();
if ($action === 'list') {
    $sql = "SELECT * FROM products ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// Get categories for dropdown
$categories = array();
$cat_result = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
if ($cat_result->num_rows > 0) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/products.css">
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
                            Add New Product
                        <?php elseif ($action === 'edit'): ?>
                            Edit Product
                        <?php elseif ($action === 'view'): ?>
                            Product Details
                        <?php else: ?>
                            Products
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($action === 'list'): ?>
                    <div class="header-actions">
                        <a href="products.php?action=add" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Add Product
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
                    <!-- Products List View -->
                    <div class="card">
                        <div class="card-header">
                            <div class="search-container">
                                <input type="text" id="product-search" placeholder="Search products..." class="search-input">
                                <div class="filter-container">
                                    <select id="category-filter" class="filter-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="stock-filter" class="filter-select">
                                        <option value="">All Stock Levels</option>
                                        <option value="low">Low Stock</option>
                                        <option value="normal">Normal Stock</option>
                                        <option value="out">Out of Stock</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="products-table">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($products) > 0): ?>
                                        <?php foreach ($products as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                <td>
                                                    <?php if ($item['quantity'] <= 0): ?>
                                                        <span class="badge badge-danger">Out of Stock</span>
                                                    <?php elseif ($item['quantity'] <= $item['min_stock_level']): ?>
                                                        <span class="badge badge-warning">Low Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">In Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="actions">
                                                    <a href="products.php?action=view&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                    </a>
                                                    <a href="products.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" title="Delete">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No products found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Delete Confirmation Modal -->
                    <div id="delete-modal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>Confirm Deletion</h2>
                                <span class="close">&times;</span>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete <span id="delete-product-name"></span>?</p>
                                <p class="text-danger">This action cannot be undone.</p>
                            </div>
                            <div class="modal-footer">
                                <form method="POST" action="">
                                    <input type="hidden" name="delete_id" id="delete-id">
                                    <button type="button" class="btn btn-secondary close-btn">Cancel</button>
                                    <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit Product Form -->
                    <div class="card">
                        <form method="POST" action="<?php echo ($action === 'edit') ? 'products.php?action=edit&id=' . $id : 'products.php?action=add'; ?>" class="form">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo ($action === 'edit' && $product) ? htmlspecialchars($product['name']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="category">Category <span class="text-danger">*</span></label>
                                    <input type="text" id="category" name="category" list="category-list" required
                                           value="<?php echo ($action === 'edit' && $product) ? htmlspecialchars($product['category']) : ''; ?>">
                                    <datalist id="category-list">
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo htmlspecialchars($category); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sku">SKU <span class="text-danger">*</span></label>
                                    <input type="text" id="sku" name="sku" required
                                           value="<?php echo ($action === 'edit' && $product) ? htmlspecialchars($product['sku']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="price">Selling Price <span class="text-danger">*</span></label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" required
                                           value="<?php echo ($action === 'edit' && $product) ? $product['price'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="cost">Cost Price</label>
                                    <input type="number" id="cost" name="cost" step="0.01" min="0"
                                           value="<?php echo ($action === 'edit' && $product) ? $product['cost'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                
                                <div class="form-group">
                                    <label for="min_stock">Min Stock Level</label>
                                    <input type="number" id="min_stock" name="min_stock" min="0"
                                           value="<?php echo ($action === 'edit' && $product) ? $product['min_stock_level'] : '5'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="supplier">Supplier</label>
                                    <input type="text" id="supplier" name="supplier"
                                           value="<?php echo ($action === 'edit' && $product) ? htmlspecialchars($product['supplier']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" rows="4"><?php echo ($action === 'edit' && $product) ? htmlspecialchars($product['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <a href="products.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="save_product" class="btn btn-primary">
                                    <?php echo ($action === 'edit') ? 'Update Product' : 'Add Product'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($action === 'view' && $product): ?>
                    <!-- Product Detail View -->
                    <div class="card">
                        <div class="product-details">
                            <div class="detail-header">
                                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                                <div class="stock-badge <?php 
                                    if ($product['quantity'] <= 0) echo 'badge-danger';
                                    elseif ($product['quantity'] <= $product['min_stock_level']) echo 'badge-warning';
                                    else echo 'badge-success';
                                ?>">
                                    <?php 
                                        if ($product['quantity'] <= 0) echo 'Out of Stock';
                                        elseif ($product['quantity'] <= $product['min_stock_level']) echo 'Low Stock';
                                        else echo 'In Stock';
                                    ?>
                                </div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-row">
                                    <div class="detail-label">SKU:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($product['sku']); ?></div>
                                </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Category:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($product['category']); ?></div>
                                </div>
                                
                                    <div class="detail-row">
                                        <div class="detail-label">Price:</div>
                                        <div class="detail-value">₱<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                    
                                    <div class="detail-row">
                                        <div class="detail-label">Cost:</div>
                                        <div class="detail-value">₱<?php echo number_format($product['cost'], 2); ?></div>
                                    </div>
                                
                                <div class="detail-row">
                                    <div class="detail-label">Current Stock:</div>
                                    <div class="detail-value"><?php echo $product['quantity']; ?> units</div>
                                </div>
                                
                            <div class="detail-row">
                                <div class="detail-label">Min Stock Level:</div>
                                <div class="detail-value"><?php echo $product['min_stock_level']; ?> units</div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">Supplier:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($product['supplier']); ?></div>
                            </div>
                            
                            <?php if (!empty($product['description'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Description:</div>
                                <div class="detail-value description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-row">
                                <div class="detail-label">Created:</div>
                                <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($product['created_at'])); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Last Updated:</div>
                                <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($product['updated_at'])); ?></div>
                            </div>
                            </div>
                            
                            <div class="detail-actions">
                                <a href="products.php" class="btn btn-secondary">Back to List</a>
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-primary">Edit</a>
                                <a href="transactions.php?product_id=<?php echo $product['id']; ?>" class="btn btn-info">View Transactions</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script src="assets/js/products.js"></script>
</body>
</html>