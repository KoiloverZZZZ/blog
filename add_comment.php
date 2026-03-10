<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    $post_id = (int)$_POST['post_id'];
    $user_id = $_SESSION['user_id'];
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO comments (content, user_id, post_id) VALUES (?, ?, ?)");
    $stmt->execute([$content, $user_id, $post_id]);
    
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'user_name' => $user['name'],
        'content' => htmlspecialchars($content),
        'date' => date('d.m.Y H:i')
    ]);
}