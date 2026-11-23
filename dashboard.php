<?php
require_once __DIR__ . '/config.php';
$user = require_role('client', 'provider', 'admin');
if ($user['role'] === 'admin') {
    redirect(BASE_URL . '/admin/index.php');
}

$pdo = db();
$isProvider = $user['role'] === 'provider';

if ($isProvider) {
    $stLayanan = $pdo->prepare("SELECT COUNT(*) FROM services WHERE provider_id = ? AND status = 'aktif'");
    $stLayanan->execute([$user['id']]);
    $jmlLayanan = (int)$stLayanan->fetchColumn();

    $stAktif = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE provider_id = ? AND status IN ('menunggu', 'diterima', 'dikerjakan')");
    $stAktif->execute([$user['id']]);
    $pesananAktif = (int)$stAktif->fetchColumn();

    $stSelesai = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga), 0) FROM orders WHERE provider_id = ? AND status = 'selesai'");
    $stSelesai->execute([$user['id']]);
    [$pesananSelesai, $totalPendapatan] = $stSelesai->fetch(PDO::FETCH_NUM);

    $stRating = $pdo->prepare("SELECT COUNT(*), COALESCE(AVG(rating), 0) FROM reviews WHERE provider_id = ? AND is_hidden = 0");
    $stRating->execute([$user['id']]);
    [$jmlUlasan, $rataRating] = $stRating->fetch(PDO::FETCH_NUM);

    $recentStmt = $pdo->prepare("
        SELECT o.*, s.judul AS judul_layanan, u.name AS pihak_lain
        FROM orders o
        JOIN services s ON o.service_id = s.id
        JOIN users u ON o.client_id = u.id
        WHERE o.provider_id = ?
        ORDER BY o.created_at DESC LIMIT 5
    ");
    $recentStmt->execute([$user['id']]);
    $recentOrders = $recentStmt->fetchAll();
} else {
    $stAktif = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE client_id = ? AND status IN ('menunggu', 'diterima', 'dikerjakan')");
    $stAktif->execute([$user['id']]);
    $pesananAktif = (int)$stAktif->fetchColumn();

    $stSelesai = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_harga), 0) FROM orders WHERE client_id = ? AND status = 'selesai'");
    $stSelesai->execute([$user['id']]);
    [$pesananSelesai, $totalPengeluaran] = $stSelesai->fetch(PDO::FETCH_NUM);

    $stBelumUlas = $pdo->prepare("
        SELECT COUNT(*) FROM orders o
        LEFT JOIN reviews r ON r.order_id = o.id
        WHERE o.client_id = ? AND o.status = 'selesai' AND r.id IS NULL
    ");
    $stBelumUlas->execute([$user['id']]);
    $belumDiulas = (int)$stBelumUlas->fetchColumn();

    $recentStmt = $pdo->prepare("
        SELECT o.*, s.judul AS judul_layanan, u.name AS pihak_lain
        FROM orders o
        JOIN services s ON o.service_id = s.id
        JOIN users u ON o.provider_id = u.id
        WHERE o.client_id = ?
        ORDER BY o.created_at DESC LIMIT 5
    ");
    $recentStmt->execute([$user['id']]);
    $recentOrders = $recentStmt->fetchAll();
}

$title = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>
<h1>Halo, <?= e($user['name']) ?> <span class="tag"><?= $isProvider ? 'Penyedia Jasa' : 'Klien' ?></span></h1>
<p class="muted" style="margin-top:-10px; margin-bottom:20px;">
    <?= $isProvider
        ? 'Pantau pesanan masuk, reputasi rating, dan catatan pendapatan jasa Anda secara transparan.'
        : 'Temukan penyedia jasa lokal terpercaya dan pantau progres pesanan Anda.' ?>
</p>

<?php if ($isProvider): ?>
<div class="stats">
    <div class="stat">
        <strong>Rp <?= number_format((int)$totalPendapatan, 0, ',', '.') ?></strong>
        <span>Pendapatan Terpantau</span>
    </div>
    <div class="stat">
        <strong><?= $pesananAktif ?></strong>
        <span>Pesanan Aktif</span>
    </div>
    <div class="stat">
        <strong><?= (int)$pesananSelesai ?></strong>
        <span>Pekerjaan Selesai</span>
    </div>
    <div class="stat">
        <strong>★ <?= number_format((float)$rataRating, 1) ?></strong>
        <span>Rating (<?= (int)$jmlUlasan ?> Ulasan)</span>
    </div>
    <div class="stat">
        <strong><?= $jmlLayanan ?></strong>
        <span>Layanan Aktif</span>
    </div>
</div>

<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
    <a class="btn" href="<?= BASE_URL ?>/tambah_layanan.php">+ Pasang Layanan Baru</a>
    <a class="btn ghost" href="<?= BASE_URL ?>/pesanan_saya.php">Kelola Pesanan Masuk</a>
    <a class="btn ghost" href="<?= BASE_URL ?>/ulasan.php">Lihat Ulasan</a>
    <a class="btn ghost" href="<?= BASE_URL ?>/profile.php">Lengkapi Portofolio</a>
</div>
<?php else: ?>
<div class="stats">
    <div class="stat">
        <strong><?= $pesananAktif ?></strong>
        <span>Pesanan Berjalan</span>
    </div>
    <div class="stat">
        <strong><?= (int)$pesananSelesai ?></strong>
        <span>Pesanan Selesai</span>
    </div>
    <div class="stat">
        <strong><?= $belumDiulas ?></strong>
        <span>Menunggu Ulasan</span>
    </div>
    <div class="stat">
        <strong>Rp <?= number_format((int)$totalPengeluaran, 0, ',', '.') ?></strong>
        <span>Total Transaksi Selesai</span>
    </div>
</div>

<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;">
    <a class="btn" href="<?= BASE_URL ?>/katalog.php">Cari Jasa Lokal</a>
    <a class="btn ghost" href="<?= BASE_URL ?>/pesanan_saya.php">Semua Pesanan Saya</a>
    <a class="btn ghost" href="<?= BASE_URL ?>/ulasan.php">Riwayat Ulasan</a>
</div>
<?php endif; ?>

<section class="card">
    <h2>Aktivitas Pesanan Terbaru</h2>
    <?php if (!$recentOrders): ?>
        <p class="muted">Belum ada transaksi pesanan.</p>
    <?php else: ?>
        <div class="scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Layanan</th>
                        <th><?= $isProvider ? 'Klien' : 'Penyedia Jasa' ?></th>
                        <th>Jadwal</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $ro): ?>
                    <tr>
                        <td>#<?= (int)$ro['id'] ?></td>
                        <td><?= e($ro['judul_layanan']) ?></td>
                        <td><?= e($ro['pihak_lain']) ?></td>
                        <td><?= e($ro['tanggal_pengerjaan']) ?></td>
                        <td>Rp <?= number_format((int)$ro['total_harga'], 0, ',', '.') ?></td>
                        <td><span class="tag"><?= strtoupper(e($ro['status'])) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="margin-top:12px;"><a href="<?= BASE_URL ?>/pesanan_saya.php" class="btn ghost">Lihat semua pesanan &rarr;</a></p>
    <?php endif; ?>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
