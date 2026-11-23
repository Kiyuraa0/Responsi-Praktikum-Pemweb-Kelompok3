<?php
require_once __DIR__ . '/config.php';
$user = require_role('client', 'provider');
$pdo = db();
$isProvider = $user['role'] === 'provider';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $order_id = (int)($_POST['order_id'] ?? 0);
    $aksi     = $_POST['aksi'] ?? '';

    $st = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $st->execute([$order_id]);
    $ord = $st->fetch();

    if (!$ord) {
        flash('Pesanan tidak ditemukan.', 'err');
        redirect(BASE_URL . '/pesanan_saya.php');
    }

    if ($isProvider && (int)$ord['provider_id'] === (int)$user['id']) {
        $transisi = [
            'terima'  => ['dari' => 'menunggu',   'ke' => 'diterima',   'msg' => 'Pesanan diterima.'],
            'tolak'   => ['dari' => 'menunggu',   'ke' => 'dibatalkan', 'msg' => 'Pesanan ditolak/dibatalkan.'],
            'kerjakan'=> ['dari' => 'diterima',   'ke' => 'dikerjakan', 'msg' => 'Status diubah menjadi sedang dikerjakan.'],
            'selesai' => ['dari' => 'dikerjakan', 'ke' => 'selesai',    'msg' => 'Pekerjaan ditandai selesai.'],
        ];
        if (isset($transisi[$aksi]) && $ord['status'] === $transisi[$aksi]['dari']) {
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$transisi[$aksi]['ke'], $order_id]);
            flash($transisi[$aksi]['msg']);
        } else {
            flash('Perubahan status tidak valid.', 'err');
        }
    } elseif (!$isProvider && (int)$ord['client_id'] === (int)$user['id']) {
        if ($aksi === 'batal' && $ord['status'] === 'menunggu') {
            $pdo->prepare("UPDATE orders SET status = 'dibatalkan' WHERE id = ?")->execute([$order_id]);
            flash('Pesanan berhasil dibatalkan.');
        } elseif ($aksi === 'konfirmasi_selesai' && $ord['status'] === 'dikerjakan') {
            $pdo->prepare("UPDATE orders SET status = 'selesai' WHERE id = ?")->execute([$order_id]);
            flash('Pesanan selesai! Silakan berikan rating dan ulasan.');
        } else {
            flash('Aksi tidak diizinkan pada status pesanan ini.', 'err');
        }
    } else {
        flash('Akses ditolak.', 'err');
    }

    redirect(BASE_URL . '/pesanan_saya.php');
}

$filterStatus = $_GET['status'] ?? '';
$allowedStatus = ['menunggu', 'diterima', 'dikerjakan', 'selesai', 'dibatalkan'];

$where = $isProvider ? ["o.provider_id = ?"] : ["o.client_id = ?"];
$params = [$user['id']];

if (in_array($filterStatus, $allowedStatus, true)) {
    $where[] = "o.status = ?";
    $params[] = $filterStatus;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);
$sql = "
    SELECT o.*, s.judul AS judul_layanan, s.gambar,
           c.name AS nama_klien, c.phone AS telp_klien,
           p.name AS nama_penyedia, p.phone AS telp_penyedia,
           r.id AS review_id, r.rating AS review_rating
    FROM orders o
    JOIN services s ON o.service_id = s.id
    JOIN users c ON o.client_id = c.id
    JOIN users p ON o.provider_id = p.id
    LEFT JOIN reviews r ON r.order_id = o.id
    $whereSql
    ORDER BY o.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$badgeColor = [
    'menunggu'   => '#b36b00',
    'diterima'   => '#1b6ca8',
    'dikerjakan' => '#6f42c1',
    'selesai'    => '#1f6f5c',
    'dibatalkan' => '#b3361f',
];

$title = $isProvider ? 'Pesanan Masuk' : 'Pesanan Saya';
require_once __DIR__ . '/partials/header.php';
?>
<h1><?= $isProvider ? 'Pesanan Masuk (Penyedia Jasa)' : 'Riwayat & Status Pesanan Saya' ?></h1>

<form method="GET" class="inline" style="margin-bottom:18px;">
    <select name="status" onchange="this.form.submit()" style="max-width:240px;">
        <option value="">Semua Status</option>
        <?php foreach ($allowedStatus as $st): ?>
            <option value="<?= $st ?>" <?= $filterStatus === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($filterStatus !== ''): ?>
        <a class="btn ghost" href="<?= BASE_URL ?>/pesanan_saya.php">Reset</a>
    <?php endif; ?>
</form>

<?php if (!$orders): ?>
    <div class="card">
        <p class="muted">Belum ada data pesanan<?= $filterStatus ? ' dengan status ' . e($filterStatus) : '' ?>.</p>
        <?php if (!$isProvider): ?>
            <a class="btn" href="<?= BASE_URL ?>/katalog.php">Cari Jasa Sekarang</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($orders as $o): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; gap:14px; flex:1; min-width:250px;">
                    <img src="<?= BASE_URL ?>/uploads/<?= e($o['gambar']) ?>" alt="" style="width:84px; height:84px; object-fit:cover; border-radius:6px;">
                    <div>
                        <span class="tag" style="border-color:<?= $badgeColor[$o['status']] ?? '#333' ?>; color:<?= $badgeColor[$o['status']] ?? '#333' ?>;">
                            <?= strtoupper(e($o['status'])) ?>
                        </span>
                        <span class="muted" style="font-size:.85rem; margin-left:6px;">#ORD-<?= (int)$o['id'] ?></span>
                        <h2 style="margin:6px 0 4px;"><?= e($o['judul_layanan']) ?></h2>
                        <?php if ($isProvider): ?>
                            <p style="margin:2px 0;"><strong>Klien:</strong> <?= e($o['nama_klien']) ?> <?= $o['telp_klien'] ? '(' . e($o['telp_klien']) . ')' : '' ?></p>
                        <?php else: ?>
                            <p style="margin:2px 0;"><strong>Penyedia:</strong> <?= e($o['nama_penyedia']) ?> <?= $o['telp_penyedia'] ? '(' . e($o['telp_penyedia']) . ')' : '' ?></p>
                        <?php endif; ?>
                        <p style="margin:2px 0;"><strong>Jadwal:</strong> <?= e($o['tanggal_pengerjaan']) ?> | <strong>Total:</strong> Rp <?= number_format((int)$o['total_harga'], 0, ',', '.') ?></p>
                        <p style="margin:2px 0;"><strong>Alamat:</strong> <?= e($o['alamat_pengerjaan']) ?></p>
                        <?php if (!empty($o['catatan'])): ?>
                            <p class="muted" style="margin:4px 0 0; font-size:.9rem;">Catatan: <?= e($o['catatan']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="actions" style="flex-direction:column; align-items:flex-end;">
                    <?php if ($isProvider): ?>
                        <?php if ($o['status'] === 'menunggu'): ?>
                            <form method="POST">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="terima">
                                <button class="btn" type="submit">Terima Pesanan</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Tolak pesanan ini?')">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="tolak">
                                <button class="link danger" type="submit">Tolak</button>
                            </form>
                        <?php elseif ($o['status'] === 'diterima'): ?>
                            <form method="POST">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="kerjakan">
                                <button class="btn" type="submit">Mulai Kerjakan</button>
                            </form>
                        <?php elseif ($o['status'] === 'dikerjakan'): ?>
                            <form method="POST" onsubmit="return confirm('Tandai pekerjaan ini sudah selesai?')">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="selesai">
                                <button class="btn" type="submit">Selesaikan Pekerjaan</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($o['status'] === 'menunggu'): ?>
                            <form method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="batal">
                                <button class="btn ghost danger" type="submit">Batalkan Pesanan</button>
                            </form>
                        <?php elseif ($o['status'] === 'dikerjakan'): ?>
                            <form method="POST" onsubmit="return confirm('Konfirmasi bahwa jasa telah selesai dikerjakan?')">
                                <?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="aksi" value="konfirmasi_selesai">
                                <button class="btn" type="submit">Konfirmasi Selesai</button>
                            </form>
                        <?php elseif ($o['status'] === 'selesai'): ?>
                            <?php if (empty($o['review_id'])): ?>
                                <a class="btn" href="<?= BASE_URL ?>/ulasan.php?order_id=<?= (int)$o['id'] ?>">Beri Rating & Ulasan</a>
                            <?php else: ?>
                                <span class="tag">Sudah Diulas (★ <?= (int)$o['review_rating'] ?>/5)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
