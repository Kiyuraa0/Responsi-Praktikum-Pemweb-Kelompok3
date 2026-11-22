<?php
require __DIR__ . '/../config.php';
require_role('admin');
$pdo = db();
$count = fn(string $sql) => (int)$pdo->query($sql)->fetchColumn();
$stats = [
    'Total pengguna' => $count('SELECT COUNT(*) FROM users'),
    'Klien' => $count("SELECT COUNT(*) FROM users WHERE role = 'client'"),
    'Penyedia jasa' => $count("SELECT COUNT(*) FROM users WHERE role = 'provider'"),
    'Akun nonaktif' => $count('SELECT COUNT(*) FROM users WHERE is_active = 0'),
    'Karya portofolio' => $count('SELECT COUNT(*) FROM portfolios'),
];
$latest = $pdo->query('SELECT name, email, role, created_at FROM users ORDER BY id DESC LIMIT 5')->fetchAll();
$title = 'Ringkasan admin';
require __DIR__ . '/../partials/header.php';
?>
<h1>Ringkasan</h1>
<div class="stats">
  <?php foreach ($stats as $label => $n): ?>
    <div class="stat"><strong><?= $n ?></strong><span><?= e($label) ?></span></div>
  <?php endforeach; ?>
</div>
<section class="card">
  <h2>Pendaftar terbaru</h2>
  <table>
    <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Daftar</th></tr>
    <?php foreach ($latest as $r): ?>
      <tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= e($r['created_at']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  <p><a href="users.php">Kelola semua pengguna</a></p>
</section>
<?php require __DIR__ . '/../partials/footer.php'; ?>
