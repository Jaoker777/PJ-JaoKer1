<?php
require_once 'db.php';
require_once 'auth.php';
requireLogin();

$currentUser = getCurrentUser();
$message = '';
$messageType = '';

// Only admin can modify products
if (isAdmin()) {
    // Handle DELETE
    if (isset($_GET['delete'])) {
        $id = (int)$_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'ลบสินค้าเรียบร้อยแล้ว';
        $messageType = 'success';
    }

    // Handle ADD
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');

        if ($name && $category_id > 0 && $price > 0) {
            $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$category_id, $name, $description, $price, $stock, $image_url]);
            $message = 'เพิ่มสินค้าเรียบร้อยแล้ว';
            $messageType = 'success';
        } else {
            $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            $messageType = 'danger';
        }
    }

    // Handle EDIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_quantity'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');

        if ($id > 0 && $name && $category_id > 0 && $price > 0) {
            $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$category_id, $name, $description, $price, $stock, $image_url, $id]);
            $message = 'แก้ไขสินค้าเรียบร้อยแล้ว';
            $messageType = 'success';
        } else {
            $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            $messageType = 'danger';
        }
    }
}

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch products
$products = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Nournia Shop</title>
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
            <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
            <li><a href="products.php" class="active"><span class="icon">📦</span> Products</a></li>
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
            <h2>📦 จัดการสินค้า</h2>
            <p>เพิ่ม แก้ไข และลบสินค้าในระบบ</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>รายการสินค้าทั้งหมด (<?= count($products) ?> รายการ)</h3>
                <?php if (isAdmin()): ?>
                <button class="btn btn-primary" onclick="openAddModal()">➕ เพิ่มสินค้า</button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($products) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อสินค้า</th>
                                <th>หมวดหมู่</th>
                                <th>ราคา</th>
                                <th>Stock</th>
                                <?php if (isAdmin()): ?>
                                <th>จัดการ</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <?php if ($p['description']): ?>
                                    <br><small style="color: var(--text-muted)"><?= htmlspecialchars(mb_substr($p['description'], 0, 60)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                                <td class="price">฿<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <?php
                                    $stockClass = 'ok';
                                    if ($p['stock_quantity'] < 5) $stockClass = 'low';
                                    elseif ($p['stock_quantity'] < 10) $stockClass = 'warning';
                                    ?>
                                    <span class="stock-badge <?= $stockClass ?>"><?= number_format($p['stock_quantity']) ?> ชิ้น</span>
                                </td>
                                <?php if (isAdmin()): ?>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($p) ?>)'>✏️ แก้ไข</button>
                                        <a href="products.php?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันลบสินค้า <?= htmlspecialchars($p['name']) ?>?')">🗑️ ลบ</a>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <p>ยังไม่มีสินค้าในระบบ</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php if (isAdmin()): ?>
<!-- Add Product Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h3>➕ เพิ่มสินค้าใหม่</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" name="name" class="form-control" required placeholder="เช่น NVIDIA RTX 4090">
                </div>
                <div class="form-group">
                    <label>หมวดหมู่ *</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ราคา (฿) *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>จำนวน Stock</label>
                    <input type="number" name="stock_quantity" class="form-control" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="description" class="form-control" placeholder="รายละเอียดสินค้า..."></textarea>
                </div>
                <div class="form-group">
                    <label>URL รูปภาพ</label>
                    <input type="text" name="image_url" class="form-control" placeholder="https://...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">💾 บันทึก</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>✏️ แก้ไขสินค้า</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-body">
                <div class="form-group">
                    <label>ชื่อสินค้า *</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่ *</label>
                    <select name="category_id" id="edit-category" class="form-control" required>
                        <option value="">-- เลือกหมวดหมู่ --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>ราคา (฿) *</label>
                    <input type="number" name="price" id="edit-price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>จำนวน Stock</label>
                    <input type="number" name="stock_quantity" id="edit-stock" class="form-control" min="0">
                </div>
                <div class="form-group">
                    <label>รายละเอียด</label>
                    <textarea name="description" id="edit-description" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label>URL รูปภาพ</label>
                    <input type="text" name="image_url" id="edit-image" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">💾 บันทึก</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (isAdmin()): ?>
function openAddModal() {
    document.getElementById('addModal').classList.add('active');
}

function openEditModal(product) {
    document.getElementById('edit-id').value = product.id;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-category').value = product.category_id;
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-stock').value = product.stock_quantity;
    document.getElementById('edit-description').value = product.description || '';
    document.getElementById('edit-image').value = product.image_url || '';
    document.getElementById('editModal').classList.add('active');
}
<?php endif; ?>

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});
</script>
</body>
</html>
