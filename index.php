<?php
require_once 'db.php';
require_once 'auth.php';
requireLogin();

$currentUser = getCurrentUser();

// Query stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity < 5")->fetchColumn();
$totalSales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();

// Recent sales
$recentSales = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           GROUP_CONCAT(p.name SEPARATOR ', ') as products,
           SUM(si.quantity) as total_items
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Nournia Shop</title>
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
            <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
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
        <div class="page-header">
            <h2>📊 Dashboard</h2>
            <p>ภาพรวมระบบจัดการสต็อกสินค้า</p>
        </div>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?= number_format($totalProducts) ?></div>
                <div class="stat-label">สินค้าทั้งหมด</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?= number_format($lowStock) ?></div>
                <div class="stat-label">สินค้า Stock ต่ำ (<5)</div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon">💰</div>
                <div class="stat-value">฿<?= number_format($totalSales, 2) ?></div>
                <div class="stat-label">ยอดขายรวม</div>
            </div>
        </div>

        <!-- Recent Sales -->
        <div class="card">
            <div class="card-header">
                <h3>🕐 ยอดขายล่าสุด</h3>
                <a href="sales.php" class="btn btn-primary btn-sm">ดูทั้งหมด</a>
            </div>
            <div class="card-body">
                <?php if (count($recentSales) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>วันที่</th>
                                <th>สินค้า</th>
                                <th>จำนวน</th>
                                <th>ยอดรวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><?= $sale['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
                                <td><?= htmlspecialchars($sale['products'] ?? '-') ?></td>
                                <td><?= number_format($sale['total_items'] ?? 0) ?> ชิ้น</td>
                                <td class="price">฿<?= number_format($sale['total_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">🛒</div>
                    <p>ยังไม่มีรายการขาย</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
