<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$id = (int)($data['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$id || !in_array($type, ['post', 'comment'])) {
    echo json_encode(['success' => false, 'error' => 'Неверные параметры']);
    exit;
}

try {
    $pdo->query("SELECT 1 FROM likes LIMIT 1");
} catch (PDOException $e) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            post_id INT NULL,
            comment_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
            CHECK (
                (post_id IS NOT NULL AND comment_id IS NULL) OR
                (post_id IS NULL AND comment_id IS NOT NULL)
            ),
            UNIQUE KEY unique_like (user_id, post_id, comment_id)
        )
    ");
}

if ($type === 'post') {
    $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
    $check->execute([$user_id, $id]);
    $exists = $check->fetch();
    
    if ($exists) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$user_id, $id]);
        $liked = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $id]);
        $liked = true;
    }
    
    $count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();
    
} else {
    $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND comment_id = ?");
    $check->execute([$user_id, $id]);
    $exists = $check->fetch();
    
    if ($exists) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$user_id, $id]);
        $liked = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, comment_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $id]);
        $liked = true;
    }
    
    $count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE comment_id = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();
}

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $total
]);