<?php

$host = 'localhost';
$database = 'car_workshop_db';
$username = 'root';
$password = '';

$dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    throw $exception;
}
