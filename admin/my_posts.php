<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /blog/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $check = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
    $check->execute([$id]);
    $post = $check->fetch();
    
    if ($post && ($post['user_id'] == $user_id || $is_admin)) {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$id]);
        
        if ($post['image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/blog/' . $post['image'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/blog/' . $post['image']);
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
        }
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
    }
    
    header('Location: my_posts.php');
    exit;
}

if ($is_admin && isset($_GET['all'])) {
    $stmt = $pdo->query("
        SELECT posts.*, users.name as author_name,
               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comments_count
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        ORDER BY posts.created_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.name as author_name,
               (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comments_count
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        WHERE posts.user_id = ?
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$user_id]);
}
$posts = $stmt->fetchAll();

require '../templates/header.php';
?>

<?php if ($is_admin): ?>
    <div style="margin-bottom: 20px;">
        <a href="my_posts.php?all=1" class="button" style="width: auto; padding: 10px 20px; background: <?= isset($_GET['all']) ? '#28a745' : '#667eea' ?>;">📋 Все посты</a>
        <a href="my_posts.php" class="button" style="width: auto; padding: 10px 20px; background: <?= !isset($_GET['all']) ? '#28a745' : '#667eea' ?>;">👤 Только мои</a>
    </div>
<?php endif; ?>

<a href="post_add.php" class="add-button">➕ Создать новый пост</a>

<?php if (empty($posts)): ?>
    <div class="no-posts">
        <p>У вас пока нет постов.</p>
        <p><a href="post_add.php">➕ Создать первый пост</a></p>
    </div>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Заголовок</th>
                <?php if (isset($_GET['all']) || $is_admin): ?>
                    <th>Автор</th>
                <?php endif; ?>
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
                <?php if (isset($_GET['all']) || $is_admin): ?>
                    <td><?= htmlspecialchars($post['author_name']) ?></td>
                <?php endif; ?>
                <td><?= date('d.m.Y', strtotime($post['created_at'])) ?></td>
                <td><?= $post['comments_count'] ?></td>
                <td>
                    <a href="post_edit.php?id=<?= $post['id'] ?>" class="edit-btn">Редактировать</a>
                    <a href="?delete=<?= $post['id'] ?>" class="delete-btn" onclick="return confirm('Удалить пост? Все комментарии к нему также будут удалены!')">Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="index.php" class="button" style="width: auto; padding: 10px 20px;">← Назад</a>
</div>

<?php require '../templates/footer.php'; ?>