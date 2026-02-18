<?php
require_once 'auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username)) {
        $errors[] = 'กรุณากรอกชื่อผู้ใช้';
    } elseif (strlen($username) < 3) {
        $errors[] = 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร';
    } elseif (strlen($username) > 50) {
        $errors[] = 'ชื่อผู้ใช้ต้องไม่เกิน 50 ตัวอักษร';
    }

    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    } elseif (strlen($password) < 6) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }

    // Check unique username/email
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว';
        }
    }

    // Create user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hashedPassword]);

        // Auto-login
        $userId = $pdo->lastInsertId();
        loginUser([
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'user',
        ]);

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก — Nournia Shop</title>
    <meta name="description" content="สมัครสมาชิก Nournia Shop — ร้านเกมมิ่งเกียร์ออนไลน์">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">🎮</div>
                <h1>Nournia Shop</h1>
                <p>สมัครสมาชิกเพื่อเริ่มช้อปปิ้ง</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $error): ?>
                <div class="auth-error-item">❌ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="registerForm" novalidate>
                <div class="form-group">
                    <label for="username">👤 ชื่อผู้ใช้</label>
                    <input type="text" name="username" id="username" class="form-control"
                           placeholder="เช่น gamer2025"
                           value="<?= htmlspecialchars($username) ?>" required minlength="3" maxlength="50">
                    <span class="field-error" id="username-error"></span>
                </div>

                <div class="form-group">
                    <label for="email">📧 อีเมล</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="example@email.com"
                           value="<?= htmlspecialchars($email) ?>" required>
                    <span class="field-error" id="email-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">🔒 รหัสผ่าน</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" class="form-control"
                               placeholder="อย่างน้อย 6 ตัวอักษร" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">👁️</button>
                    </div>
                    <span class="field-error" id="password-error"></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">🔒 ยืนยันรหัสผ่าน</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                               placeholder="กรอกรหัสผ่านอีกครั้ง" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</button>
                    </div>
                    <span class="field-error" id="confirm-error"></span>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="registerBtn">
                    ✨ สมัครสมาชิก
                </button>
            </form>

            <div class="auth-footer">
                <p>มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
            </div>
        </div>
    </div>

    <script>
    // Client-side validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        let valid = true;
        clearErrors();

        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');

        if (!username.value.trim()) {
            showError('username-error', 'กรุณากรอกชื่อผู้ใช้');
            valid = false;
        } else if (username.value.trim().length < 3) {
            showError('username-error', 'ชื่อผู้ใช้ต้องมีอย่างน้อย 3 ตัวอักษร');
            valid = false;
        }

        if (!email.value.trim()) {
            showError('email-error', 'กรุณากรอกอีเมล');
            valid = false;
        } else if (!isValidEmail(email.value)) {
            showError('email-error', 'รูปแบบอีเมลไม่ถูกต้อง');
            valid = false;
        }

        if (!password.value) {
            showError('password-error', 'กรุณากรอกรหัสผ่าน');
            valid = false;
        } else if (password.value.length < 6) {
            showError('password-error', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
            valid = false;
        }

        if (password.value !== confirm.value) {
            showError('confirm-error', 'รหัสผ่านไม่ตรงกัน');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            shakeForm();
        }
    });

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showError(id, msg) {
        const el = document.getElementById(id);
        el.textContent = msg;
        el.style.display = 'block';
        el.previousElementSibling.classList.add('input-error');
    }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    }

    function shakeForm() {
        const card = document.querySelector('.auth-card');
        card.classList.add('shake');
        setTimeout(() => card.classList.remove('shake'), 500);
    }

    function togglePassword(fieldId, btn) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            btn.textContent = '🔒';
        } else {
            field.type = 'password';
            btn.textContent = '👁️';
        }
    }
    </script>
</body>
</html>
