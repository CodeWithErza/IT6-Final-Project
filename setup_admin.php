<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/config/db.php';

// Admin credentials
$username = 'admin';
$password = 'admin123';  // This will be the password to login
$role = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing admin user
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $username);
        if ($stmt->execute()) {
            echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>";
            echo "<h2 style='color: #4CAF50;'>Admin Password Updated Successfully!</h2>";
            echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
            echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
            echo "<p style='margin-top: 20px; color: #666;'>You can now use these credentials to log in to the system.</p>";
            echo "</div>";
        } else {
            echo "<div style='color: #f44336; padding: 20px;'>Error updating admin: " . htmlspecialchars($conn->error) . "</div>";
        }
    } else {
        // Create new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        if ($stmt->execute()) {
            echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>";
            echo "<h2 style='color: #4CAF50;'>Admin User Created Successfully!</h2>";
            echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
            echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
            echo "<p style='margin-top: 20px; color: #666;'>You can now use these credentials to log in to the system.</p>";
            echo "</div>";
        } else {
            echo "<div style='color: #f44336; padding: 20px;'>Error creating admin: " . htmlspecialchars($conn->error) . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: #f44336; padding: 20px;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?> 