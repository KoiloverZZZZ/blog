<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Блог</title>
    <link rel="stylesheet" href="/blog/style.css">
</head>
<body>
    <div class="wrapper">
        <header>
            <nav>
                <a href="/blog/index.php">Главная</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="/blog/admin/index.php" class="admin-nav-link">Админ-панель</a>
                    <?php else: ?>
                        <a href="/blog/admin/my_posts.php" class="user-nav-link">Мои посты</a>
                    <?php endif; ?>
                    <a href="/blog/logout.php">Выйти (<?= htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь') ?>)</a>
                <?php else: ?>
                    <a href="/blog/login.php">Войти</a>
                    <a href="/blog/register.php">Регистрация</a>
                <?php endif; ?>
            </nav>
        </header>
        <main>