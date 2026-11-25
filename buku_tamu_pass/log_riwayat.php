<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

// Fungsi badge dibuat di sini juga agar halaman ini berdiri sendiri
if (!function_exists('badge_pass')) {
    function badge_pass($status_approval, $status_log = null) {
        if ($status_approval !== 'Approved') {
            return '';
        }
        if ($status_log === 'Kedaluwarsa') {
            return '<span class="badge badge-expired">EXPIRED</span>';
        }
        return '<span class="badge badge-aktif">PASS AKTIF</span>';
    }
}

// Semua role internal yang terautentikasi dapat melihat audit log riwayat akses
require_role(['admin', 'pegawai', 'keamanan']);

$page_title = "Log Riwayat Akses Gedung - Buku Tamu PASS";

$filter_status = $_GET['status_pass'] ?? 'Semua';
$filter_tanggal = trim($_GET['tanggal'] ?? '');
$keyword = trim($_GET['q'] ?? '');

if (!in_array($filter_status, ['Semua', 'Di Dalam Gedung', 'Kedaluwarsa'], true)) {
    $filter_status = 'Semua';
}

$sql = "
    SELECT l.id_log, l.waktu_checkin, l.waktu_checkout, l.status_pass,
           p.kode_pass, p.nama_tamu, p.no_identitas, p.instansi, p.keperluan,
           u.nama AS nama_pegawai, u.divisi AS divisi_pegawai,
           s.nama AS nama_petugas
    FROM log_akses l
    JOIN pengajuan_pass p ON l.id_pass = p.id_pass
    JOIN users u ON p.id_tujuan_pegawai = u.id_user
    JOIN users s ON l.id_petugas = s.id_user
    WHERE 1=1
";

$params = [];
$types = "";

// Jika role pegawai, tampilkan riwayat tamu yang berkunjung ke pegawai tersebut
if ($_SESSION['role'] === 'pegawai') {
    $sql .= " AND p.id_tujuan_pegawai = ?";
    $params[] = (int)$_SESSION['id_user'];
    $types .= "i";
}

if ($filter_status !== 'Semua') {
    $sql .= " AND l.status_pass = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_tanggal !== '') {
    $sql .= " AND DATE(l.waktu_checkin) = ?";
    $params[] = $filter_tanggal;
    $types .= "s";
}

if ($keyword !== '') {
    $sql .= " AND (p.kode_pass LIKE ? OR p.nama_tamu LIKE ? OR p.instansi LIKE ?)";
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY l.id_log DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'includes/header.php';
?>

<h2>Log Riwayat Akses Gedung (Audit Trail)</h2>
<p>Rekapitulasi lengkap seluruh aktivitas masuk (Check-In) dan keluar (Check-Out) tamu di lingkungan gedung.</p>

<form method="GET" action="log_riwayat.php">
    <fieldset>
        <legend><strong>Filter &amp; Pencarian Log Akses</strong></legend>
        <label for="q"><strong>Kata Kunci:</strong></label>
        <input type="text" id="q" name="q" value="<?php echo e($keyword); ?>" placeholder="Kode PASS / Nama Tamu / Instansi" size="28">

        <label for="tanggal"><strong>Tanggal Check-In:</strong></label>
        <input type="date" id="tanggal" name="tanggal" value="<?php echo e($filter_tanggal); ?>">

        <label for="status_pass"><strong>Status Pass:</strong></label>
        <select id="status_pass" name="status_pass">
            <?php foreach (['Semua', 'Di Dalam Gedung', 'Kedaluwarsa'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo ($filter_status === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Filter Data</button>
        <a href="log_riwayat.php">Reset</a>
        <button type="button" onclick="window.print();">Cetak Laporan Log</button>
    </fieldset>
</form>

<br>

<fieldset>
    <legend><strong>Rekap Audit Aktivitas Keluar-Masuk Gedung (<?php echo count($logs); ?> Catatan)</strong></legend>
    <?php if (empty($logs)): ?>
        <p>Belum ada catatan log aktivitas keluar-masuk gedung yang sesuai dengan filter.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode PASS</th>
                    <th>Nama Tamu &amp; Identitas</th>
                    <th>Instansi</th>
                    <th>Pegawai Tujuan</th>
                    <th>Keperluan</th>
                    <th>Waktu Check-In</th>
                    <th>Waktu Check-Out</th>
                    <th>Status Pass</th>
                    <th>Petugas Pos</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($logs as $log): ?>
                    <tr>
                        <td align="center"><?php echo $no++; ?></td>
                        <td><strong><?php echo e($log['kode_pass']); ?></strong></td>
                        <td>
                            <strong><?php echo e($log['nama_tamu']); ?></strong><br>
                            <small><?php echo e($log['no_identitas']); ?></small>
                        </td>
                        <td><?php echo e($log['instansi']); ?></td>
                        <td><?php echo e($log['nama_pegawai'] . ' (' . $log['divisi_pegawai'] . ')'); ?></td>
                        <td><?php echo e($log['keperluan']); ?></td>
                        <td><?php echo e($log['waktu_checkin']); ?></td>
                        <td><?php echo $log['waktu_checkout'] ? e($log['waktu_checkout']) : '<em>Masih di dalam</em>'; ?></td>
                        <td align="center"><?php echo badge_pass('Approved', $log['status_pass']); ?><br><small><?php echo e($log['status_pass']); ?></small></td>
                        <td><?php echo e($log['nama_petugas']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</fieldset>

<?php include 'includes/footer.php'; ?>