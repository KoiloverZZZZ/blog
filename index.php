<?php
session_start();
require 'config/db.php';

// Проверяем, есть ли таблица posts с нужной структурой
try {
    $test_query = $pdo->query("SELECT COUNT(*) FROM posts");
    $test_query->fetch();
    $posts_exist = true;
} catch (PDOException $e) {
    $posts_exist = false;
}

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Получаем общее количество постов
if ($posts_exist) {
    $total_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $total_pages = ceil($total_posts / $limit);

    // Получаем посты для текущей страницы
    $stmt = $pdo->prepare("
        SELECT posts.*, users.name as author_name 
        FROM posts 
        JOIN users ON posts.user_id = users.id 
        ORDER BY posts.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    $posts = $stmt->fetchAll();
} else {
    $total_pages = 1;
    $posts = [];
}

require 'templates/header.php';
?>

<?php if (!$posts_exist): ?>
    <div class="warning-message">
        <p>Таблица posts еще не создана или имеет неправильную структуру.</p>
        <p>Пожалуйста, создайте таблицы через phpMyAdmin.</p>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="welcome-message">
        <p>Привет, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь') ?></strong>!</p>
        <p>Ты успешно авторизован в системе.</p>
    </div>
    
    <div class="info-box">
        <h3>Информация о пользователе:</h3>
        <ul>
            <li><strong>ID:</strong> <?= $_SESSION['user_id'] ?></li>
            <li><strong>Имя:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Не указано') ?></li>
            <li><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user_email'] ?? 'Не указан') ?></li>
            <li><strong>Роль:</strong> <?= $_SESSION['role'] ?? 'user' ?></li>
        </ul>
    </div>
<?php else: ?>
    <div class="welcome-message">
        <p>Добро пожаловать в блог!</p>
        <p>Чтобы оставлять комментарии, пожалуйста, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>.</p>
    </div>
<?php endif; ?>

<?php if ($posts_exist && !empty($posts)): ?>
    <h2>Последние посты</h2>
    <?php foreach ($posts as $post): ?>
        <div class="post-card">
            <?php if ($post['image']): ?>
                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="post-image">
            <?php endif; ?>
            
            <h3 class="post-title">
                <a href="post.php?id=<?= $post['id'] ?>">
                    <?= htmlspecialchars($post['title']) ?>
                </a>
            </h3>
            
            <div class="post-meta">
                <span>👤 <?= htmlspecialchars($post['author_name']) ?></span>
                <span>📅 <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
            </div>
            
            <div class="post-excerpt">
                <?= nl2br(htmlspecialchars(substr($post['content'], 0, 300))) ?>...
            </div>
            
            <a href="post.php?id=<?= $post['id'] ?>" class="read-more">Читать далее →</a>
        </div>
    <?php endforeach; ?>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>">← Предыдущая</a>
            <?php endif; ?>
            
            <span>Страница <?= $page ?> из <?= $total_pages ?></span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Следующая →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php elseif ($posts_exist): ?>
    <div class="no-posts">
        <p>Пока нет ни одного поста.</p>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <p><a href="admin/post_add.php">➕ Добавить первый пост</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require 'templates/footer.php'; ?>