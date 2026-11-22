<?php $u = current_user(); $f = flash(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Gawean') ?> | Gawean</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="bar">
  <a class="brand" href="<?= BASE_URL ?>/">Gawean</a>
  <nav>
    <?php if ($u): ?>
      <?php if ($u['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/admin/index.php">Ringkasan</a>
        <a href="<?= BASE_URL ?>/admin/users.php">Pengguna</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/profile.php">Profil saya</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/logout.php">Keluar (<?= e($u['name']) ?>)</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php">Masuk</a>
      <a href="<?= BASE_URL ?>/register.php">Daftar</a>
    <?php endif; ?>
  </nav>
</header>
<main>
<?php if ($f): ?><div class="flash <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif; ?>
