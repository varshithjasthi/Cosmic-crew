<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: register.php?error=All fields are required");
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=Invalid email format");
        exit();
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            header("Location: register.php?error=Email already exists");
            exit();
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashed_password]);

        if ($result) {
            // Get the new user's ID
            $user_id = $pdo->lastInsertId();
            
            // Log in the user
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            header("Location: dashboard.php?success=Registration successful");
            exit();
        } else {
            throw new Exception("Failed to create user");
        }
    } catch(Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        header("Location: register.php?error=Registration failed. Please try again.");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
} 