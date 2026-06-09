<?php
require_once __DIR__ . '/config.php';

// Only admins can access this page
if (!is_admin()) {
    header('Location: index.php#learning');
    exit;
}

$articles = load_articles();

// Normalize articles by id for easier lookup
function find_article_index_by_id(array $articles, string $id): ?int {
    foreach ($articles as $index => $article) {
        if (($article['id'] ?? '') === $id) {
            return $index;
        }
    }
    return null;
}

$mode     = $_GET['mode'] ?? 'list'; // 'list' | 'edit'
$editId   = $_GET['id'] ?? null;
$error    = null;
$success  = null;
$editing  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode   = 'edit';
    $editId = $_POST['id'] ?? null;

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $content     = $_POST['content'] ?? '';

    if ($title === '' || $description === '' || $content === '') {
        $error = 'Please fill in title, description, and content.';
    } else {
        // Handle optional image upload
        $newImagePath = null;
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['image']['tmp_name'];
            $name    = basename($_FILES['image']['name']);
            $ext     = pathinfo($name, PATHINFO_EXTENSION);
            $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            if ($safeExt !== '') {
                $target = UPLOADS_DIR . '/article_' . time() . '.' . $safeExt;
                if (move_uploaded_file($tmpName, $target)) {
                    $newImagePath = 'uploads/' . basename($target);
                }
            }
        }

        // New article
        if ($editId === '' || $editId === null) {
            $newId = strtolower(preg_replace('/[^a-z0-9\-]+/i', '-', $title));
            if ($newId === '' || find_article($newId) !== null) {
                $newId = $newId . '-' . time();
            }

            $articleData = [
                'id'          => $newId,
                'title'       => $title,
                'description' => $description,
                'content'     => $content,
            ];

            if ($newImagePath !== null) {
                $articleData['image'] = $newImagePath;
            }

            $articles[] = $articleData;

            save_articles($articles);
            $success = 'Article created.';
            $editId  = $newId;
        } else {
            $idx = find_article_index_by_id($articles, $editId);
            if ($idx === null) {
                $error = 'Article not found.';
            } else {
                $articles[$idx]['title']       = $title;
                $articles[$idx]['description'] = $description;
                $articles[$idx]['content']     = $content;
                if ($newImagePath !== null) {
                    $articles[$idx]['image'] = $newImagePath;
                }
                save_articles($articles);
                $success = 'Article updated.';
            }
        }
    }
}

if ($mode === 'edit' && $editId) {
    $editing = find_article($editId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Articles - Pollution Forum</title>
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
                <div class="nav-auth">
                    <?php if (is_logged_in()): ?>
                        <?php $user = current_user(); ?>
                        <div class="user-info">
                            <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" class="username-link">
                                <?php echo h($user['username']); ?><?php echo is_admin() ? ' (Admin)' : ''; ?>
                            </a>
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'article-admin.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'article-admin.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="back-button-section">
                <a href="index.php#learning" class="btn-back">← Back to Learning Corner</a>
            </div>

            <section class="article-section">
                <div class="card">
                    <div class="learning-header">
                        <h2 class="section-title">Manage Learning Articles</h2>
                        <a href="article-admin.php?mode=edit" class="btn btn-primary">+ New Article</a>
                    </div>

                    <?php if ($error): ?>
                        <p style="color: #d32f2f; margin-bottom: 1rem;"><?php echo h($error); ?></p>
                    <?php elseif ($success): ?>
                        <p style="color: #388e3c; margin-bottom: 1rem;"><?php echo h($success); ?></p>
                    <?php endif; ?>

                    <?php if ($mode === 'edit'): ?>
                        <?php
                        $title       = $editing['title'] ?? '';
                        $description = $editing['description'] ?? '';
                        $content     = $editing['content'] ?? '';
                        $image       = $editing['image'] ?? null;
                        ?>
                        <form method="post" class="article-edit-form" enctype="multipart/form-data">
                            <input type="hidden" name="id" value="<?php echo $editing['id'] ?? ''; ?>">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" required value="<?php echo h($title); ?>">
                            </div>
                            <div class="form-group">
                                <label for="description">Short Description</label>
                                <input type="text" id="description" name="description" required value="<?php echo h($description); ?>">
                            </div>
                            <div class="form-group">
                                <label for="content">Content (HTML allowed)</label>
                                <textarea id="content" name="content" rows="10" required><?php echo h($content); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image">Header Image (optional)</label>
                                <input type="file" id="image" name="image" accept="image/*">
                                <?php if ($image): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <span style="font-size: 0.85rem;">Current image:</span><br>
                                        <img src="<?php echo h($image); ?>" alt="Current article image" style="max-width: 200px; border-radius: 8px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Article</button>
                        </form>
                    <?php else: ?>
                        <div class="articles-grid">
                            <?php if (empty($articles)): ?>
                                <p>No articles yet. Click "New Article" to add one.</p>
                            <?php else: ?>
                                <?php foreach ($articles as $article): ?>
                                    <div class="article-card">
                                        <div class="article-card-icon">
                                            <?php
                                            $parts = explode(' ', $article['title']);
                                            echo h($parts[0]);
                                            ?>
                                        </div>
                                        <h3><?php echo h($article['title']); ?></h3>
                                        <p><?php echo h($article['description']); ?></p>
                                        <div style="margin-top: 0.5rem;">
                                            <a href="article.php?topic=<?php echo urlencode($article['id']); ?>" class="article-link-text">View →</a>
                                            <span style="margin: 0 0.5rem;">|</span>
                                            <a href="article-admin.php?mode=edit&id=<?php echo urlencode($article['id']); ?>" class="article-link-text">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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


