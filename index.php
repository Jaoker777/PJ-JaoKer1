<?php
require_once 'db.php';
require_once 'auth.php';
requireLogin();

$currentUser = getCurrentUser();

// Fetch categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);

// Build products query with filters
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

if ($categoryFilter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Category icons mapping
$categoryIcons = [
    'Graphics Cards' => '🎴',
    'Processors' => '⚡',
    'RAM' => '🧠',
    'Storage' => '💾',
    'Monitors' => '🖥️',
    'Peripherals' => '🎧',
];

// Product gradient mapping by category
$categoryGradients = [
    'Graphics Cards' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'Processors' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'RAM' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'Storage' => 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'Monitors' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'Peripherals' => 'linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%)',
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Nournia Shop</title>
    <meta name="description" content="Nournia Shop — ร้านเกมมิ่งเกียร์ออนไลน์ คุณภาพสูง ราคาดี">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1>🎮 Nournia Shop</h1>
            <span>Gaming Gear Store</span>
        </div>
        <ul class="sidebar-nav">
            <li><a href="index.php" class="active"><span class="icon">🏠</span> Dashboard</a></li>
            <li><a href="products.php"><span class="icon">📦</span> Products</a></li>
            <li><a href="sales.php"><span class="icon">💰</span> Sales</a></li>
        </ul>
        <div class="sidebar-user">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(mb_substr($currentUser['username'], 0, 1)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($currentUser['username']) ?></span>
                    <span class="user-role <?= $currentUser['role'] ?>"><?= $currentUser['role'] === 'admin' ? '🛠 Admin' : '👤 User' ?></span>
                </div>
            </div>
            <a href="logout.php" class="btn-logout" title="ออกจากระบบ">🚪</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <!-- Hero Header -->
        <div class="shop-hero">
            <div class="hero-text">
                <h2>🎮 สินค้าเกมมิ่งเกียร์</h2>
                <p>เลือกช้อปอุปกรณ์เกมมิ่งคุณภาพสูง ราคาดีที่สุด</p>
            </div>
            <!-- Search Bar -->
            <div class="search-wrapper">
                <form method="GET" class="search-form" id="searchForm">
                    <?php if ($categoryFilter > 0): ?>
                    <input type="hidden" name="category" value="<?= $categoryFilter ?>">
                    <?php endif; ?>
                    <div class="search-input-group">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="search" id="searchInput" class="search-input"
                               placeholder="ค้นหาสินค้า... เช่น RTX 4090, Razer, Samsung"
                               value="<?= htmlspecialchars($searchQuery) ?>"
                               autocomplete="off">
                        <?php if ($searchQuery): ?>
                        <a href="index.php<?= $categoryFilter ? '?category='.$categoryFilter : '' ?>" class="search-clear" title="ล้างการค้นหา">✕</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="category-filter">
            <a href="index.php<?= $searchQuery ? '?search='.urlencode($searchQuery) : '' ?>"
               class="category-pill <?= $categoryFilter === 0 ? 'active' : '' ?>">
                🏷️ ทั้งหมด
            </a>
            <?php foreach ($categories as $cat): ?>
            <a href="index.php?category=<?= $cat['id'] ?><?= $searchQuery ? '&search='.urlencode($searchQuery) : '' ?>"
               class="category-pill <?= $categoryFilter === $cat['id'] ? 'active' : '' ?>">
                <?= $categoryIcons[$cat['name']] ?? '📁' ?> <?= htmlspecialchars($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Results Info -->
        <div class="results-info">
            <span>แสดง <strong><?= count($products) ?></strong> สินค้า</span>
            <?php if ($searchQuery || $categoryFilter): ?>
            <a href="index.php" class="clear-filters">ล้างตัวกรอง ✕</a>
            <?php endif; ?>
        </div>

        <!-- Product Grid -->
        <?php if (count($products) > 0): ?>
        <div class="product-grid">
            <?php foreach ($products as $p):
                $gradient = $categoryGradients[$p['category_name']] ?? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                $icon = $categoryIcons[$p['category_name']] ?? '📦';
            ?>
            <div class="product-card" data-id="<?= $p['id'] ?>">
                <div class="product-image" style="background: <?= $gradient ?>">
                    <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="product-image-placeholder">
                        <span class="placeholder-icon"><?= $icon ?></span>
                    </div>
                    <?php endif; ?>
                    <span class="product-category-badge"><?= htmlspecialchars($p['category_name']) ?></span>
                    <?php if ($p['stock_quantity'] < 5): ?>
                    <span class="product-stock-warning">เหลือน้อย!</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                    <?php if ($p['description']): ?>
                    <p class="product-desc"><?= htmlspecialchars(mb_substr($p['description'], 0, 50)) ?></p>
                    <?php endif; ?>
                    <div class="product-bottom">
                        <div class="product-price">
                            <span class="price-label">ราคา</span>
                            <span class="price-value">฿<?= number_format($p['price'], 2) ?></span>
                        </div>
                        <button class="btn-add-cart" onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>, <?= $p['stock_quantity'] ?>)" <?= $p['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                            <?= $p['stock_quantity'] > 0 ? '🛒 Add to Cart' : '❌ หมดสต็อก' ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state" style="margin-top: 3rem;">
            <div class="icon">🔍</div>
            <p>ไม่พบสินค้าที่ค้นหา</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">ดูสินค้าทั้งหมด</a>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Toast Notification -->
<div class="toast-container" id="toastContainer"></div>

<script>
// ===== Cart Management =====
let cart = JSON.parse(localStorage.getItem('nournia_cart') || '[]');

function addToCart(id, name, price, stock) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        if (existing.qty >= stock) {
            showToast('⚠️ สินค้าในตะกร้าถึงจำนวน stock สูงสุดแล้ว', 'warning');
            return;
        }
        existing.qty += 1;
    } else {
        cart.push({ id, name, price, qty: 1 });
    }
    localStorage.setItem('nournia_cart', JSON.stringify(cart));
    showToast(`✅ เพิ่ม "${name}" ลงตะกร้าแล้ว!`, 'success');
    animateCartButton(id);
}

function animateCartButton(productId) {
    const card = document.querySelector(`.product-card[data-id="${productId}"]`);
    if (!card) return;
    const btn = card.querySelector('.btn-add-cart');
    btn.classList.add('cart-added');
    btn.textContent = '✅ เพิ่มแล้ว!';
    setTimeout(() => {
        btn.classList.remove('cart-added');
        btn.textContent = '🛒 Add to Cart';
    }, 1500);
}

// ===== Toast =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}

// ===== Live Search (debounced) =====
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('searchForm').submit();
    }, 600);
});
</script>
</body>
</html>
