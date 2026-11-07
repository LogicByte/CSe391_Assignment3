<?php

session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_dashboard.php', true, 303);
    exit;
}

$loginFailed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;

    if (
        is_string($username)
        && is_string($password)
        && hash_equals('admin', $username)
        && hash_equals('admin123', $password)
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin_dashboard.php', true, 303);
        exit;
    }

    $loginFailed = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1e293b">
    <title>Admin Login | AutoCare Workshop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="AutoCare Workshop home">
            <span class="brand-mark" aria-hidden="true">A</span>
            <span>AutoCare <span class="brand-light">Workshop</span></span>
        </a>
        <span class="header-note">Workshop administration</span>
    </header>

    <main class="booking-layout">
        <section class="intro" aria-labelledby="page-title">
            <p class="eyebrow">Admin portal</p>
            <h1 id="page-title">Welcome back.</h1>
            <p class="intro-copy">Sign in to manage workshop appointments.</p>
        </section>

        <section class="card booking-card admin-login-card animate-in" aria-labelledby="login-title">
            <div class="card-heading">
                <div>
                    <p class="eyebrow">Secure access</p>
                    <h2 id="login-title">Administrator login</h2>
                </div>
                <span class="card-index" aria-hidden="true">01</span>
            </div>

            <?php if ($loginFailed): ?>
                <p class="form-alert" role="alert">The username or password is incorrect. Please try again.</p>
            <?php endif; ?>

            <form action="admin_login.php" method="post">
                <div class="form-grid">
                    <div class="field field-full">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" autocomplete="username" required>
                    </div>
                    <div class="field field-full">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                </div>
                <div class="form-footer">
                    <p class="form-note">Administrator access only.</p>
                    <button class="btn-primary" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button>
                </div>
            </form>
        </section>
        <footer class="page-footer">AutoCare Workshop <span aria-hidden="true">&middot;</span> Administration</footer>
    </main>
</body>
</html>
