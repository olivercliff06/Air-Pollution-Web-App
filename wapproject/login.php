<?php
require_once __DIR__ . '/config.php';

$error    = null;
$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? 'index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $result   = handle_login_attempt($username, $password);

    if ($result['success']) {
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = $result['message'] ?? 'Login failed';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pollution Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="new-post.php" class="nav-link">New Post</a>
                <a href="index.php#quiz" class="nav-link">Quiz Game</a>
                <a href="index.php#learning" class="nav-link">Learning Corner</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="auth-section">
                <div class="card">
                    <h2>Login</h2>
                    <?php if ($error): ?>
                        <p style="color: #d32f2f; margin-bottom: 1rem;"><?php echo h($error); ?></p>
                    <?php endif; ?>
                    <form method="post" action="login.php">
                        <input type="hidden" name="redirect" value="<?php echo h($redirect); ?>">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <br>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 1rem;">
                            Admin: admin / admin123<br>
                            Don’t have an account?
                            <a href="signup.php?redirect=<?php echo urlencode($redirect); ?>">Sign up here</a>.
                        </p>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2025 Pollution Forum. Join the conversation to make a difference.</p>
        </div>
    </footer>
</body>
</html>


