<?php
session_start();
require 'config/db.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT posts.*, users.name as author_name 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    die('Пост не найден');
}

$stmt = $pdo->prepare("
    SELECT comments.*, users.name as user_name 
    FROM comments 
    JOIN users ON comments.user_id = users.id 
    WHERE comments.post_id = ? 
    ORDER BY comments.created_at DESC
");
$stmt->execute([$post_id]);
$comments = $stmt->fetchAll();

try {
    $pdo->query("SELECT 1 FROM likes LIMIT 1");
    $likes_count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $likes_count->execute([$post_id]);
    $likes = $likes_count->fetchColumn();
    
    $user_liked = false;
    if (isset($_SESSION['user_id'])) {
        $check = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND post_id = ?");
        $check->execute([$_SESSION['user_id'], $post_id]);
        $user_liked = $check->fetch() ? true : false;
    }
} catch (PDOException $e) {
    $likes = 0;
    $user_liked = false;
}

require 'templates/header.php';
?>

<article class="full-post">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    
    <div class="post-meta">
        <span>👤 <?= htmlspecialchars($post['author_name']) ?></span>
        <span>📅 <?= date('d.m.Y H:i', strtotime($post['created_at'])) ?></span>
    </div>
    
    <?php if ($post['image']): ?>
        <img src="<?= htmlspecialchars($post['image']) ?>" alt="Post image" class="full-post-image">
    <?php endif; ?>
    
    <div class="full-post-content">
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </div>
    
    <div class="post-actions">
        <button onclick="toggleLike('post', <?= $post_id ?>, this)" 
                class="like-button <?= $user_liked ? 'liked' : '' ?>"
                data-post-id="<?= $post_id ?>">
            <span class="like-icon"><?= $user_liked ? '❤️' : '🤍' ?></span>
            <span class="like-count"><?= $likes ?></span>
        </button>
    </div>
</article>

<section class="comments-section">
    <h2>💬 Комментарии (<span id="comments-count"><?= count($comments) ?></span>)</h2>
    
    <div id="comments-list">
        <?php foreach ($comments as $comment): ?>
            <div class="comment" id="comment-<?= $comment['id'] ?>">
                <div class="comment-meta">
                    <strong><?= htmlspecialchars($comment['user_name']) ?></strong>
                    <span><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
                </div>
                <div class="comment-content">
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <form id="comment-form" class="comment-form">
            <h3>Добавить комментарий</h3>
            <div class="form-group">
                <textarea name="content" placeholder="Ваш комментарий..." required></textarea>
            </div>
            <input type="hidden" name="post_id" value="<?= $post_id ?>">
            <button type="submit">Отправить</button>
        </form>
    <?php else: ?>
        <div class="login-to-comment">
            <p><a href="login.php">Войдите</a>, чтобы оставить комментарий</p>
        </div>
    <?php endif; ?>
</section>

<script>
document.getElementById('comment-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    const response = await fetch('add_comment.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        const commentsList = document.getElementById('comments-list');
        const newComment = document.createElement('div');
        newComment.className = 'comment';
        newComment.innerHTML = `
            <div class="comment-meta">
                <strong>${result.user_name}</strong>
                <span>${result.date}</span>
            </div>
            <div class="comment-content">
                ${result.content.replace(/\n/g, '<br>')}
            </div>
        `;
        commentsList.prepend(newComment);
        
        const countSpan = document.getElementById('comments-count');
        countSpan.textContent = parseInt(countSpan.textContent) + 1;
        
        e.target.reset();
    } else {
        alert('Ошибка: ' + result.error);
    }
});
</script>

<?php require 'templates/footer.php'; ?>