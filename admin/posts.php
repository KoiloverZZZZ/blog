<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /blog/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    
    if ($post && $post['image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/blog/' . $post['image'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . '/blog/' . $post['image']);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
    }
    
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: posts.php');
    exit;
}

$stmt = $pdo->query("
    SELECT posts.*, users.name as author_name,
           (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comments_count
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    ORDER BY posts.created_at DESC
");
$posts = $stmt->fetchAll();

require '../templates/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="post_add.php" class="add-button">➕ Добавить новый пост</a>
    <a href="my_posts.php" class="button" style="width: auto; padding: 10px 20px; margin-left: 10px;">Мои посты</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Заголовок</th>
            <th>Автор</th>
            <th>Дата</th>
            <th>Комментарии</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($posts as $post): ?>
        <tr>
            <td><?= $post['id'] ?></td>
            <td>
                <a href="/blog/post.php?id=<?= $post['id'] ?>" target="_blank">
                    <?= htmlspecialchars($post['title']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($post['author_name']) ?></td>
            <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
            <td><?= $post['comments_count'] ?></td>
            <td>
                <a href="post_edit.php?id=<?= $post['id'] ?>" class="edit-btn">✏️</a>
                <a href="?delete=<?= $post['id'] ?>" class="delete-btn" onclick="return confirm('Удалить пост? Все комментарии к нему также будут удалены!')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require '../templates/footer.php'; ?>