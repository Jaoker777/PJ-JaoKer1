<?php
require_once 'db.php';
require_once 'auth.php';
requireLogin();

$currentUser = getCurrentUser();
$message = '';
$messageType = '';

// Handle new sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sell') {
    $productIds = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (!empty($productIds)) {
        try {
            $pdo->beginTransaction();

            $totalAmount = 0;
            $validItems = [];

            for ($i = 0; $i < count($productIds); $i++) {
                $pid = (int)$productIds[$i];
                $qty = (int)$quantities[$i];

                if ($pid <= 0 || $qty <= 0) continue;

                // Check stock
                $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$pid]);
                $product = $stmt->fetch();

                if (!$product) continue;
                if ($product['stock_quantity'] < $qty) {
                    throw new Exception("สินค้า {$product['name']} มี stock ไม่เพียงพอ (เหลือ {$product['stock_quantity']} ชิ้น)");
                }

                $lineTotal = $product['price'] * $qty;
                $totalAmount += $lineTotal;
                $validItems[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_price' => $product['price'],
                ];
            }

            if (empty($validItems)) {
                throw new Exception('ไม่มีรายการที่ถูกต้อง');
            }

            // Create sale record
            $stmt = $pdo->prepare("INSERT INTO sales (total_amount) VALUES (?)");
            $stmt->execute([$totalAmount]);
            $saleId = $pdo->lastInsertId();

            // Create sale items & deduct stock
            $insertItem = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");

            foreach ($validItems as $item) {
                $insertItem->execute([$saleId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }

            $pdo->commit();
            $message = "บันทึกการขายเรียบร้อย! (฿" . number_format($totalAmount, 2) . ")";
            $messageType = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ';
        $messageType = 'danger';
    }
}

// Fetch products for dropdown
$products = $pdo->query("SELECT id, name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY name")->fetchAll();

// Fetch sales history
$salesHistory = $pdo->query("
    SELECT s.id, s.sale_date, s.total_amount,
           GROUP_CONCAT(CONCAT(p.name, ' x', si.quantity) SEPARATOR ', ') as items,
           SUM(si.quantity) as total_items
    FROM sales s
    LEFT JOIN sale_items si ON s.id = si.sale_id
    LEFT JOIN products p ON si.product_id = p.id
    GROUP BY s.id
    ORDER BY s.sale_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales — Nournia Shop</title>
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
            <li><a href="products.php"><span class="icon">📦</span> Products</a></li>
            <li><a href="sales.php" class="active"><span class="icon">💰</span> Sales</a></li>
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
            <h2>💰 การขาย</h2>
            <p>บันทึกรายการขายและดูประวัติ</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- New Sale Form -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>🛒 สร้างรายการขายใหม่</h3>
            </div>
            <div class="card-body">
                <?php if (count($products) > 0): ?>
                <form method="POST" id="saleForm">
                    <input type="hidden" name="action" value="sell">

                    <div id="saleItems">
                        <div class="sale-item-row" data-index="0">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>สินค้า</label>
                                <select name="product_id[]" class="form-control product-select" required onchange="updatePrice(this)">
                                    <option value="">-- เลือกสินค้า --</option>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" data-stock="<?= $p['stock_quantity'] ?>">
                                        <?= htmlspecialchars($p['name']) ?> (฿<?= number_format($p['price'], 2) ?>) [Stock: <?= $p['stock_quantity'] ?>]
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>จำนวน</label>
                                <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required oninput="calcTotal()">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>ราคารวม</label>
                                <input type="text" class="form-control line-total" readonly value="฿0.00">
                            </div>
                            <div style="padding-bottom: 2px;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)" style="margin-top: 1.6rem;">✕</button>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <button type="button" class="btn btn-secondary" onclick="addItem()">➕ เพิ่มรายการ</button>
                        <div class="sale-total">
                            รวมทั้งหมด: <span id="grandTotal">฿0.00</span>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; text-align: right;">
                        <button type="submit" class="btn btn-success" onclick="return confirm('ยืนยันการขาย?')">💳 บันทึกการขาย</button>
                    </div>
                </form>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">📦</div>
                    <p>ไม่มีสินค้าที่มี stock พร้อมขาย</p>
                    <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">เพิ่มสินค้า</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales History -->
        <div class="card">
            <div class="card-header">
                <h3>📋 ประวัติการขาย</h3>
            </div>
            <div class="card-body">
                <?php if (count($salesHistory) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>วันที่</th>
                                <th>รายการสินค้า</th>
                                <th>จำนวนรวม</th>
                                <th>ยอดรวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salesHistory as $sale): ?>
                            <tr>
                                <td><?= $sale['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($sale['sale_date'])) ?></td>
                                <td><?= htmlspecialchars($sale['items'] ?? '-') ?></td>
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
                    <p>ยังไม่มีประวัติการขาย</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
const productsData = <?= json_encode($products) ?>;

function addItem() {
    const container = document.getElementById('saleItems');
    const index = container.children.length;
    const options = productsData.map(p =>
        `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock_quantity}">${escapeHtml(p.name)} (฿${Number(p.price).toLocaleString('th-TH', {minimumFractionDigits: 2})}) [Stock: ${p.stock_quantity}]</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'sale-item-row';
    row.dataset.index = index;
    row.innerHTML = `
        <div class="form-group" style="margin-bottom: 0;">
            <label>สินค้า</label>
            <select name="product_id[]" class="form-control product-select" required onchange="updatePrice(this)">
                <option value="">-- เลือกสินค้า --</option>
                ${options}
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>จำนวน</label>
            <input type="number" name="quantity[]" class="form-control qty-input" min="1" value="1" required oninput="calcTotal()">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>ราคารวม</label>
            <input type="text" class="form-control line-total" readonly value="฿0.00">
        </div>
        <div style="padding-bottom: 2px;">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)" style="margin-top: 1.6rem;">✕</button>
        </div>
    `;
    container.appendChild(row);
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.sale-item-row');
    if (rows.length <= 1) return;
    btn.closest('.sale-item-row').remove();
    calcTotal();
}

function updatePrice(select) {
    const row = select.closest('.sale-item-row');
    const qty = parseInt(row.querySelector('.qty-input').value) || 1;
    const option = select.options[select.selectedIndex];
    const price = parseFloat(option.dataset.price) || 0;
    const maxStock = parseInt(option.dataset.stock) || 0;

    const qtyInput = row.querySelector('.qty-input');
    qtyInput.max = maxStock;

    const lineTotal = price * qty;
    row.querySelector('.line-total').value = '฿' + lineTotal.toLocaleString('th-TH', {minimumFractionDigits: 2});
    calcTotal();
}

function calcTotal() {
    let grand = 0;
    document.querySelectorAll('.sale-item-row').forEach(row => {
        const select = row.querySelector('.product-select');
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const option = select.options[select.selectedIndex];
        const price = parseFloat(option?.dataset?.price) || 0;
        const lineTotal = price * qty;
        row.querySelector('.line-total').value = '฿' + lineTotal.toLocaleString('th-TH', {minimumFractionDigits: 2});
        grand += lineTotal;
    });
    document.getElementById('grandTotal').textContent = '฿' + grand.toLocaleString('th-TH', {minimumFractionDigits: 2});
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
</body>
</html>
