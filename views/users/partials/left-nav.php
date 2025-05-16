<?php
global $conn;
require_once __DIR__ . '../../../../config.php';

$categoryQuery = $conn->query("SELECT DISTINCT name FROM categories ORDER BY id");
$categories = [];
if ($categoryQuery && $categoryQuery->num_rows > 0) {
    while ($catRow = $categoryQuery->fetch_assoc()) {
        $categories[] = $catRow['name'];
    }
}
$currentCategory = $_GET['category'] ?? 'coffee';
?>

<nav class="left-nav">
    <h3 class="left-nav-title">Categories</h3>
    <ul class="left-nav-list">
        <?php foreach ($categories as $catName): ?>
            <?php
            $catSlug = strtolower(str_replace(' ', '-', $catName));
            $isActive = $catSlug === $currentCategory ? 'active' : '';
            ?>
            <li class="left-nav-item">
                <a href="/views/users/user-menu.php?category=<?= urlencode($catSlug) ?>" class="left-nav-link <?= $isActive ?>">
                    <?= htmlspecialchars($catName, ENT_QUOTES) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>