<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /blog/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] === 'admin');

require '../templates/header.php';
?>

<?php if ($is_admin): ?>
    <div class="admin-menu">
        <a href="posts.php" class="admin-button">Управление постами (все)</a>
        <a href="comments.php" class="admin-button">Управление комментариями</a>
        <a href="users.php" class="admin-button">Управление пользователями</a>
    </div>
<?php else: ?>
    <div class="user-menu">
        <h2>Мои посты</h2>
        <a href="my_posts.php" class="admin-button">Мои посты</a>
        <a href="post_add.php" class="admin-button">➕ Создать новый пост</a>
    </div>
    
    <div class="info-box" style="margin-top: 30px;">
        <h3>Моя статистика</h3>
        <?php
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $posts_count = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $comments_count = $stmt->fetchColumn();
        ?>
        <ul>
            <li>Моих постов: <strong><?= $posts_count ?></strong></li>
            <li>Моих комментариев: <strong><?= $comments_count ?></strong></li>
        </ul>
    </div>
<?php endif; ?>

<?php require '../templates/footer.php'; ?>