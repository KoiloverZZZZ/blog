<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <a href="/blog/admin/index.php" class="admin-panel-button">Админ-панель</a>
<?php endif; ?>