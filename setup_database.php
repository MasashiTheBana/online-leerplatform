<?php
/**
 * Database Setup Script
 * This script will create the database and import the schema
 */

// Database connection settings (without database name)
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'leerplatform';

echo "<h1>Database Setup</h1>";

try {
    // Connect to MySQL server (without selecting database)
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✓ Connected to MySQL server</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "<p>Database '$dbname' already exists.</p>";
        echo "<p><a href='test_connection.php'>Test Connection</a></p>";
    } else {
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color: green;'>✓ Database '$dbname' created</p>";
        
        // Select database
        $pdo->exec("USE `$dbname`");
        
        // Read and execute SQL file
        $sqlFile = __DIR__ . '/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Remove comments and split by semicolons
            $sql = preg_replace('/--.*$/m', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            $executed = 0;
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                        $executed++;
                    } catch (PDOException $e) {
                        // Ignore errors for DROP TABLE IF EXISTS and similar
                        if (strpos($e->getMessage(), 'does not exist') === false) {
                            echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
                        }
                    }
                }
            }
            
            echo "<p style='color: green;'>✓ Executed $executed SQL statements</p>";
            echo "<p style='color: green;'><strong>Database setup complete!</strong></p>";
            echo "<p><a href='login.php'>Go to Login Page</a></p>";
        } else {
            echo "<p style='color: red;'>✗ database.sql file not found!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<h2>Manual Setup:</h2>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin: <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a></li>";
    echo "<li>Create a new database named 'leerplatform'</li>";
    echo "<li>Import the database.sql file</li>";
    echo "</ol>";
}


