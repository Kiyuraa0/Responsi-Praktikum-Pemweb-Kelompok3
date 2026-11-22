<?php
require __DIR__ . '/config.php';
if ($u = current_user()) redirect(home_for($u['role']));
$title = 'Beranda';
require __DIR__ . '/partials/header.php';
?>
<section class="hero">
  <h1>Cari jasa lokal yang bisa dipercaya, atau tawarkan keahlianmu.</h1>
  <p>Gawean mempertemukan warga dan UMKM dengan tukang, penjahit, fotografer, dan penyedia jasa di sekitarmu.</p>
  <p><a class="btn" href="register.php">Buat akun gratis</a> <a class="btn ghost" href="login.php">Sudah punya akun</a></p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
