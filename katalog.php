<?php
require_once __DIR__ . '/config.php';

$keyword   = trim($_GET['keyword'] ?? '');
$kategori  = (int)($_GET['kategori'] ?? 0);
$lokasi    = (int)($_GET['lokasi'] ?? 0);
$harga_min = ($_GET['harga_min'] ?? '') !== '' ? (int)$_GET['harga_min'] : 0;
$harga_max = ($_GET['harga_max'] ?? '') !== '' ? (int)$_GET['harga_max'] : 0;

$where = ["s.status = 'aktif'"];
$params = [];

if ($keyword !== '') {
    $where[] = "(s.judul LIKE ? OR s.deskripsi LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}
if ($kategori > 0) {
    $where[] = "s.category_id = ?";
    $params[] = $kategori;
}
if ($lokasi > 0) {
    $where[] = "s.location_id = ?";
    $params[] = $lokasi;
}
if ($harga_min > 0) {
    $where[] = "s.harga >= ?";
    $params[] = $harga_min;
}
if ($harga_max > 0) {
    $where[] = "s.harga <= ?";
    $params[] = $harga_max;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$limit = 6;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$count_stmt = db()->prepare("SELECT COUNT(*) FROM services s $where_sql");
$count_stmt->execute($params);
$total_data = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_data / $limit));

$sql = "
    SELECT s.*, c.nama_kategori, l.nama_wilayah, u.name AS nama_penyedia
    FROM services s
    JOIN categories c ON s.category_id = c.id
    JOIN locations l ON s.location_id = l.id
    JOIN users u ON s.provider_id = u.id
    $where_sql
    ORDER BY s.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY nama_kategori ASC')->fetchAll();
$locations  = db()->query('SELECT * FROM locations ORDER BY nama_wilayah ASC')->fetchAll();

require_once __DIR__ . '/partials/header.php';
?>
<div class="container">
    <h2>Cari Jasa Lokal</h2>
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px;">
        <input type="text" name="keyword" placeholder="Cari jasa..." value="<?= e($keyword); ?>">
        <select name="kategori">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id']; ?>" <?= $kategori == $c['id'] ? 'selected' : ''; ?>>
                    <?= e($c['nama_kategori']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="lokasi">
            <option value="0">Semua Lokasi</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id']; ?>" <?= $lokasi == $l['id'] ? 'selected' : ''; ?>>
                    <?= e($l['nama_wilayah']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="harga_min" placeholder="Harga Min" value="<?= $harga_min ?: ''; ?>">
        <input type="number" name="harga_max" placeholder="Harga Max" value="<?= $harga_max ?: ''; ?>">
        <button type="submit" class="btn">Cari</button>
        <a href="<?= BASE_URL; ?>/katalog.php" class="btn">Reset</a>
    </form>

    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap:20px;">
        <?php if (count($services) > 0): ?>
            <?php foreach ($services as $s): ?>
            <div style="border:1px solid #ddd; padding:12px; border-radius:8px;">
                <img src="<?= BASE_URL; ?>/uploads/<?= e($s['gambar']); ?>" style="width:100%; height:160px; object-fit:cover;">
                <h3><?= e($s['judul']); ?></h3>
                <p><strong>Penyedia:</strong> <?= e($s['nama_penyedia']); ?></p>
                <p><strong>Kategori:</strong> <?= e($s['nama_kategori']); ?></p>
                <p><strong>Lokasi:</strong> <?= e($s['nama_wilayah']); ?></p>
                <p><strong>Harga:</strong> Rp <?= number_format((int)$s['harga'], 0, ',', '.'); ?></p>
                <a href="<?= BASE_URL; ?>/pesan.php?service_id=<?= $s['id']; ?>" class="btn">Pesan Jasa Ini</a>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Tidak ada jasa yang sesuai dengan pencarianmu.</p>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php
            $q = $_GET;
            $q['page'] = $i;
            $url = BASE_URL . '/katalog.php?' . http_build_query($q);
            ?>
            <a href="<?= e($url); ?>" style="padding:6px 12px; border:1px solid #333; <?= $i == $page ? 'background:#333;color:#fff;' : ''; ?>">
                <?= $i; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
