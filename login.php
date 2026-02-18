<?php
require_once 'auth.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php';

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            loginUser($user);
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Nournia Shop</title>
    <meta name="description" content="เข้าสู่ระบบ Nournia Shop — ร้านเกมมิ่งเกียร์ออนไลน์">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">🎮</div>
                <h1>Nournia Shop</h1>
                <p>เข้าสู่ระบบเพื่อจัดการร้านค้า</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="auth-errors">
                <?php foreach ($errors as $error): ?>
                <div class="auth-error-item">❌ <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="loginForm" novalidate>
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
                               placeholder="กรอกรหัสผ่าน" required minlength="6">
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">👁️</button>
                    </div>
                    <span class="field-error" id="password-error"></span>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    🔐 เข้าสู่ระบบ
                </button>
            </form>

            <div class="auth-footer">
                <p>ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
            </div>

            <div class="auth-demo-info">
                <p>🛠 <strong>Admin Demo:</strong> admin@nournia.shop / admin123</p>
            </div>
        </div>
    </div>

    <script>
    // Client-side validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        let valid = true;
        clearErrors();

        const email = document.getElementById('email');
        const password = document.getElementById('password');

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
