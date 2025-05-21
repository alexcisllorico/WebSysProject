<?php
// Database setup script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $servername = $_POST['servername'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $dbname = $_POST['dbname'];
    
    // Create connection
    $conn = new mysqli($servername, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($dbname);
        
        // Read SQL file
        $sql_content = file_get_contents('db_setup.sql');
        
        // Execute SQL statements
        if ($conn->multi_query($sql_content)) {
            $success = "Database setup completed successfully!";
            
            // Create db_connect.php file
            $db_config = "<?php
// Database connection
\$servername = \"$servername\";
\$username = \"$username\";
\$password = \"$password\";
\$dbname = \"$dbname\";

// Create connection
\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);

// Check connection
if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}
?>";
            
            // Write to file
            file_put_contents('includes/db_connect.php', $db_config);
        } else {
            $error = "Error executing SQL: " . $conn->error;
        }
    } else {
        $error = "Error creating database: " . $conn->error;
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini-Mart Inventory - Setup</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .setup-container {
            max-width: 500px;
        }
        
        .setup-header {
            background-color: var(--primary-color);
            padding: 1.5rem;
            color: white;
            text-align: center;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }
        
        .setup-content {
            padding: 1.5rem;
        }
        
        .step {
            margin-bottom: 1.5rem;
        }
        
        .step-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="card">
            <div class="setup-header">
                <h1>Mini-Mart Inventory Setup</h1>
                <p>Configure your database connection</p>
            </div>
            
            <div class="setup-content">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <p class="text-center">
                        <a href="index.php" class="btn btn-primary">Go to Login</a>
                    </p>
                <?php else: ?>
                    <div class="step">
                        <h3><span class="step-number">1</span> Requirements</h3>
                        <p>Make sure you have XAMPP installed and both Apache and MySQL services are running.</p>
                    </div>
                    
                    <div class="step">
                        <h3><span class="step-number">2</span> Database Information</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="servername">Server Name</label>
                                <input type="text" id="servername" name="servername" value="localhost" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="username">Database Username</label>
                                <input type="text" id="username" name="username" value="root" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password">Database Password</label>
                                <input type="password" id="password" name="password" placeholder="Usually blank for XAMPP">
                            </div>
                            
                            <div class="form-group">
                                <label for="dbname">Database Name</label>
                                <input type="text" id="dbname" name="dbname" value="mini_mart" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">Set Up Database</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>