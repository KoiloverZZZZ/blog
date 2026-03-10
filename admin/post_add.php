<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /blog/login.php');
    exit;
}

$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/blog/uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];
    
    if (empty($title)) {
        $errors[] = "Введите заголовок";
    }
    
    if (empty($content)) {
        $errors[] = "Введите текст поста";
    }
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $upload_file = $upload_dir . $file_name;
        
        $imageFileType = strtolower(pathinfo($upload_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (in_array($imageFileType, $allowed_types) && in_array($mime, $allowed_mimes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                $image = 'uploads/' . $file_name;
            } else {
                $errors[] = "Ошибка при загрузке файла";
            }
        } else {
            $errors[] = "Разрешены только изображения JPG, JPEG, PNG & GIF";
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, image, user_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$title, $content, $image, $user_id])) {
            $success = "Пост успешно добавлен!";
            $title = $content = '';
        } else {
            $errors[] = "Ошибка при сохранении в базу";
        }
    }
}

require '../templates/header.php';
?>

<?php if (!empty($errors)): ?>
    <ul class="error-list">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success-message"><?= $success ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label for="title">Заголовок:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>
    </div>
    
    <div class="form-group">
        <label for="content">Текст поста:</label>
        <textarea id="content" name="content" rows="10" required><?= htmlspecialchars($content ?? '') ?></textarea>
    </div>
    
    <div class="form-group">
        <label for="image">Изображение (необязательно):</label>
        <input type="file" id="image" name="image" accept="image/*">
    </div>
    
    <button type="submit">Опубликовать</button>
</form>

<div style="margin-top: 20px;">
    <a href="my_posts.php" class="button" style="width: auto; padding: 10px 20px;">← Назад к моим постам</a>
</div>

<?php require '../templates/footer.php'; ?>