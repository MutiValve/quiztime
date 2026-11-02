<?php
// backend/api/quizzes.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

try {
    $pdo = get_db();
    $sql = "SELECT q.*, COUNT(qst.id) as question_count 
            FROM quizzes q 
            LEFT JOIN questions qst ON q.id = qst.quiz_id 
            GROUP BY q.id 
            ORDER BY q.id DESC";
            
    $stmt = $pdo->query($sql);
    $quizzes = $stmt->fetchAll();

    // Map types cleanly for client
    $result = array_map(function($q) {
        return [
            'id' => (int)$q['id'],
            'title' => $q['title'],
            'category' => $q['category'],
            'description' => $q['description'],
            'default_timer' => (int)$q['default_timer'],
            'question_count' => (int)$q['question_count'],
            'created_at' => $q['created_at']
        ];
    }, $quizzes);

    echo json_encode([
        'status' => 'success',
        'count' => count($result),
        'data' => $result
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
