<?php

$today = date('Y-m-d');
$dailyCapacity = 4;

function escapeHtml(string $value): string
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
    <title>Book a Mechanic | AutoCare Workshop</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AutoCare Workshop home">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>AutoCare <span class="brand-light">Workshop</span></span>
        </a>
        <span class="header-note">Independent service. Expert care.</span>
    </header>

    <main class="booking-layout">
        <section class="intro" aria-labelledby="page-title">
            <p class="eyebrow">Workshop appointments</p>
            <h1 id="page-title">AutoCare Workshop - Book Your Mechanic</h1>
            <p class="intro-copy">Tell us about your vehicle and choose a mechanic for your next visit.</p>
        </section>

        <section class="card booking-card animate-in" aria-labelledby="form-title">
            <div class="card-heading">
                <div>
                    <p class="eyebrow">Service request</p>
                    <h2 id="form-title">Appointment details</h2>
                </div>
                <span class="card-index" aria-hidden="true">01</span>
            </div>

            <form action="process_booking.php" method="post">
                <?php
                $bookingErrors = [
                    'duplicate' => 'Error: You already have an appointment on this date!',
                    'full' => 'Error: This mechanic is fully booked for this date!',
                    'db' => 'Error: Database error occurred.',
                ];
                $errorCode = $_GET['error'] ?? null;
                if (is_string($errorCode) && isset($bookingErrors[$errorCode])):
                ?>
                    <p class="form-alert" role="alert"><?= escapeHtml($bookingErrors[$errorCode]) ?></p>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <p class="form-success" role="status">Success: Appointment booked successfully!</p>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field">
                        <label for="client_name">Client Name</label>
                        <input id="client_name" name="client_name" type="text" autocomplete="name" required>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="tel" autocomplete="tel" required>
                    </div>

                    <div class="field field-full">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" autocomplete="street-address" required></textarea>
                    </div>

                    <div class="field">
                        <label for="license_no">Car License No</label>
                        <input id="license_no" name="license_no" type="text" required>
                    </div>

                    <div class="field">
                        <label for="engine_no">Car Engine No</label>
                        <input id="engine_no" name="engine_no" type="text" required>
                    </div>

                    <div class="field">
                        <label for="appointment-date">Appointment Date</label>
                        <input id="appointment-date" name="appointment_date" type="date" min="<?= escapeHtml($today) ?>" value="<?= escapeHtml($today) ?>" required>
                    </div>

                    <div class="field">
                        <label for="mechanic-dropdown">Select Mechanic</label>
                        <select id="mechanic-dropdown" name="mechanic_id" required disabled>
                            <option value="" disabled selected>Choose an available mechanic</option>
                        </select>
                        <small class="field-hint">Availability is shown for the selected date. Each mechanic can take <?= $dailyCapacity ?> appointments per day.</small>
                    </div>
                </div>

                <div class="form-footer">
                    <p class="form-note">All fields are required to request an appointment.</p>
                    <button class="btn-primary" type="submit">
                        Request appointment <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </form>
        </section>
    </main>
    <footer class="page-footer site-footer">
        <span>&copy; 2026 AutoCare Workshop</span>
        <span class="footer-divider" aria-hidden="true">&middot;</span>
        <a href="admin_login.php">Admin Login</a>
    </footer>
</body>
</html>
