<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /blog/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: comments.php');
    exit;
}

$stmt = $pdo->query("
    SELECT comments.*, users.name as user_name, posts.title as post_title 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    JOIN posts ON comments.post_id = posts.id 
    ORDER BY comments.created_at DESC
");
$comments = $stmt->fetchAll();

require '../templates/header.php';
?>

<?php if (empty($comments)): ?>
    <div class="no-posts">
        <p>Пока нет ни одного комментария.</p>
    </div>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Комментарий</th>
                <th>Автор</th>
                <th>Пост</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?= $comment['id'] ?></td>
                <td><?= htmlspecialchars(mb_substr($comment['content'], 0, 50)) ?>...</td>
                <td><?= htmlspecialchars($comment['user_name']) ?></td>
                <td><?= htmlspecialchars(mb_substr($comment['post_title'], 0, 30)) ?>...</td>
                <td><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></td>
                <td>
                    <a href="?delete=<?= $comment['id'] ?>" class="delete-btn" onclick="return confirm('Удалить комментарий?')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="index.php" class="button" style="width: auto; padding: 10px 20px;">← Назад в админ-панель</a>
</div>

<?php require '../templates/footer.php'; ?>