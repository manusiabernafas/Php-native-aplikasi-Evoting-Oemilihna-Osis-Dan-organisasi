<?php
if (user()) redirect('dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT * FROM users WHERE username = ?', [trim($_POST['username'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard');
    }
    $error = 'Username atau password salah.';
}
$title = 'Panitia';
require __DIR__ . '/../../includes/booth_header.php';
?>
<form method="post" class="booth-login">
    <?= csrf_field() ?>
    <span class="label" style="color:var(--red)">Panel panitia</span>
    <h1 style="margin-top:8px">Masuk</h1>
    <p class="muted" style="margin:0 0 24px">Untuk pembina dan panitia pemilihan.</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <div style="margin-bottom:14px"><label>Username</label><input type="text" name="username" required autofocus></div>
    <div style="margin-bottom:20px"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-black btn-block">Masuk</button>
    <div class="note">Akun demo: <b>admin</b> / <b>admin123</b> atau <b>panitia</b> / <b>admin123</b></div>
</form>
<?php require __DIR__ . '/../../includes/booth_footer.php'; ?>
