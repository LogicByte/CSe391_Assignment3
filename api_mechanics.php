<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';

    $requestedDate = $_GET['date'] ?? date('Y-m-d');
    $date = is_string($requestedDate) && $requestedDate !== ''
        ? $requestedDate
        : date('Y-m-d');

    $statement = $pdo->prepare(
        'SELECT m.id, m.name, m.specialty, COUNT(a.id) as booked
         FROM mechanics m
         LEFT JOIN appointments a ON m.id = a.mechanic_id AND a.appointment_date = :date
         GROUP BY m.id'
    );
    $statement->execute(['date' => $date]);

    $mechanics_array = [];
    foreach ($statement->fetchAll() as $row) {
        $slots = max(0, 4 - (int) $row['booked']);
        $mechanics_array[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'specialty' => $row['specialty'],
            'slots' => $slots,
        ];
    }

    echo json_encode($mechanics_array, JSON_THROW_ON_ERROR);
    exit;
} catch (Throwable $exception) {
    error_log('Unable to generate mechanics API response: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Mechanics are temporarily unavailable.']);
    exit;
}
