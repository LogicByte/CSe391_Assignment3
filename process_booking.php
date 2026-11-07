<?php

function redirectToBooking(string $status): void
{
    header('Location: index.php?' . $status, true, 303);
    exit;
}

try {
    require_once __DIR__ . '/config.php';
} catch (PDOException $exception) {
    redirectToBooking('error=db');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$fields = [
    'client_name' => 'client_name',
    'address' => 'address',
    'phone' => 'phone',
    'license' => 'license_no',
    'engine' => 'engine_no',
    'appointment_date' => 'appointment_date',
];
$booking = [];

foreach ($fields as $field => $postKey) {
    $value = $_POST[$postKey] ?? null;
    if (!is_string($value)) {
        redirectToBooking('error=invalid');
    }

    $booking[$field] = trim($value);
    if ($booking[$field] === '') {
        redirectToBooking('error=invalid');
    }
}

$mechanicId = filter_var(
    $_POST['mechanic_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$date = DateTimeImmutable::createFromFormat('!Y-m-d', $booking['appointment_date']);
$dateErrors = DateTimeImmutable::getLastErrors();

if (
    $mechanicId === false
    || $date === false
    || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
    || $date->format('Y-m-d') !== $booking['appointment_date']
    || $booking['appointment_date'] < date('Y-m-d')
    || strlen($booking['client_name']) < 3
    || strlen($booking['client_name']) > 100
    || strlen($booking['phone']) > 30
    || !preg_match('/^\d{10,15}$/', $booking['phone'])
    || strlen($booking['license']) > 50
    || !preg_match('/^[a-z0-9]+$/i', $booking['license'])
    || strlen($booking['engine']) > 50
    || !preg_match('/^\d+$/', $booking['engine'])
) {
    redirectToBooking('error=invalid');
}

try {
    $pdo->beginTransaction();

    $mechanicStatement = $pdo->prepare(
        'SELECT id FROM mechanics WHERE id = :mechanic_id FOR UPDATE'
    );
    $mechanicStatement->execute(['mechanic_id' => $mechanicId]);
    if (!$mechanicStatement->fetch()) {
        $pdo->rollBack();
        redirectToBooking('error=invalid');
    }

    $duplicateStatement = $pdo->prepare(
        'SELECT id
         FROM appointments
         WHERE appointment_date = :appointment_date
           AND (phone = :phone OR license_no = :license_no)
         LIMIT 1'
    );
    $duplicateStatement->execute([
        'appointment_date' => $booking['appointment_date'],
        'phone' => $booking['phone'],
        'license_no' => $booking['license'],
    ]);

    if ($duplicateStatement->fetch()) {
        $pdo->rollBack();
        redirectToBooking('error=duplicate');
    }

    $capacityStatement = $pdo->prepare(
        'SELECT COUNT(*) FROM appointments
         WHERE mechanic_id = :mechanic_id
           AND appointment_date = :appointment_date'
    );
    $capacityStatement->execute([
        'mechanic_id' => $mechanicId,
        'appointment_date' => $booking['appointment_date'],
    ]);

    if ((int) $capacityStatement->fetchColumn() >= 4) {
        $pdo->rollBack();
        redirectToBooking('error=full');
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO appointments
            (client_name, address, phone, license_no, engine_no, appointment_date, mechanic_id)
         VALUES
            (:client_name, :address, :phone, :license_no, :engine_no, :appointment_date, :mechanic_id)'
    );
    $insertStatement->execute([
        'client_name' => $booking['client_name'],
        'address' => $booking['address'],
        'phone' => $booking['phone'],
        'license_no' => $booking['license'],
        'engine_no' => $booking['engine'],
        'appointment_date' => $booking['appointment_date'],
        'mechanic_id' => $mechanicId,
    ]);

    $pdo->commit();
    redirectToBooking('success=booked');
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Unable to process appointment booking: ' . $exception->getMessage());
    redirectToBooking('error=db');
}
