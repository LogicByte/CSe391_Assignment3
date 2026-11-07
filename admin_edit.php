<?php

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php', true, 303);
    exit;
}

$appointmentId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($appointmentId === false) {
    http_response_code(400);
    exit('A valid appointment ID is required.');
}

try {
    require_once __DIR__ . '/config.php';
    $appointmentStatement = $pdo->prepare(
        'SELECT id, appointment_date, mechanic_id
         FROM appointments
         WHERE id = :id'
    );
    $appointmentStatement->execute(['id' => $appointmentId]);
    $appointment = $appointmentStatement->fetch();
} catch (PDOException $exception) {
    error_log('Unable to load appointment for editing: ' . $exception->getMessage());
    http_response_code(500);
    exit('The appointment could not be loaded. Please try again later.');
}

if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found.');
}

function isValidEditDate(string $date): bool
{
    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTimeImmutable::getLastErrors();

    return $parsedDate !== false
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0))
        && $parsedDate->format('Y-m-d') === $date;
}

function escapeEditHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$selectedDate = $appointment['appointment_date'];
$selectedMechanicId = (int) $appointment['mechanic_id'];
$formError = null;
$saveSucceeded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedDate = $_POST['appointment_date'] ?? null;
    $postedMechanicId = filter_var(
        $_POST['mechanic_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (is_string($postedDate) && isValidEditDate($postedDate)) {
        $selectedDate = $postedDate;
    }

    if ($postedMechanicId !== false) {
        $selectedMechanicId = $postedMechanicId;
    }

    if (!is_string($postedDate) || !isValidEditDate($postedDate) || $postedMechanicId === false) {
        $formError = 'Choose a valid appointment date and mechanic.';
    } else {
        try {
            $pdo->beginTransaction();

            $mechanicStatement = $pdo->prepare(
                'SELECT id FROM mechanics WHERE id = :mechanic_id FOR UPDATE'
            );
            $mechanicStatement->execute(['mechanic_id' => $postedMechanicId]);

            if (!$mechanicStatement->fetch()) {
                $pdo->rollBack();
                $formError = 'The selected mechanic is unavailable. Please choose another.';
            } else {
                $capacityStatement = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM appointments
                     WHERE mechanic_id = :mechanic_id
                       AND appointment_date = :appointment_date
                       AND id <> :appointment_id'
                );
                $capacityStatement->execute([
                    'mechanic_id' => $postedMechanicId,
                    'appointment_date' => $postedDate,
                    'appointment_id' => $appointmentId,
                ]);

                if ((int) $capacityStatement->fetchColumn() >= 4) {
                    $pdo->rollBack();
                    $formError = 'That mechanic is fully booked on the selected date. Choose another date or mechanic.';
                } else {
                    $updateStatement = $pdo->prepare(
                        'UPDATE appointments
                         SET appointment_date = :appointment_date,
                             mechanic_id = :mechanic_id
                         WHERE id = :id'
                    );
                    $updateStatement->execute([
                        'appointment_date' => $postedDate,
                        'mechanic_id' => $postedMechanicId,
                        'id' => $appointmentId,
                    ]);
                    $pdo->commit();
                    header('Location: admin_dashboard.php?success=updated', true, 303);
                    exit;
                }
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Unable to update appointment: ' . $exception->getMessage());
            $formError = 'The appointment could not be updated right now. Please try again later.';
        }
    }
} elseif (isset($_GET['appointment_date']) && is_string($_GET['appointment_date'])) {
    if (isValidEditDate($_GET['appointment_date'])) {
        $selectedDate = $_GET['appointment_date'];
    }

    $requestedMechanicId = filter_var(
        $_GET['mechanic_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($requestedMechanicId !== false) {
        $selectedMechanicId = $requestedMechanicId;
    }
}

try {
    $mechanicsStatement = $pdo->prepare(
        'SELECT m.id, m.name, m.specialty, COUNT(a.id) AS booked_slots
         FROM mechanics AS m
         LEFT JOIN appointments AS a
            ON a.mechanic_id = m.id
            AND a.appointment_date = :appointment_date
            AND a.id <> :appointment_id
         GROUP BY m.id, m.name, m.specialty
         ORDER BY m.name'
    );
    $mechanicsStatement->execute([
        'appointment_date' => $selectedDate,
        'appointment_id' => $appointmentId,
    ]);
    $mechanics = $mechanicsStatement->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load mechanics for appointment editing: ' . $exception->getMessage());
    http_response_code(500);
    exit('Mechanic availability could not be loaded. Please try again later.');
}

$selectedMechanicAvailable = false;
foreach ($mechanics as $mechanic) {
    if (
        (int) $mechanic['id'] === $selectedMechanicId
        && (int) $mechanic['booked_slots'] < 4
    ) {
        $selectedMechanicAvailable = true;
        break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e293b">
    <title>Edit Appointment | AutoCare Workshop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AutoCare Workshop home">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>AutoCare <span class="brand-light">Workshop</span></span>
        </a>
        <a class="btn-primary btn-secondary" href="admin_dashboard.php">Back to appointments</a>
    </header>

    <main class="booking-layout">
        <section class="intro" aria-labelledby="page-title">
            <p class="eyebrow">Workshop administration</p>
            <h1 id="page-title">Edit appointment</h1>
            <p class="intro-copy">Change the booking date or assigned mechanic.</p>
        </section>

        <section class="card booking-card animate-in" aria-labelledby="form-title">
            <div class="card-heading">
                <div>
                    <p class="eyebrow">Appointment #<?= (int) $appointmentId ?></p>
                    <h2 id="form-title">Booking details</h2>
                </div>
                <span class="card-index" aria-hidden="true">EDIT</span>
            </div>

            <?php if ($formError !== null): ?>
                <p class="form-alert" role="alert"><?= escapeEditHtml($formError) ?></p>
            <?php endif; ?>

            <form action="admin_edit.php?id=<?= (int) $appointmentId ?>" method="post">
                <div class="form-grid">
                    <div class="field">
                        <label for="appointment_date">Appointment Date</label>
                        <input
                            id="appointment_date"
                            name="appointment_date"
                            type="date"
                            value="<?= escapeEditHtml($selectedDate) ?>"
                            required
                        >
                    </div>
                    <div class="field">
                        <label for="mechanic_id">Mechanic</label>
                        <select id="mechanic_id" name="mechanic_id" required>
                            <option value="" disabled <?= !$selectedMechanicAvailable ? 'selected' : '' ?>>
                                Choose an available mechanic
                            </option>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <?php
                                $slotsAvailable = max(0, 4 - (int) $mechanic['booked_slots']);
                                $mechanicId = (int) $mechanic['id'];
                                $isSelected = $selectedMechanicAvailable && $mechanicId === $selectedMechanicId;
                                $mechanicLabel = $mechanic['name'] . ' (' . $mechanic['specialty'] . ') - '
                                    . $slotsAvailable . ' ' . ($slotsAvailable === 1 ? 'slot' : 'slots')
                                    . ' available' . ($slotsAvailable === 0 ? ' (FULL)' : '');
                                ?>
                                <option
                                    value="<?= $mechanicId ?>"
                                    <?= $slotsAvailable === 0 ? 'disabled' : '' ?>
                                    <?= $isSelected ? 'selected' : '' ?>
                                >
                                    <?= escapeEditHtml($mechanicLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="field-hint">Availability is calculated for the selected date. Each mechanic can take four appointments per day.</small>
                    </div>
                </div>

                <div class="form-footer">
                    <button class="btn-primary btn-secondary" type="submit" formmethod="get">Check availability</button>
                    <button class="btn-primary" type="submit">Save changes <span aria-hidden="true">&rarr;</span></button>
                </div>
            </form>
        </section>
        <footer class="page-footer">AutoCare Workshop <span aria-hidden="true">&middot;</span> Administration</footer>
    </main>
</body>
</html>
