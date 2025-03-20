<?php
require_once 'config.php';

try {
    // Drop existing tables if they exist
    $pdo->exec("DROP TABLE IF EXISTS expenses");
    $pdo->exec("DROP TABLE IF EXISTS users");

    // Create users table
    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create expenses table
    $pdo->exec("CREATE TABLE expenses (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description VARCHAR(255),
        category VARCHAR(50),
        date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    echo "Database tables created successfully!";
} catch(PDOException $e) {
    echo "Error creating database tables: " . $e->getMessage();
}
?> 