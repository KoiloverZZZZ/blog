<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /blog/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: users.php');
    exit;
}

if (isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
    }
    header('Location: users.php');
    exit;
}

$stmt = $pdo->query("
    SELECT 
        users.*,
        COUNT(DISTINCT posts.id) as posts_count,
        COUNT(DISTINCT comments.id) as comments_count
    FROM users
    LEFT JOIN posts ON users.id = posts.user_id
    LEFT JOIN comments ON users.id = comments.user_id
    GROUP BY users.id
    ORDER BY users.created_at DESC
");
$users = $stmt->fetchAll();

require '../templates/header.php';
?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Дата регистрации</th>
            <th>Постов</th>
            <th>Комментариев</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <select name="role" onchange="this.form.submit()">
                            <option value="user" <?= $user['role'] == 'user' ? 'selected' : '' ?>>Пользователь</option>
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Админ</option>
                        </select>
                        <input type="hidden" name="change_role" value="1">
                    </form>
                <?php else: ?>
                    <strong>Админ (это вы)</strong>
                <?php endif; ?>
            </td>
            <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
            <td><?= $user['posts_count'] ?></td>
            <td><?= $user['comments_count'] ?></td>
            <td>
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <a href="?delete=<?= $user['id'] ?>" class="delete-btn" onclick="return confirm('Удалить пользователя? Все его посты и комментарии также будут удалены!')">Удалить</a>
                <?php else: ?>
                    <span style="color: #999;">Нельзя удалить себя</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 20px;">
    <a href="index.php" class="button" style="width: auto; padding: 10px 20px;">← Назад в админ-панель</a>
</div>

<?php require '../templates/footer.php'; ?>