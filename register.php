<?php
require __DIR__ . '/config.php';
if ($u = current_user()) redirect(home_for($u['role']));

$errors = []; $old = ['name' => '', 'email' => '', 'phone' => '', 'role' => 'client'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = [
        'name' => trim($_POST['name'] ?? ''), 'email' => strtolower(trim($_POST['email'] ?? '')),
        'phone' => trim($_POST['phone'] ?? ''), 'role' => $_POST['role'] ?? 'client',
    ];
    $pass = $_POST['password'] ?? ''; $confirm = $_POST['confirm'] ?? '';

    if (mb_strlen($old['name']) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($old['phone'] !== '' && !preg_match('/^[0-9+]{8,16}$/', $old['phone'])) $errors[] = 'Nomor telepon hanya berisi angka (8-16 digit).';
    if (!in_array($old['role'], ['client', 'provider'], true)) $errors[] = 'Peran tidak valid.';
    if (strlen($pass) < 8) $errors[] = 'Password minimal 8 karakter.';
    if ($pass !== $confirm) $errors[] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?'); $s->execute([$old['email']]);
        if ($s->fetch()) $errors[] = 'Email sudah terdaftar.';
    }
    if (!$errors) {
        $pdo = db(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO users (name, email, phone, password, role) VALUES (?,?,?,?,?)')
            ->execute([$old['name'], $old['email'], $old['phone'] ?: null, password_hash($pass, PASSWORD_DEFAULT), $old['role']]);
        if ($old['role'] === 'provider') {
            $pdo->prepare('INSERT INTO provider_profiles (user_id) VALUES (?)')->execute([$pdo->lastInsertId()]);
        }
        $pdo->commit();
        flash('Pendaftaran berhasil. Silakan masuk.');
        redirect('login.php');
    }
}
$title = 'Daftar';
require __DIR__ . '/partials/header.php';
?>
<section class="card narrow">
  <h1>Buat akun</h1>
  <?php foreach ($errors as $er): ?><p class="flash err"><?= e($er) ?></p><?php endforeach; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Nama lengkap <input name="name" value="<?= e($old['name']) ?>" required></label>
    <label>Email <input type="email" name="email" value="<?= e($old['email']) ?>" required></label>
    <label>No. telepon <input name="phone" value="<?= e($old['phone']) ?>"></label>
    <label>Saya ingin
      <select name="role">
        <option value="client" <?= $old['role'] === 'client' ? 'selected' : '' ?>>Mencari jasa (klien)</option>
        <option value="provider" <?= $old['role'] === 'provider' ? 'selected' : '' ?>>Menawarkan jasa (penyedia)</option>
      </select>
    </label>
    <label>Password <input type="password" name="password" minlength="8" required></label>
    <label>Ulangi password <input type="password" name="confirm" minlength="8" required></label>
    <button class="btn">Daftar</button>
  </form>
  <p class="muted">Sudah punya akun? <a href="login.php">Masuk</a></p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
