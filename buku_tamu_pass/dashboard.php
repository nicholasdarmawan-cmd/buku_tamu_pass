<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

// Semua role internal yang terautentikasi dapat melihat Dashboard Keamanan
require_role(['admin', 'pegawai', 'keamanan']);

$page_title = "Dashboard Keamanan - Buku Tamu PASS";

// Hitung statistik pengajuan
$stat_pending = 0;
$stat_approved = 0;
$stat_rejected = 0;

$res_stat = $conn->query("SELECT status_approval, COUNT(*) AS total FROM pengajuan_pass GROUP BY status_approval");
if ($res_stat) {
    while ($r = $res_stat->fetch_assoc()) {
        if ($r['status_approval'] === 'Pending') $stat_pending = (int)$r['total'];
        if ($r['status_approval'] === 'Approved') $stat_approved = (int)$r['total'];
        if ($r['status_approval'] === 'Rejected') $stat_rejected = (int)$r['total'];
    }
}

// Hitung statistik log akses gedung (Di Dalam Gedung vs Sudah Keluar)
$stat_inside = 0;
$stat_outside = 0;

$res_log_stat = $conn->query("SELECT status_pass, COUNT(*) AS total FROM log_akses GROUP BY status_pass");
if ($res_log_stat) {
    while ($r = $res_log_stat->fetch_assoc()) {
        if ($r['status_pass'] === 'Di Dalam Gedung') $stat_inside = (int)$r['total'];
        if ($r['status_pass'] === 'kedarluasa') $stat_outside = (int)$r['total'];
    }
}

// Daftar tamu yang saat ini berada di dalam gedung
$active_visitors = [];
$res_active = $conn->query("
    SELECT l.id_log, l.waktu_checkin, p.kode_pass, p.nama_tamu, p.instansi, p.keperluan,
           u.nama AS nama_pegawai, u.divisi AS divisi_pegawai, s.nama AS nama_petugas
    FROM log_akses l
    JOIN pengajuan_pass p ON l.id_pass = p.id_pass
    JOIN users u ON p.id_tujuan_pegawai = u.id_user
    JOIN users s ON l.id_petugas = s.id_user
    WHERE l.status_pass = 'Di Dalam Gedung'
    ORDER BY l.waktu_checkin DESC
");
if ($res_active) {
    while ($row = $res_active->fetch_assoc()) {
        $active_visitors[] = $row;
    }
}

include 'includes/header.php';
?>

<h2>Dashboard Keamanan &amp; Monitoring Tamu Gedung</h2>
<p>Ringkasan status pengajuan kunjungan dan pemantauan real-time pergerakan tamu di area gedung.</p>

<fieldset>
    <legend><strong>Statistik Monitoring Kunjungan</strong></legend>
    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Tamu Pending (Menunggu Approval)</th>
                <th>Tamu Approved (Disetujui)</th>
                <th>Tamu Di Dalam Gedung (Aktif)</th>
                <th>Tamu Sudah Keluar</th>
                <th>Pengajuan Ditolak (Rejected)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td align="center">
                    <h2><?php echo $stat_pending; ?></h2>
                    <small>Menunggu Konfirmasi</small>
                </td>
                <td align="center">
                    <h2><?php echo $stat_approved; ?></h2>
                    <small>Izin Masuk Terbit</small>
                </td>
                <td align="center">
                    <h2><?php echo $stat_inside; ?></h2>
                    <small>Sedang Berada di Gedung</small>
                </td>
                <td align="center">
                    <h2><?php echo $stat_outside; ?></h2>
                    <small>Selesai Kunjungan</small>
                </td>
                <td align="center">
                    <h2><?php echo $stat_rejected; ?></h2>
                    <small>Tidak Diizinkan</small>
                </td>
            </tr>
        </tbody>
    </table>
</fieldset>

<br>

<fieldset>
    <legend><strong>Daftar Tamu yang Sedang Berada di Dalam Gedung (Real-Time)</strong></legend>
    <?php if (empty($active_visitors)): ?>
        <p>Saat ini tidak ada tamu yang tercatat berada di dalam gedung.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode PASS</th>
                    <th>Nama Tamu</th>
                    <th>Instansi</th>
                    <th>Pegawai Tujuan</th>
                    <th>Waktu Check-In</th>
                    <th>Petugas Gate</th>
                    <?php if (in_array($_SESSION['role'], ['admin', 'keamanan'], true)): ?>
                        <th>Aksi Cepat</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($active_visitors as $v): ?>
                    <tr>
                        <td align="center"><?php echo $no++; ?></td>
                        <td><strong><?php echo e($v['kode_pass']); ?></strong></td>
                        <td><?php echo e($v['nama_tamu']); ?></td>
                        <td><?php echo e($v['instansi']); ?></td>
                        <td><?php echo e($v['nama_pegawai'] . ' (' . $v['divisi_pegawai'] . ')'); ?></td>
                        <td><?php echo e($v['waktu_checkin']); ?></td>
                        <td><?php echo e($v['nama_petugas']); ?></td>
                        <?php if (in_array($_SESSION['role'], ['admin', 'keamanan'], true)): ?>
                            <td align="center">
                                <a href="pos_keamanan.php?kode_pass=<?php echo urlencode($v['kode_pass']); ?>">Proses Check-Out</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</fieldset>

<?php include 'includes/footer.php'; ?>
