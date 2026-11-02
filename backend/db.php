<?php
// backend/db.php

$dbPath = __DIR__ . '/pubquiz.db';
$pdo = new PDO("sqlite:" . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("PRAGMA foreign_keys = ON;");

$pdo->exec("
CREATE TABLE IF NOT EXISTS quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    category TEXT DEFAULT 'General Knowledge',
    description TEXT,
    default_timer INTEGER DEFAULT 30,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    image_url TEXT,
    points INTEGER DEFAULT 10,
    order_num INTEGER DEFAULT 1,
    explanation TEXT,
    FOREIGN KEY(quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct INTEGER DEFAULT 0,
    FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE
);
");

$stmt = $pdo->query("SELECT COUNT(*) as count FROM quizzes");
$count = $stmt->fetch()['count'];

if ($count == 0) {
    //testquiz
    $pdo->exec("INSERT INTO quizzes (title, category, description, default_timer) VALUES 
        ('Ultimate Pop Culture & Music Trivia', 'Music & Movies', 'A fun pub quiz testing retro classics, modern pop hits, and iconic movie lore!', 25)");
    $quiz1Id = $pdo->lastInsertId();

    // Q1
    $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$quiz1Id, 'Which legendary band performed the famous hit "Bohemian Rhapsody"?', 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600&auto=format&fit=crop', 10, 1, 'Queen released Bohemian Rhapsody in 1975 on their album A Night at the Opera.']);
    $q1Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q1Id, 'The Beatles', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q1Id, 'Queen', 1]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q1Id, 'Pink Floyd', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q1Id, 'Led Zeppelin', 0]);

    // Q2
    $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$quiz1Id, 'What is the highest-grossing film of all time (unadjusted for inflation)?', null, 10, 2, 'Avatar (2009) directed by James Cameron remains #1 highest-grossing worldwide!']);
    $q2Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q2Id, 'Avengers: Endgame', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q2Id, 'Titanic', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q2Id, 'Avatar', 1]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q2Id, 'Star Wars: The Force Awakens', 0]);

    // Q3
    $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$quiz1Id, 'How many strings does a standard acoustic guitar have?', null, 5, 3, 'Standard guitars have 6 strings tuned E-A-D-G-B-E.']);
    $q3Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q3Id, '4 Strings', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q3Id, '5 Strings', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q3Id, '6 Strings', 1]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q3Id, '8 Strings', 0]);

    // Seed Quiz 2: Science & World Wonders
    $pdo->exec("INSERT INTO quizzes (title, category, description, default_timer) VALUES 
        ('Science, Space & World Wonders', 'Science & Geography', 'Mind-bending science facts, space exploration, and geography trivia.', 30)");
    $quiz2Id = $pdo->lastInsertId();

    // Q1
    $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$quiz2Id, 'Which planet in our solar system is known as the Red Planet?', 'https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?w=600&auto=format&fit=crop', 10, 1, 'Mars appears reddish due to iron oxide (rust) on its surface.']);
    $q4Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q4Id, 'Venus', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q4Id, 'Mars', 1]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q4Id, 'Jupiter', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q4Id, 'Mercury', 0]);

    // Q2
    $pdo->prepare("INSERT INTO questions (quiz_id, question_text, image_url, points, order_num, explanation) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$quiz2Id, 'What chemical element has the atomic symbol "Au"?', null, 15, 2, 'Au comes from the Latin word "Aurum", meaning glowing dawn / gold.']);
    $q5Id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q5Id, 'Silver', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q5Id, 'Gold', 1]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q5Id, 'Aluminum', 0]);
    $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)")->execute([$q5Id, 'Argon', 0]);
}

function get_db() {
    global $pdo;
    return $pdo;
}
