<?php

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php', true, 303);
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? null) === 'logout'
) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin_login.php', true, 303);
    exit;
}

$appointments = [];
$appointmentsError = false;

try {
    require_once __DIR__ . '/config.php';
    $statement = $pdo->query(
        'SELECT a.id, a.client_name, a.phone, a.license_no, a.appointment_date, m.name AS mechanic_name
         FROM appointments AS a
         INNER JOIN mechanics AS m ON m.id = a.mechanic_id
         ORDER BY a.appointment_date, a.id'
    );
    $appointments = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Unable to load admin appointments: ' . $exception->getMessage());
    $appointmentsError = true;
}

function escapeAdminHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e293b">
    <title>Appointments | AutoCare Workshop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AutoCare Workshop home">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>AutoCare <span class="brand-light">Workshop</span></span>
        </a>
        <form action="admin_dashboard.php" method="post">
            <button class="btn-primary btn-secondary" type="submit" name="action" value="logout">Log out</button>
        </form>
    </header>

    <main class="admin-layout">
        <section class="admin-heading" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Workshop administration</p>
                <h1 id="page-title">Appointments</h1>
                <p class="intro-copy">Review bookings and assigned mechanics.</p>
            </div>
            <span class="admin-count"><?= count($appointments) ?> <?= count($appointments) === 1 ? 'appointment' : 'appointments' ?></span>
        </section>

        <section class="card admin-table-card" aria-label="Appointment list">
            <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
                <p class="form-success" role="status">Appointment updated successfully.</p>
            <?php endif; ?>
            <?php if ($appointmentsError): ?>
                <p class="form-alert" role="alert">Appointments could not be loaded right now. Please try again later.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th scope="col">Client Name</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Car License No (Registration)</th>
                                <th scope="col">Appointment Date</th>
                                <th scope="col">Mechanic Name</th>
                                <th scope="col"><span class="visually-hidden">Edit</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$appointments): ?>
                                <tr>
                                    <td class="table-empty" colspan="6">There are no appointments to display.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?= escapeAdminHtml($appointment['client_name']) ?></td>
                                        <td><?= escapeAdminHtml($appointment['phone']) ?></td>
                                        <td><?= escapeAdminHtml($appointment['license_no']) ?></td>
                                        <td><?= escapeAdminHtml($appointment['appointment_date']) ?></td>
                                        <td><?= escapeAdminHtml($appointment['mechanic_name']) ?></td>
                                        <td>
                                            <a class="btn-primary btn-edit" href="admin_edit.php?id=<?= (int) $appointment['id'] ?>">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <footer class="page-footer">AutoCare Workshop <span aria-hidden="true">&middot;</span> Administration</footer>
    </main>
</body>
</html>
