<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

// Get report type and date range
$report_type = isset($_GET['type']) ? $_GET['type'] : 'inventory';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Initialize report data array
$report_data = array();

// Generate report based on type
switch ($report_type) {
    case 'inventory':
        // Current inventory status
        $sql = "SELECT * FROM products ORDER BY category, name";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        break;
        
    case 'low_stock':
        // Low stock items
        $sql = "SELECT * FROM products WHERE quantity <= min_stock_level ORDER BY quantity ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        break;
        
    case 'transactions':
        // Transactions in date range
        $sql = "SELECT t.*, u.username, p.category 
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN products p ON t.product_id = p.id
                WHERE DATE(t.transaction_date) BETWEEN ? AND ?
                ORDER BY t.transaction_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        break;
        
    case 'category':
        // Inventory by category
        $sql = "SELECT category, 
                COUNT(*) as product_count, 
                SUM(quantity) as total_items,
                SUM(quantity * price) as total_value
                FROM products
                GROUP BY category
                ORDER BY category";
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        break;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/reports.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h1>Reports</h1>
                </div>
                
                <div class="report-filters">
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-group">
                            <label for="type">Report Type</label>
                            <select id="type" name="type" class="form-select" onchange="this.form.submit()">
                                <option value="inventory" <?php echo $report_type === 'inventory' ? 'selected' : ''; ?>>Current Inventory</option>
                                <option value="low_stock" <?php echo $report_type === 'low_stock' ? 'selected' : ''; ?>>Low Stock Items</option>
                                <option value="transactions" <?php echo $report_type === 'transactions' ? 'selected' : ''; ?>>Transaction History</option>
                                <option value="category" <?php echo $report_type === 'category' ? 'selected' : ''; ?>>Inventory by Category</option>
                            </select>
                        </div>
                        
                        <?php if ($report_type === 'transactions'): ?>
                        <div class="filter-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="filter-group" style="flex-grow: 0;">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="report-header">
                            <h2 class="report-title">
                                <?php 
                                switch ($report_type) {
                                    case 'inventory': echo 'Current Inventory Report'; break;
                                    case 'low_stock': echo 'Low Stock Items Report'; break;
                                    case 'transactions': echo 'Transaction History Report'; break;
                                    case 'category': echo 'Inventory by Category Report'; break;
                                }
                                ?>
                            </h2>
                            <div class="report-meta">
                                <?php if ($report_type === 'transactions'): ?>
                                    Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?>
                                <?php else: ?>
                                    Generated: <?php echo date('F j, Y, g:i a'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <?php if ($report_type === 'inventory' || $report_type === 'low_stock'): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>SKU</th>
                                            <th>Product Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Cost</th>
                                            <th>Quantity</th>
                                            <th>Value</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($report_data) > 0): ?>
                                            <?php 
                                            $total_items = 0;
                                            $total_value = 0;
                                            
                                            foreach ($report_data as $item): 
                                                $item_value = $item['quantity'] * $item['price'];
                                                $total_items += $item['quantity'];
                                                $total_value += $item_value;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                    <td>₱<?php echo number_format($item['cost'], 2); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td>₱<?php echo number_format($item_value, 2); ?></td>
                                                    <td>
                                                        <?php if ($item['quantity'] <= 0): ?>
                                                            <span class="badge badge-danger">Out of Stock</span>
                                                        <?php elseif ($item['quantity'] <= $item['min_stock_level']): ?>
                                                            <span class="badge badge-warning">Low Stock</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">In Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td colspan="5" class="text-right"><strong>Totals:</strong></td>
                                                <td><strong><?php echo $total_items; ?></strong></td>
                                                <td><strong>₱<?php echo number_format($total_value, 2); ?></strong></td>
                                                <td></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center">No data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                
                            <?php elseif ($report_type === 'transactions'): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>User</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($report_data) > 0): ?>
                                            <?php 
                                            $total_in = 0;
                                            $total_out = 0;
                                            
                                            foreach ($report_data as $transaction): 
                                                if ($transaction['type'] === 'in') {
                                                    $total_in += $transaction['quantity'];
                                                } else {
                                                    $total_out += $transaction['quantity'];
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y g:i A', strtotime($transaction['transaction_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['product_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['category']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo $transaction['type'] === 'in' ? 'badge-success' : 'badge-warning'; ?>">
                                                            <?php echo $transaction['type'] === 'in' ? 'Stock In' : 'Stock Out'; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $transaction['quantity']; ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                                    <td><?php echo !empty($transaction['notes']) ? htmlspecialchars($transaction['notes']) : '-'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td colspan="4" class="text-right"><strong>Total Stock In/Out:</strong></td>
                                                <td colspan="3">
                                                    <span class="badge badge-success">In: <?php echo $total_in; ?></span>
                                                    <span class="badge badge-warning">Out: <?php echo $total_out; ?></span>
                                                    <span class="badge badge-info">Net: <?php echo $total_in - $total_out; ?></span>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No transactions found for the selected period</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                                
                            <?php elseif ($report_type === 'category'): ?>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Products</th>
                                            <th>Total Items</th>
                                            <th>Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($report_data) > 0): ?>
                                            <?php 
                                            $total_products = 0;
                                            $total_items = 0;
                                            $total_value = 0;
                                            
                                            foreach ($report_data as $category): 
                                                $total_products += $category['product_count'];
                                                $total_items += $category['total_items'];
                                                $total_value += $category['total_value'];
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($category['category']); ?></td>
                                                    <td><?php echo $category['product_count']; ?></td>
                                                    <td><?php echo $category['total_items']; ?></td>
                                                    <td>₱<?php echo number_format($category['total_value'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr>
                                                <td><strong>Total</strong></td>
                                                <td><strong><?php echo $total_products; ?></strong></td>
                                                <td><strong><?php echo $total_items; ?></strong></td>
                                                <td><strong>₱<?php echo number_format($total_value, 2); ?></strong></td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        
                        <div class="report-actions">
                            <button onclick="window.print()" class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-printer"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                Print Report
                            </button>
                            <button onclick="exportTableToCSV()" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                Export to CSV
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Function to export table data to CSV
        function exportTableToCSV() {
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Get cell text content, cleaning it for CSV
                    let data = cols[j].textContent.replace(/(\r\n|\n|\r)/gm, ' ').trim();
                    data = data.replace(/"/g, '""'); // Escape double quotes
                    row.push('"' + data + '"');
                }
                
                csv.push(row.join(','));
            }
            
            // Create and download the CSV file
            const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'report_<?php echo $report_type; ?>_<?php echo date('Y-m-d'); ?>.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
