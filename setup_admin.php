<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'config/database.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';  // This will be the password to login
$role = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Update existing admin user
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        
        echo "<div style='text-align: center; padding: 20px; background-color: #e8f5e9; border-radius: 8px; margin: 20px;'>";
        echo "<h2 style='color: #2e7d32;'>Admin Password Updated Successfully!</h2>";
        echo "<p style='margin: 10px 0;'><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p style='margin: 10px 0;'><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "</div>";
    } else {
        // Create new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $role]);
        
        echo "<div style='text-align: center; padding: 20px; background-color: #e8f5e9; border-radius: 8px; margin: 20px;'>";
        echo "<h2 style='color: #2e7d32;'>Admin User Created Successfully!</h2>";
        echo "<p style='margin: 10px 0;'><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p style='margin: 10px 0;'><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div style='text-align: center; padding: 20px; background-color: #ffebee; border-radius: 8px; margin: 20px;'>";
    echo "<h2 style='color: #c62828;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// Add a link to go to login page
echo "<div style='text-align: center; margin-top: 20px;'>";
echo "<a href='/ERC-POS/views/auth/login.php' style='display: inline-block; padding: 10px 20px; background-color: #333; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
echo "</div>";
?> 