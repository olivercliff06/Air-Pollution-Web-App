<?php
require_once __DIR__ . '/config.php';

if (!is_admin()) {
    header('Location: index.php');
    exit;
}

$quizzes = load_quizzes();

$mode    = $_GET['mode'] ?? 'list'; // list | edit
$quizId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error   = null;
$success = null;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode   = 'edit';
    $quizId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;

    $title = trim($_POST['title'] ?? '');

    if ($title === '') {
        $error = 'Please provide a quiz title.';
    } else {
        $questions = [];
        $qTexts    = $_POST['q_text'] ?? [];

        foreach ($qTexts as $i => $rawText) {
            if (count($questions) >= 20) {
                break;
            }

            $qText = trim($rawText ?? '');
            if ($qText === '') {
                continue;
            }

            $opt1 = trim($_POST['q_opt1'][$i] ?? '');
            $opt2 = trim($_POST['q_opt2'][$i] ?? '');
            $opt3 = trim($_POST['q_opt3'][$i] ?? '');
            $opt4 = trim($_POST['q_opt4'][$i] ?? '');
            $correct = isset($_POST['q_correct'][$i]) ? (int)$_POST['q_correct'][$i] : 0;

            if ($opt1 === '' || $opt2 === '' || $opt3 === '' || $opt4 === '') {
    $error = 'All options must be filled for each question.';
    continue;
}//Sam


            $questions[] = [
                'text'         => $qText,
                'options'      => [$opt1, $opt2, $opt3, $opt4],
                'correctIndex' => max(0, min(3, $correct)),
            ];
        }

        if (empty($questions)) {
    $error = 'Please add at least one valid question with 4 options.';
} else {
    // All good → Save quiz
    $currentUser = current_user();
    $savedId     = save_quiz($quizId, $title, $questions, $currentUser['username'] ?? null);
    $quizId      = $savedId;
    $success     = 'Quiz saved.';
}

    }
}

if ($mode === 'edit' && $quizId) {
    $editing = find_quiz($quizId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Pollution Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">🌍 Pollution Forum - Quiz Admin</h1>
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
                            <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'quiz-admin.php'); ?>" class="btn btn-logout">Logout</a>
                        </div>
                    <?php else: ?>
                        <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'quiz-admin.php'); ?>">Login</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="back-button-section">
                <a href="index.php" class="btn-back">← Back to Home</a>
            </div>

            <section class="article-section">
                <div class="card">
                    <div class="learning-header">
                        <h2 class="section-title">Manage Quizzes</h2>
                        <a href="quiz-admin.php?mode=edit" class="btn btn-primary">+ New Quiz</a>
                    </div>

                    <?php if ($error): ?>
                        <p style="color: #d32f2f; margin-bottom: 1rem;"><?php echo h($error); ?></p>
                    <?php elseif ($success): ?>
                        <p style="color: #388e3c; margin-bottom: 1rem;"><?php echo h($success); ?></p>
                    <?php endif; ?>

                    <?php if ($mode === 'edit'): ?>
                        <?php
                        $title     = $editing['title'] ?? '';
                        $questions = $editing['questions'] ?? [];
                        ?>
                        <form method="post" class="article-edit-form" id="quizForm">
                            <input type="hidden" name="id" value="<?php echo $editing['id'] ?? ''; ?>">
                            <div class="form-group">
                                <label for="title">Quiz Title</label>
                                <input type="text" id="title" name="title" required value="<?php echo h($title); ?>">
                            </div>

                            <p style="font-size: 0.9rem; margin-bottom: 1rem;">
                                Add up to 20 questions. Each question must have 4 options and one correct answer.
                            </p>

                            <div id="questionsContainer">
                                <?php foreach ($questions as $i => $q): ?>
                                    <?php
                                    $qText  = $q['text'] ?? '';
                                    $opts   = $q['options'] ?? ['', '', '', ''];
                                    $correctIndex = isset($q['correctIndex']) ? (int)$q['correctIndex'] : 0;
                                    ?>
                                    <fieldset class="quiz-question" data-index="<?php echo $i; ?>" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem;">
                                        <legend style="font-size: 0.9rem; padding: 0 0.5rem;">Question <?php echo $i + 1; ?></legend>
                                        <div class="form-group">
                                            <label for="q_text_<?php echo $i; ?>">Question text</label>
                                            <input type="text" id="q_text_<?php echo $i; ?>" name="q_text[<?php echo $i; ?>]" value="<?php echo h($qText); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Options</label>
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                                <input type="text" name="q_opt1[<?php echo $i; ?>]" placeholder="Option 1" value="<?php echo h($opts[0] ?? ''); ?>">
                                                <input type="text" name="q_opt2[<?php echo $i; ?>]" placeholder="Option 2" value="<?php echo h($opts[1] ?? ''); ?>">
                                                <input type="text" name="q_opt3[<?php echo $i; ?>]" placeholder="Option 3" value="<?php echo h($opts[2] ?? ''); ?>">
                                                <input type="text" name="q_opt4[<?php echo $i; ?>]" placeholder="Option 4" value="<?php echo h($opts[3] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="q_correct_<?php echo $i; ?>">Correct option</label>
                                            <select id="q_correct_<?php echo $i; ?>" name="q_correct[<?php echo $i; ?>]" style="max-width: 200px;">
                                                <option value="0" <?php echo $correctIndex === 0 ? 'selected' : ''; ?>>Option 1</option>
                                                <option value="1" <?php echo $correctIndex === 1 ? 'selected' : ''; ?>>Option 2</option>
                                                <option value="2" <?php echo $correctIndex === 2 ? 'selected' : ''; ?>>Option 3</option>
                                                <option value="3" <?php echo $correctIndex === 3 ? 'selected' : ''; ?>>Option 4</option>
                                            </select>
                                        </div>
                                    </fieldset>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" class="btn btn-outline" id="addQuestionButton" style="margin-bottom: 1rem;">+ Add Question</button>
                            <button type="submit" class="btn btn-primary">Save Quiz</button>
                        </form>
                    <?php else: ?>
                        <div class="posts-list-container">
                            <?php if (empty($quizzes)): ?>
                                <p>No quizzes yet. Click "New Quiz" to create one.</p>
                            <?php else: ?>
                                <?php foreach ($quizzes as $q): ?>
                                    <a href="quiz.php?id=<?php echo (int)$q['id']; ?>" class="post-list-item">
                                        <div class="post-list-content">
                                            <h3 class="post-list-title"><?php echo h($q['title']); ?></h3>
                                            <div class="post-list-meta">
                                                <span class="post-list-date">
                                                    Created: <?php echo h((new DateTime($q['created_at']))->format('M j, Y H:i')); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div style="margin-left: auto; display: flex; align-items: center; gap: 0.5rem;">
                                            <a href="quiz-admin.php?mode=edit&id=<?php echo (int)$q['id']; ?>" class="btn btn-outline">Edit</a>
                                        </div>
                                    </a>
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

    <script>
        (function () {
            const container = document.getElementById('questionsContainer');
            const addBtn = document.getElementById('addQuestionButton');
            if (!container || !addBtn) return;

            function nextIndex() {
                const existing = container.querySelectorAll('.quiz-question');
                return existing.length;
            }

            function addQuestion() {
                const idx = nextIndex();
                if (idx >= 20) {
                    alert('You can add at most 20 questions.');
                    return;
                }
                const fieldset = document.createElement('fieldset');
                fieldset.className = 'quiz-question';
                fieldset.setAttribute('data-index', String(idx));
                fieldset.style.border = '1px solid var(--border-color)';
                fieldset.style.borderRadius = '8px';
                fieldset.style.padding = '0.75rem';
                fieldset.style.marginBottom = '0.75rem';
                fieldset.innerHTML = ''
                    + '<legend style="font-size: 0.9rem; padding: 0 0.5rem;">Question ' + (idx + 1) + '</legend>'
                    + '<div class="form-group">'
                    + '  <label for="q_text_' + idx + '">Question text</label>'
                    + '  <input type="text" id="q_text_' + idx + '" name="q_text[' + idx + ']" value="">' 
                    + '</div>'
                    + '<div class="form-group">'
                    + '  <label>Options</label>'
                    + '  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">'
                    + '    <input type="text" name="q_opt1[' + idx + ']" placeholder="Option 1" value="">'
                    + '    <input type="text" name="q_opt2[' + idx + ']" placeholder="Option 2" value="">'
                    + '    <input type="text" name="q_opt3[' + idx + ']" placeholder="Option 3" value="">'
                    + '    <input type="text" name="q_opt4[' + idx + ']" placeholder="Option 4" value="">'
                    + '  </div>'
                    + '</div>'
                    + '<div class="form-group">'
                    + '  <label for="q_correct_' + idx + '">Correct option</label>'
                    + '  <select id="q_correct_' + idx + '" name="q_correct[' + idx + ']" style="max-width: 200px;">'
                    + '    <option value="0">Option 1</option>'
                    + '    <option value="1">Option 2</option>'
                    + '    <option value="2">Option 3</option>'
                    + '    <option value="3">Option 4</option>'
                    + '  </select>'
                    + '</div>';

                container.appendChild(fieldset);
            }

            addBtn.addEventListener('click', addQuestion);

            // If there are no existing questions, start with one empty block
            if (container.querySelectorAll('.quiz-question').length === 0) {
                addQuestion();
            }
        })();
    </script>
</body>
</html>


