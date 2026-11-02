<?php
// backend/index.php
require_once __DIR__ . '/db.php';

$pdo = get_db();
$stmt = $pdo->query("
    SELECT q.*, COUNT(qst.id) as question_count 
    FROM quizzes q 
    LEFT JOIN questions qst ON q.id = qst.quiz_id 
    GROUP BY q.id 
    ORDER BY q.id DESC
");
$quizzes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PubQuiz Manager - Admin Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <a href="index.php" class="brand">
                <div class="brand-logo">:]</div>
                <div>
                    <h1 class="brand-title">PubQuiz Central</h1>
                    <div class="brand-tagline">AI slop styling + API</div>
                </div>
            </a>
            <div class="api-badge">
                <span class="api-pulse"></span> REST API: <code>/api/quizzes.php</code>
            </div>
        </header>

        <div class="action-bar">
            <h2 class="section-title">
                Available Pub Quizzes
            </h2>
            <a href="quiz_edit.php" class="btn btn-primary">
                <span>➕</span> Create New Quiz
            </a>
        </div>

        <?php if (empty($quizzes)): ?>
            <div class="glass-panel empty-state">
                <h3>No Quizzes Created Yet</h3>
                <p>Click "Create New Quiz" above to set up your first pub quiz questions!</p>
            </div>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <div>
                            <span class="quiz-category"><?= htmlspecialchars($quiz['category']) ?></span>
                            <h3 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h3>
                            <p class="quiz-desc"><?= htmlspecialchars($quiz['description'] ?: 'No description provided.') ?></p>
                        </div>
                        <div>
                            <div class="quiz-stats">
                                <div class="quiz-stat-item">
                                     <?= $quiz['question_count'] ?> Questions
                                </div>
                                <div class="quiz-stat-item">
                                    <?= $quiz['default_timer'] ?>s Timer
                                </div>
                            </div>
                            <div class="quiz-actions">
                                <a href="quiz_edit.php?id=<?= $quiz['id'] ?>" class="btn btn-secondary btn-sm" style="flex: 1; justify-content: center;">
                                    Edit
                                </a>
                                <a href="api/quiz.php?id=<?= $quiz['id'] ?>" target="_blank" class="btn btn-cyan btn-sm" title="View JSON payload">
                                    API 
                                </a>
                                <a href="quiz_delete.php?id=<?= $quiz['id'] ?>" onclick="return confirm('Are you sure you want to delete this quiz?')" class="btn btn-danger btn-sm">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $error = $_GET['error'] ?? null; ?>
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>