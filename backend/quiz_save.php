<?php
// backend/quiz_save.php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$pdo = get_db();

$quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
$title = trim($_POST['title'] ?? 'Untitled Quiz');
$category = trim($_POST['category'] ?? 'General Knowledge');
$description = trim($_POST['description'] ?? '');
$defaultTimer = max(5, (int)($_POST['default_timer'] ?? 30));
$questions = $_POST['questions'] ?? [];

try {
    $pdo->beginTransaction();

    if ($quizId > 0) {
        // Update quiz
        $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, category = ?, description = ?, default_timer = ? WHERE id = ?");
        $stmt->execute([$title, $category, $description, $defaultTimer, $quizId]);

        // Delete old questions (cascade deletes answers)
        $delStmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $delStmt->execute([$quizId]);
    } else {
        // Insert quiz
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, category, description, default_timer) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $category, $description, $defaultTimer]);
        $quizId = $pdo->lastInsertId();
    }

    if (count($questions) < 10) {
        throw new Exception("A quiz must have at least 10 questions.");
    }

    // Insert questions and answers
    $orderNum = 1;
    foreach ($questions as $qData) {
        $qText = trim($qData['text'] ?? '');
        if (empty($qText)) continue;

        $imageUrl = trim($qData['image_url'] ?? '');
        $points = (int)($qData['points'] ?? 10);
        $explanation = trim($qData['explanation'] ?? '');
        $correctIndex = (int)($qData['correct_index'] ?? 0);

        $qStmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)");
        $qStmt->execute([$quizId, $qText, $imageUrl ?: null, $points, $orderNum, $explanation]);
        $qId = $pdo->lastInsertId();



        $answers = $qData['answers'] ?? [];
        foreach ($answers as $ansIdx => $ansText) {
            $ansTextClean = trim($ansText);
            if (empty($ansTextClean)) continue;

            $isCorrect = ($ansIdx === $correctIndex) ? 1 : 0;
            $aStmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            $aStmt->execute([$qId, $ansTextClean, $isCorrect]);
        }



        $orderNum++;
    }

    $pdo->commit();

    header("Location: index.php?saved=1");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = "Failed to save quiz: " . $e->getMessage();
    header("Location: index.php?error=" . urlencode($error));
    exit();
}
