<?php
// backend/api/quiz.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo = get_db();

    // If ID is not specified, default to the first available quiz
    if ($id <= 0) {
        $stmtFirst = $pdo->query("SELECT id FROM quizzes ORDER BY id ASC LIMIT 1");
        $first = $stmtFirst->fetch();
        if ($first) {
            $id = (int)$first['id'];
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'No quizzes found in database.']);
            exit();
        }
    }

    // Fetch quiz header
    $stmtQuiz = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmtQuiz->execute([$id]);
    $quiz = $stmtQuiz->fetch();

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Quiz not found']);
        exit();
    }

    // Fetch questions
    $stmtQuestions = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY order_num ASC, id ASC");
    $stmtQuestions->execute([$id]);
    $rawQuestions = $stmtQuestions->fetchAll();

    $questions = [];
    foreach ($rawQuestions as $q) {
        $qId = (int)$q['id'];
        
        // Fetch answers for this question
        $stmtAnswers = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id ASC");
        $stmtAnswers->execute([$qId]);
        $rawAnswers = $stmtAnswers->fetchAll();

        $answers = array_map(function($a) {
            return [
                'id' => (int)$a['id'],
                'answer_text' => $a['answer_text'],
                'is_correct' => (bool)$a['is_correct']
            ];
        }, $rawAnswers);

        $questions[] = [
            'id' => $qId,
            'question_text' => $q['question_text'],
            'image_url' => $q['image_url'],
            'points' => (int)$q['points'],
            'order_num' => (int)$q['order_num'],
            'explanation' => $q['explanation'],
            'answers' => $answers
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'id' => (int)$quiz['id'],
            'title' => $quiz['title'],
            'category' => $quiz['category'],
            'description' => $quiz['description'],
            'default_timer' => (int)$quiz['default_timer'],
            'questions' => $questions
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
