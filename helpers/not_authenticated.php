<?php

session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['access_token'])) {
    header("Location: /ERC-POS/views/dashboard/index.php");
    exit;
}
