<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Log the received data
    error_log("Received POST data: " . print_r($_POST, true));

    // Get the raw amount value before filtering
    $raw_amount = $_POST['amount'];
    
    // Validate and sanitize input with better handling for amount
    $amount = str_replace(',', '', $raw_amount); // Remove any commas
    $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
    $description = trim(htmlspecialchars($_POST['description']));
    $category = trim(htmlspecialchars($_POST['category']));
    $date = $_POST['date'];
    $user_id = $_SESSION['user_id'];

    // Debug: Log the processed amount
    error_log("Processed amount: " . $amount);

    // Validate amount with detailed error message
    if ($amount === false || $amount <= 0) {
        $error_msg = "Invalid amount: " . $raw_amount;
        error_log($error_msg);
        header("Location: dashboard.php?error=" . urlencode($error_msg));
        exit();
    }

    // Validate description
    if (empty($description)) {
        header("Location: dashboard.php?error=Please enter a description");
        exit();
    }

    // Validate category
    $valid_categories = ['Food', 'Transportation', 'Housing', 'Utilities', 'Entertainment', 'Healthcare', 'Shopping', 'Other'];
    if (!in_array($category, $valid_categories)) {
        header("Location: dashboard.php?error=Please select a valid category");
        exit();
    }

    // Validate date
    if (!strtotime($date)) {
        header("Location: dashboard.php?error=Please enter a valid date");
        exit();
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Debug: Log the SQL parameters
        error_log("Inserting expense with parameters: " . print_r([$user_id, $amount, $description, $category, $date], true));

        // Insert the expense
        $stmt = $pdo->prepare("INSERT INTO expenses (user_id, amount, description, category, date) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$user_id, $amount, $description, $category, $date]);

        if ($result) {
            // Debug: Log successful insertion
            error_log("Expense inserted successfully");
            $pdo->commit();
            header("Location: dashboard.php?success=Expense added successfully");
            exit();
        } else {
            throw new Exception("Failed to insert expense");
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_msg = "Error adding expense: " . $e->getMessage();
        error_log($error_msg);
        header("Location: dashboard.php?error=" . urlencode($error_msg));
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
} 