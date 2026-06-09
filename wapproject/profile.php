<?php
require_once __DIR__ . '/config.php';

$labels = category_labels();

$username = $_GET['user'] ?? null;
if (!$username && is_logged_in()) {
    $username = current_user()['username'];
}

$username = $username ? trim($username) : null;

$currentUser  = current_user();
$isOwnProfile = $currentUser && $username && strcasecmp($currentUser['username'], $username) === 0;

// Bio handling
$bio      = $username ? get_user_bio($username) : '';
$bioError = null;

if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $newBio = $_POST['bio'] ?? '';

    if (strlen($newBio) > 500) {
        $bioError = 'Bio must be at most 500 characters.';
    } else {
        update_user_bio($username, $newBio);
        $bio = $newBio;
    }
}

$posts = load_posts();
$userPosts = [];
$commentCount = 0;

if ($username) {
    foreach ($posts as $post) {
        if (($post['author'] ?? '') === $username) {
            $userPosts[] = $post;
        }
        if (!empty($post['comments']) && is_array($post['comments'])) {
            foreach ($post['comments'] as $comment) {
                if (($comment['author'] ?? '') === $username) {
                    $commentCount++;
                }
            }
        }
    }
}

$isAdminUser = $username === ADMIN_USERNAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $username ? h($username) . "'s Profile - Pollution Forum" : 'User Profile - Pollution Forum'; ?></title>
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
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'profile.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="signup.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'profile.php'); ?>">Sign Up</a>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'profile.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="back-button-section">
                <a href="javascript:history.back()" class="btn-back">← Back</a>
            </div>

            <section class="profile-section">
                <div class="card profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <span class="avatar-icon">👤</span>
                        </div>
                        <div class="profile-info">
                            <h1 class="profile-username">
                                <?php echo $username ? h($username) : 'User Not Found'; ?>
                            </h1>
                            <p class="profile-role" id="profileRole">
                                <?php if ($username): ?>
                                    <?php if ($isAdminUser): ?>
                                        👑 Administrator
                                    <?php else: ?>
                                        Community Member
                                    <?php endif; ?>
                                <?php else: ?>
                                    Unknown User
                                <?php endif; ?>
                            </p>
                            <?php if ($username): ?>
                                <div class="profile-bio" style="margin-top: 0.5rem;">
                                    <?php if ($isOwnProfile): ?>
                                        <?php if ($bioError): ?>
                                            <p style="color: #d32f2f; margin-bottom: 0.5rem;"><?php echo h($bioError); ?></p>
                                        <?php endif; ?>
                                        <form method="post" action="profile.php?user=<?php echo urlencode($username); ?>">
                                            <textarea
                                                name="bio"
                                                rows="3"
                                                maxlength="500"
                                                style="width: 100%; padding: 0.5rem; border-radius: 8px; border: 1px solid var(--border-color); resize: vertical; font-size: 0.95rem;"
                                                placeholder="Write a short introduction about yourself..."
                                            ><?php echo h($bio); ?></textarea>
                                            <div class="form-group" style="margin-top: 0.25rem;">
                                                <button type="submit" class="btn btn-primary">Save bio</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <?php if (trim($bio) !== ''): ?>
                                            <p class="bio-text" style="margin-top: 0.25rem;"><?php echo nl2br(h($bio)); ?></p>
                                        <?php else: ?>
                                            <p class="bio-text" style="margin-top: 0.25rem; color: var(--text-secondary);">
                                                This user hasn’t added a bio yet.
                                            </p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-number" id="totalPosts"><?php echo count($userPosts); ?></span>
                                    <span class="stat-label">Posts</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number" id="totalComments"><?php echo $commentCount; ?></span>
                                    <span class="stat-label">Comments</span>
                                </div>

                                <?php
                                // Load user badges
                                $userBadges = $username ? get_user_badges($username) : [];
                                ?>

                                <div class="profile-badges" style="margin-top: 1rem; width:100%;">
                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 0.95rem;">🏅 Achievements</h3>

                                    <?php if (empty($userBadges)): ?>
                                        <p style="color: var(--text-secondary); margin:0;">No badges earned yet.</p>
                                    <?php else: ?>
                                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                            <?php foreach ($userBadges as $badge): ?>
                                                <span
                                                    style="
                                                        padding: 0.4rem 0.6rem;
                                                        background: #e8f5e9;
                                                        border-radius: 12px;
                                                        font-size: 0.85rem;
                                                        font-weight: 500;
                                                        display: inline-block;
                                                    ">
                                                    <?php echo h($badge); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card posts-section-card">
                    <h2 class="section-title">Posts by <span id="postsByUsername"><?php echo $username ? h($username) : 'Unknown'; ?></span></h2>
                    <div id="userPostsContainer" class="posts-list-container">
                        <?php if (!$username): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">❌</div>
                                <h3>User not found</h3>
                                <p>Please login or select a user from a post or comment.</p>
                                <a href="index.php" class="btn btn-primary" style="margin-top: 1rem; display: inline-block;">Go to Home</a>
                            </div>
                        <?php elseif (empty($userPosts)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">📝</div>
                                <h3>No posts yet</h3>
                                <p>This user hasn't created any posts yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($userPosts as $post): ?>
                                <?php
                                $pDate = new DateTime($post['date'] ?? 'now');
                                $pFormatted = $pDate->format('M j, Y H:i');
                                $pComments = isset($post['comments']) && is_array($post['comments']) ? count($post['comments']) : 0;
                                $thumb = null;
                                if (!empty($post['images']) && is_array($post['images'])) {
                                    $thumb = $post['images'][0];
                                } elseif (!empty($post['image'])) {
                                    $thumb = $post['image'];
                                }
                                ?>
                                <a href="post-detail.php?id=<?php echo (int)$post['id']; ?>&category=<?php echo urlencode($post['category']); ?>" class="post-list-item">
                                    <?php if ($thumb): ?>
                                        <div class="post-list-thumbnail">
                                            <img src="<?php echo h($thumb); ?>" alt="Post thumbnail" class="post-thumbnail-image">
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-list-content">
                                        <div class="post-list-category">
                                            <span class="post-category-badge">
                                                <?php echo h($labels[$post['category']] ?? $post['category']); ?>
                                            </span>
                                        </div>
                                        <h3 class="post-list-title"><?php echo h($post['title']); ?></h3>
                                        <div class="post-list-meta">
                                            <span class="post-list-date">📅 <?php echo h($pFormatted); ?></span>
                                            <span class="post-list-comments">
                                                💬 <?php echo $pComments; ?> <?php echo $pComments === 1 ? 'comment' : 'comments'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
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


