<?php
// backend/quiz_edit.php
require_once __DIR__ . '/db.php';

$pdo = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$quiz = [
    'id' => 0,
    'title' => '',
    'category' => 'General Knowledge',
    'description' => '',
    'default_timer' => 30
];

$questions = [];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if ($existing) {
        $quiz = $existing;

        // Fetch questions
        $qStmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY order_num ASC, id ASC");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        foreach ($questions as &$q) {
            $aStmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
            $aStmt->execute([$q['id']]);
            $q['answers'] = $aStmt->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $quiz['id'] ? 'Edit Quiz' : 'Create Quiz' ?> - PubQuiz Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <a href="index.php" class="brand">
                <div class="brand-logo">🍻</div>
                <div>
                    <h1 class="brand-title">PubQuiz Editor</h1>
                    <div class="brand-tagline"><?= $quiz['id'] ? 'Modifying Pub Quiz #' . $quiz['id'] : 'Creating New Pub Quiz' ?></div>
                </div>
            </a>
            <a href="index.php" class="btn btn-secondary">⬅️ Back to Dashboard</a>
        </header>

        <form action="quiz_save.php" method="POST" id="quizForm">
            <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">

            <div class="glass-panel">
                <h2 class="section-title"><span>📋</span> Quiz General Information</h2>
                <div style="margin-top: 1rem;">
                    <div class="form-row">
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label">Quiz Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($quiz['title']) ?>" placeholder="e.g., Friday Night Ultimate Pop & Rock Quiz">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($quiz['category']) ?>" placeholder="e.g., Music & Movies">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Default Timer (seconds)</label>
                            <input type="number" name="default_timer" class="form-control" value="<?= (int)$quiz['default_timer'] ?>" min="5" max="300">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description / Host Notes</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Short description for the quizmaster..."><?= htmlspecialchars($quiz['description']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="glass-panel">
                <div class="action-bar">
                    <h2 class="section-title"><span>❓</span> Questions & Multiple Choice Options</h2>
                    <button type="button" class="btn btn-cyan btn-sm" onclick="addQuestionCard()">➕ Add Question</button>
                </div>

                <div id="questionsContainer">
                    <?php 
                    if (empty($questions)) {
                        // Include 1 blank template question card
                        $questions = [[
                            'id' => 0,
                            'question_text' => '',
                            'image_url' => '',
                            'points' => 10,
                            'explanation' => '',
                            'answers' => [
                                ['answer_text' => '', 'is_correct' => 1],
                                ['answer_text' => '', 'is_correct' => 0],
                                ['answer_text' => '', 'is_correct' => 0],
                                ['answer_text' => '', 'is_correct' => 0]
                            ]
                        ]];
                    }

                    foreach ($questions as $qIndex => $q): 
                    ?>
                        <div class="question-item" id="q_card_<?= $qIndex ?>">
                            <div class="question-header">
                                <span class="form-label" style="font-size: 1rem; color: var(--accent-cyan);">Question #<span class="q-num"><?= $qIndex + 1 ?></span></span>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionCard(<?= $qIndex ?>)">🗑️ Remove</button>
                            </div>
                            <div class="form-row">
                                <div class="form-group" style="grid-column: span 3;">
                                    <label class="form-label">Question Text *</label>
                                    <input type="text" name="questions[<?= $qIndex ?>][text]" class="form-control" required value="<?= htmlspecialchars($q['question_text']) ?>" placeholder="e.g. Which planet is closest to the Sun?">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Points</label>
                                    <input type="number" name="questions[<?= $qIndex ?>][points]" class="form-control" value="<?= (int)($q['points'] ?? 10) ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">Image URL (Optional)</label>
                                    <input type="url" name="questions[<?= $qIndex ?>][image_url]" class="form-control" value="<?= htmlspecialchars($q['image_url'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                                </div>
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label">Answer Explanation / Host Fact</label>
                                    <input type="text" name="questions[<?= $qIndex ?>][explanation]" class="form-control" value="<?= htmlspecialchars($q['explanation'] ?? '') ?>" placeholder="Bonus info revealed after answer">
                                </div>
                            </div>

                            <label class="form-label" style="margin-top: 10px;">Multiple Choice Options (Select radio for Correct Answer)</label>
                            <div class="answers-grid">
                                <?php 
                                $answers = $q['answers'] ?? [];
                                for ($i = 0; $i < 4; $i++): 
                                    $ansText = isset($answers[$i]) ? $answers[$i]['answer_text'] : '';
                                    $isCorrect = isset($answers[$i]) ? (bool)$answers[$i]['is_correct'] : ($i === 0);
                                ?>
                                    <div class="answer-option-input <?= $isCorrect ? 'is-correct' : '' ?>">
                                        <input type="radio" name="questions[<?= $qIndex ?>][correct_index]" value="<?= $i ?>" <?= $isCorrect ? 'checked' : '' ?> onchange="highlightCorrect(this)">
                                        <input type="text" name="questions[<?= $qIndex ?>][answers][<?= $i ?>]" class="form-control" placeholder="Option <?= chr(65 + $i) ?>" value="<?= htmlspecialchars($ansText) ?>" required>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">💾 Save Quiz</button>
                    <a href="index.php" class="btn btn-secondary" style="padding: 12px 20px;">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <script>
    let qCount = <?= count($questions) ?>;

    function addQuestionCard() {
        const container = document.getElementById('questionsContainer');
        const div = document.createElement('div');
        div.className = 'question-item';
        div.id = `q_card_${qCount}`;
        
        div.innerHTML = `
            <div class="question-header">
                <span class="form-label" style="font-size: 1rem; color: var(--accent-cyan);">Question #<span class="q-num">${qCount + 1}</span></span>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestionCard(${qCount})">🗑️ Remove</button>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 3;">
                    <label class="form-label">Question Text *</label>
                    <input type="text" name="questions[${qCount}][text]" class="form-control" required placeholder="Enter question here...">
                </div>
                <div class="form-group">
                    <label class="form-label">Points</label>
                    <input type="number" name="questions[${qCount}][points]" class="form-control" value="10">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Image URL (Optional)</label>
                    <input type="url" name="questions[${qCount}][image_url]" class="form-control" placeholder="https://...">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Answer Explanation / Host Fact</label>
                    <input type="text" name="questions[${qCount}][explanation]" class="form-control" placeholder="Interesting fun fact...">
                </div>
            </div>
            <label class="form-label" style="margin-top: 10px;">Multiple Choice Options (Select radio for Correct Answer)</label>
            <div class="answers-grid">
                <div class="answer-option-input is-correct">
                    <input type="radio" name="questions[${qCount}][correct_index]" value="0" checked onchange="highlightCorrect(this)">
                    <input type="text" name="questions[${qCount}][answers][0]" class="form-control" placeholder="Option A" required>
                </div>
                <div class="answer-option-input">
                    <input type="radio" name="questions[${qCount}][correct_index]" value="1" onchange="highlightCorrect(this)">
                    <input type="text" name="questions[${qCount}][answers][1]" class="form-control" placeholder="Option B" required>
                </div>
                <div class="answer-option-input">
                    <input type="radio" name="questions[${qCount}][correct_index]" value="2" onchange="highlightCorrect(this)">
                    <input type="text" name="questions[${qCount}][answers][2]" class="form-control" placeholder="Option C" required>
                </div>
                <div class="answer-option-input">
                    <input type="radio" name="questions[${qCount}][correct_index]" value="3" onchange="highlightCorrect(this)">
                    <input type="text" name="questions[${qCount}][answers][3]" class="form-control" placeholder="Option D" required>
                </div>
            </div>
        `;

        container.appendChild(div);
        qCount++;
        reindexNumbers();
    }

    function removeQuestionCard(index) {
        const card = document.getElementById(`q_card_${index}`);
        if (card) {
            card.remove();
            reindexNumbers();
        }
    }

    function reindexNumbers() {
        const cards = document.querySelectorAll('.question-item');
        cards.forEach((card, idx) => {
            const qNum = card.querySelector('.q-num');
            if (qNum) qNum.innerText = idx + 1;
        });
    }

    function highlightCorrect(radio) {
        const grid = radio.closest('.answers-grid');
        const items = grid.querySelectorAll('.answer-option-input');
        items.forEach(item => item.classList.remove('is-correct'));
        radio.closest('.answer-option-input').classList.add('is-correct');
    }
    </script>
</body>
</html>
