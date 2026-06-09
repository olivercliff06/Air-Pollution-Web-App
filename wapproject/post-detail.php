<?php
require_once __DIR__ . '/config.php';

$labels  = category_labels();
$postId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$fromNew = ($_GET['from'] ?? '') === 'new';
$categoryFromUrl = $_GET['category'] ?? null;

$post  = $postId ? find_post($postId) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $postId && $post) {
    if (!is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'post-detail.php'));
        exit;
    }

    $action = $_POST['action'] ?? 'add_comment';
    $user   = current_user();

    if ($action === 'delete_post') {
        if (delete_post_as_user($postId, $user['username'])) {
            $category = $categoryFromUrl ?? ($post['category'] ?? null);
            if ($category) {
                header('Location: forum-posts.php?category=' . urlencode($category));
            } else {
                header('Location: index.php#categories');
            }
            exit;
        }
    } elseif ($action === 'delete_comment') {
        $commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
        if ($commentId > 0) {
            $deletedPostId = delete_comment_as_user($commentId, $user['username']);
            if ($deletedPostId !== null) {
                header('Location: post-detail.php?id=' . $postId . '&category=' . urlencode($categoryFromUrl ?? ($post['category'] ?? '')));
                exit;
            }
        }
    } elseif ($action === 'report_post') {
        $type   = $_POST['report_type'] ?? 'other';
        $reason = trim($_POST['reason'] ?? '');
        if ($reason === '') {
            $reason = null;
        }
        report_post($postId, $user['username'], $type, $reason);
        header('Location: post-detail.php?id=' . $postId . '&category=' . urlencode($categoryFromUrl ?? ($post['category'] ?? '')));
        exit;
    } else { // add_comment
        $content = trim($_POST['comment_content'] ?? '');
        if ($content !== '') {
            add_comment_to_post($postId, $user['username'], $content);

            header('Location: post-detail.php?id=' . $postId . '&category=' . urlencode($categoryFromUrl ?? ($post['category'] ?? '')));
            exit;
        }
    }
}

// Re-fetch post after potential update
$post = $postId ? find_post($postId) : null;

// Determine category for back link
$category = $categoryFromUrl ?? ($post['category'] ?? null);
$backHref = 'javascript:history.back()';
if ($fromNew && $category) {
    $backHref = 'forum-posts.php?category=' . urlencode($category);
} elseif ($category) {
    $backHref = 'forum-posts.php?category=' . urlencode($category);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Details - Pollution Forum</title>
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
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'post-detail.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'post-detail.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'post-detail.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="back-button-section" style="display: flex; justify-content: space-between; align-items: center; gap: 0.75rem;">
                <a href="<?php echo h($backHref); ?>" class="btn-back">← Back to Discussions</a>
                <?php if (is_logged_in() && $post): ?>
                    <?php $currentUserTop = current_user(); ?>
                    <?php if (
                        $currentUserTop &&
                        (
                            strcasecmp($currentUserTop['username'], $post['author']) === 0
                            || is_admin()
                        )
                    ): ?>
                        <form method="post" class="post-delete-form" onsubmit="return confirm('Are you sure you want to delete this post? This cannot be undone.');" style="margin: 0;">
                            <input type="hidden" name="action" value="delete_post">
                            <button type="submit" class="btn btn-danger">Delete Post</button>
                        </form>
                    <?php else: ?>
                        <form method="post" class="post-report-form" onsubmit="return confirm('Report this post to admins?');" style="margin: 0; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <input type="hidden" name="action" value="report_post">
                            <label for="report_type" style="font-size: 0.85rem;">Issue:</label>
                            <select name="report_type" id="report_type" required style="font-size: 0.85rem; padding: 0.25rem 0.5rem;">
                                <option value="">Select...</option>
                                <option value="misinformation">Misinformation</option>
                                <option value="spam">Spam</option>
                                <option value="scam">Scam</option>
                                <option value="other">Other</option>
                            </select>
                            <input
                                type="text"
                                name="reason"
                                placeholder="Why is this post an issue?"
                                style="font-size: 0.85rem; padding: 0.25rem 0.5rem; min-width: 180px; max-width: 260px; border-radius: 6px; border: 1px solid var(--border-color);"
                                required
                            >
                            <button type="submit" class="btn btn-outline">Report Post</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <section class="post-detail-section">
                <div id="postDetailContainer" class="post-detail-container">
                    <?php if (!$post): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <h3>Post not found</h3>
                            <p>The requested post does not exist.</p>
                            <a href="index.php#categories" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Browse Categories</a>
                        </div>
                    <?php else: ?>
                        <?php
                        $date = new DateTime($post['date'] ?? 'now');
                        $formattedDate = $date->format('F j, Y H:i');
                        $comments = isset($post['comments']) && is_array($post['comments']) ? $post['comments'] : [];
                        $commentCount = count($comments);

                        $images = [];
                        if (!empty($post['images']) && is_array($post['images'])) {
                            $images = $post['images'];
                        } elseif (!empty($post['image'])) {
                            $images = [$post['image']];
                        }
                        ?>
                        <article class="post-card post-detail-card">
                            <div class="post-header">
                                <div>
                                    <div class="post-detail-category">
                                        <span class="post-category-badge">
                                            <?php echo h($labels[$post['category']] ?? $post['category']); ?>
                                        </span>
                                    </div>
                                    <h3 class="post-title"><?php echo h($post['title']); ?></h3>
                                    <div class="post-meta">
                                        <a href="profile.php?user=<?php echo urlencode($post['author']); ?>" class="post-author">
                                            👤 <?php echo h($post['author']); ?>
                                        </a>
                                        <span class="post-date">📅 <?php echo h($formattedDate); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="post-content"><?php echo nl2br(h($post['content'])); ?></div>

                            <?php if (!empty($images)): ?>
                                <?php if (count($images) === 1): ?>
                                    <div class="post-image-container">
                                        <img src="<?php echo h($images[0]); ?>" alt="Post image" class="post-image">
                                    </div>
                                <?php else: ?>
                                    <div class="post-images-grid">
                                        <?php foreach ($images as $i => $img): ?>
                                            <div class="post-image-item">
                                                <img src="<?php echo h($img); ?>" alt="Post image <?php echo $i + 1; ?>" class="post-image">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="comments-section">
                                <div class="comments-header">
                                    <h4 class="comments-title">Discussion</h4>
                                    <span class="comment-count">
                                        <?php echo $commentCount; ?> <?php echo $commentCount === 1 ? 'comment' : 'comments'; ?>
                                    </span>
                                </div>

                                <?php if (is_logged_in()): ?>
                                    <form class="comment-form" method="post">
                                        <input type="hidden" name="action" value="add_comment">
                                        <textarea class="comment-textarea comment-content-input" name="comment_content" placeholder="Write a comment..." required></textarea>
                                        <button type="submit" class="btn btn-comment">Post Comment</button>
                                    </form>
                                <?php else: ?>
                                    <div class="comment-login-prompt">
                                        <p>
                                            Please
                                            <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'post-detail.php'); ?>">Login</a>
                                            to post a comment.
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="comments-list">
                                    <?php if (empty($comments)): ?>
                                        <p class="empty-state">No comments yet. Be the first to comment!</p>
                                    <?php else: ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <?php
                                            $cDate = new DateTime($comment['date'] ?? 'now');
                                            $cFormatted = $cDate->format('M j, Y H:i');
                                            ?>
                                            <div class="comment-card">
                                                <div class="comment-header">
                                                    <a href="profile.php?user=<?php echo urlencode($comment['author']); ?>" class="comment-author">
                                                        <?php echo h($comment['author']); ?>
                                                    </a>
                                                    <span class="comment-date"><?php echo h($cFormatted); ?></span>
                                                    <?php if (is_logged_in()): ?>
                                                        <?php $currentUser = current_user(); ?>
                                                        <?php if (
                                                            $currentUser &&
                                                            (
                                                                strcasecmp($currentUser['username'], $comment['author']) === 0
                                                                || is_admin()
                                                            )
                                                        ): ?>
                                                            <form method="post" class="comment-delete-form" onsubmit="return confirm('Delete this comment?');" style="display: inline-block; margin-left: 0.5rem;">
                                                                <input type="hidden" name="action" value="delete_comment">
                                                                <input type="hidden" name="comment_id" value="<?php echo (int)$comment['id']; ?>">
                                                                <button type="submit" class="btn btn-link btn-delete-comment">Delete</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="comment-content"><?php echo nl2br(h($comment['content'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
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


