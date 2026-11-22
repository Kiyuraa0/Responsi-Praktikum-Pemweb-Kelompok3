<?php
require __DIR__ . '/config.php';
if ($u = current_user()) redirect(home_for($u['role']));

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $s = db()->prepare('SELECT * FROM users WHERE email = ?'); $s->execute([$email]);
    $user = $s->fetch();
    if (!$user || !password_verify($_POST['password'] ?? '', $user['password'])) {
        $error = 'Email atau password salah.';
    } elseif (!$user['is_active']) {
        $error = 'Akun Anda dinonaktifkan. Hubungi admin.';
    } else {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$user['id'];
        redirect(home_for($user['role']));
    }
}
$title = 'Masuk';
require __DIR__ . '/partials/header.php';
?>
<section class="card narrow">
  <h1>Masuk</h1>
  <?php if ($error): ?><p class="flash err"><?= e($error) ?></p><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required></label>
    <label>Password <input type="password" name="password" required></label>
    <button class="btn">Masuk</button>
  </form>
  <p class="muted">Belum punya akun? <a href="register.php">Daftar</a></p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
