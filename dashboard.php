<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Get inventory summary
$stmt = $conn->prepare("SELECT COUNT(*) as total_products, SUM(quantity) as total_items FROM products");
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Get low stock items
$stmt = $conn->prepare("SELECT COUNT(*) as low_stock FROM products WHERE quantity <= min_stock_level");
$stmt->execute();
$low_stock = $stmt->get_result()->fetch_assoc();

// Get recent transactions
$stmt = $conn->prepare("SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 5");
$stmt->execute();
$recent_transactions = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="dashboard-content">
                <h1>Dashboard</h1>
                
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon products-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3>Total Products</h3>
                            <p><?php echo $summary['total_products']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon inventory-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-stacked"><path d="M4 6V4H20V6"/><path d="M4 10V8H20V10"/><path d="M4 14V12H20V14"/><path d="M4 18V16H20V18"/><path d="M4 22V20H20V22"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3>Total Items</h3>
                            <p><?php echo $summary['total_items']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card alert-card">
                        <div class="stat-icon alert-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-alert-triangle"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        </div>
                        <div class="stat-info">
                            <h3>Low Stock</h3>
                            <p><?php echo $low_stock['low_stock']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-sections">
                    <section class="recent-transactions">
                        <div class="section-header">
                            <h2>Recent Transactions</h2>
                            <a href="transactions.php" class="btn btn-sm btn-outline">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $transaction['id']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $transaction['type'] === 'in' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo $transaction['type'] === 'in' ? 'Stock In' : 'Stock Out'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $transaction['product_name']; ?></td>
                                        <td><?php echo $transaction['quantity']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    
                    <section class="quick-actions">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="products.php?action=add" class="action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Add Product
                            </a>
                            <a href="transactions.php?action=add&type=in" class="action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-down-to-line"><path d="M12 3v14"/><path d="m5 10 7 7 7-7"/><path d="M19 21H5"/></svg>
                                Stock In
                            </a>
                            <a href="transactions.php?action=add&type=out" class="action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-up-from-line"><path d="m12 21 7-7-7-7"/><path d="M5 14h14"/><path d="M19 3H5"/></svg>
                                Stock Out
                            </a>
                            <a href="reports.php" class="action-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart-3"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                                Generate Report
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>