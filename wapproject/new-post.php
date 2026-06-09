<?php
require_once __DIR__ . '/config.php';

require_login();

$labels   = category_labels();
$error    = null;
$categoryPrefill = $_GET['category'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['postTitle'] ?? '');
    $category = $_POST['postCategory'] ?? '';
    $content  = trim($_POST['postContent'] ?? '');

    if ($title === '' || $category === '' || $content === '') {
        $error = 'Please fill in all fields.';
    } elseif (!isset($labels[$category])) {
        $error = 'Invalid category selected.';
    } else {
        $user = current_user();

        // Handle multiple image uploads
        $images = [];
        if (!empty($_FILES['postImage']) && is_array($_FILES['postImage']['name'])) {
            $count = count($_FILES['postImage']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['postImage']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['postImage']['tmp_name'][$i];
                    $name    = basename($_FILES['postImage']['name'][$i]);
                    $ext     = pathinfo($name, PATHINFO_EXTENSION);
                    $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
                    $target  = UPLOADS_DIR . '/' . time() . '_' . $i . '.' . $safeExt;
                    if (move_uploaded_file($tmpName, $target)) {
                        $images[] = 'uploads/' . basename($target);
                    }
                }
            }
        }

        $newId = create_post($title, $category, $content, $images, $user['username']);

        header('Location: post-detail.php?id=' . $newId . '&from=new&category=' . urlencode($category));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Post - Pollution Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum</h1>
            <nav class="nav">
                <a href="index.php" class="nav-link">Home</a>
                <a href="new-post.php" class="nav-link active">New Post</a>
                <a href="index.php#quiz" class="nav-link">Quiz Game</a>
                <a href="index.php#learning" class="nav-link">Learning Corner</a>
                <div class="nav-auth">
                    <?php if (is_logged_in()): ?>
                        <?php $user = current_user(); ?>
                        <div class="user-info">
                            <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" class="username-link">
                                <?php echo h($user['username']); ?><?php echo is_admin() ? ' (Admin)' : ''; ?>
                            </a>
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'new-post.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'new-post.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'new-post.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section id="new-post" class="post-form-section">
                <div class="card">
                    <h2>Create New Forum Post</h2>
                    <?php if ($error): ?>
                        <p style="color: #d32f2f; margin-bottom: 1rem;"><?php echo h($error); ?></p>
                    <?php endif; ?>
                    <form id="postForm" class="post-form" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="postTitle">Post Title</label>
                            <input type="text" id="postTitle" name="postTitle" placeholder="Enter a title for your post..." required value="<?php echo isset($_POST['postTitle']) ? h($_POST['postTitle']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="postCategory">Category</label>
                            <select id="postCategory" name="postCategory" required>
                                <option value="">Select a category...</option>
                                <?php foreach ($labels as $key => $label): ?>
                                    <option value="<?php echo h($key); ?>" <?php
                                        $selected = $_POST['postCategory'] ?? $categoryPrefill;
                                        echo $selected === $key ? 'selected' : '';
                                    ?>><?php echo h($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="postContent">Post Content</label>
                            <textarea id="postContent" name="postContent" rows="6" placeholder="Share your thoughts about pollution..." required><?php echo isset($_POST['postContent']) ? h($_POST['postContent']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="postImage">Add Images (Optional - You can add multiple images)</label>
                            <input type="file" id="postImage" name="postImage[]" accept="image/*" multiple class="file-input">
                            <small style="color: var(--text-secondary); display: block; margin-top: 0.5rem;">Images will be uploaded when you submit the post.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Post</button>
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


