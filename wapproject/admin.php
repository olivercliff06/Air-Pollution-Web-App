<?php
require_once __DIR__ . '/config.php';

if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$totalUsers = get_total_users();
$totalPosts = get_total_posts();
$reported   = get_reported_posts();
$labels     = category_labels();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pollution Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum - Admin</h1>
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
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'admin.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'admin.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="profile-section">
                <div class="card profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <span class="avatar-icon">👑</span>
                        </div>
                        <div class="profile-info">
                            <h1 class="profile-username">Admin Dashboard</h1>
                            <p class="profile-role">Overview of forum activity</p>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo (int)$totalUsers; ?></span>
                                    <span class="stat-label">Total Users</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo (int)$totalPosts; ?></span>
                                    <span class="stat-label">Total Posts</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo count($reported); ?></span>
                                    <span class="stat-label">Reported Posts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card posts-section-card" style="margin-top: 1.5rem;">
                    <h2 class="section-title">Reported Posts</h2>
                    <?php if (empty($reported)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">✅</div>
                            <h3>No reported posts</h3>
                            <p>Everything looks clean. Users have not reported any posts.</p>
                        </div>
                    <?php else: ?>
                        <div class="posts-list-container">
                            <?php foreach ($reported as $item): ?>
                                <?php
                                $pDate = new DateTime($item['last_reported_at'] ?? 'now');
                                $pFormatted = $pDate->format('M j, Y H:i');
                                ?>
                                <a href="post-detail.php?id=<?php echo (int)$item['post_id']; ?>&category=<?php echo urlencode($item['category']); ?>" class="post-list-item">
                                    <div class="post-list-content">
                                        <div class="post-list-category">
                                            <span class="post-category-badge">
                                                <?php echo h($labels[$item['category']] ?? $item['category']); ?>
                                            </span>
                                        </div>
                                        <h3 class="post-list-title"><?php echo h($item['title']); ?></h3>
                                        <div class="post-list-meta">
                                            <span class="post-list-author">👤 <?php echo h($item['author']); ?></span>
                                            <span class="post-list-date">Last reported: <?php echo h($pFormatted); ?></span>
                                            <span class="post-list-comments">🚩 <?php echo (int)$item['report_count']; ?> reports</span>
                                        </div>
                                        <div class="post-list-meta" style="margin-top: 0.25rem; font-size: 0.85rem; color: var(--text-secondary);">
                                            <?php if (!empty($item['report_types'])): ?>
                                                <div>Issue types: <?php echo h($item['report_types']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($item['sample_reason'])): ?>
                                                <div>Example reason: "<?php echo h($item['sample_reason']); ?>"</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
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


