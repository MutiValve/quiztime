<?php
// backend/quiz_delete.php
require_once __DIR__ . '/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        die("Error deleting quiz: " . $e->getMessage());
    }
}

header("Location: index.php");
exit();
