<?php
session_start();
require 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($name)) {
        $errors[] = "Имя обязательно для заполнения.";
    }
    
    if (empty($email)) {
        $errors[] = "Email обязателен для заполнения.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Неверный формат email.";
    }
    
    if (empty($password)) {
        $errors[] = "Пароль обязателен для заполнения.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов.";
    }
    
    if ($password !== $password_confirm) {
        $errors[] = "Пароли не совпадают.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Пользователь с таким email уже существует.";
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
        
        if ($stmt->execute([$name, $email, $hashed_password])) {
            $_SESSION['register_success'] = "Регистрация прошла успешно! Теперь вы можете войти.";
            header('Location: login.php');
            exit;
        } else {
            $errors[] = "Ошибка при сохранении в базу данных.";
        }
    }
}

require 'templates/header.php';
?>

<div class="register-container">
    <h2>Регистрация</h2>
    
    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="name">Ваше имя</label>
            <input type="text" id="name" name="name" 
                   value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" 
                   placeholder="Введите ваше имя" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" 
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                   placeholder="example@mail.com" required>
        </div>
        
        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" 
                   placeholder="Минимум 6 символов" required>
            <span class="hint">Пароль должен содержать не менее 6 символов</span>
        </div>
        
        <div class="form-group">
            <label for="password_confirm">Подтверждение пароля</label>
            <input type="password" id="password_confirm" name="password_confirm" 
                   placeholder="Повторите пароль" required>
        </div>
        
        <button type="submit">Зарегистрироваться</button>
    </form>
    
    <div class="auth-links">
        Уже есть аккаунт? <a href="login.php">Войти</a>
    </div>
</div>

<?php require 'templates/footer.php'; ?>