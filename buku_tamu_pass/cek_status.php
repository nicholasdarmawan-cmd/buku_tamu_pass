<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

$page_title = "Cek Status & Cetak Visitor Pass - Buku Tamu PASS";
$kode_input = trim($_GET['kode'] ?? $_POST['kode'] ?? '');
$pass_data = null;
$log_data = null;
$search_error = '';

if ($kode_input !== '') {
    if (!preg_match('/^[A-Za-z0-9\-]{5,30}$/', $kode_input)) {
        $search_error = "Format kode referensi PASS tidak valid.";
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, u.nama AS nama_pegawai, u.divisi AS divisi_pegawai
            FROM pengajuan_pass p
            JOIN users u ON p.id_tujuan_pegawai = u.id_user
            WHERE p.kode_pass = ?
        ");
        $stmt->bind_param("s", $kode_input);
        $stmt->execute();
        $result = $stmt->get_result();
        $pass_data = $result->fetch_assoc();
        $stmt->close();

        if (!$pass_data) {
            $search_error = "Data pengajuan dengan kode referensi '" . $kode_input . "' tidak ditemukan.";
        } else {
            // Cek status log akses terakhir jika ada
            $stmt_log = $conn->prepare("
                SELECT l.*, s.nama AS nama_petugas
                FROM log_akses l
                JOIN users s ON l.id_petugas = s.id_user
                WHERE l.id_pass = ?
                ORDER BY l.id_log DESC LIMIT 1
            ");
            $stmt_log->bind_param("i", $pass_data['id_pass']);
            $stmt_log->execute();
            $log_data = $stmt_log->get_result()->fetch_assoc();
            $stmt_log->close();
        }
    }
}

include 'includes/header.php';
?>

<h2>Halaman Cek Status &amp; Cetak Visitor Pass</h2>
<p>Masukkan <strong>Kode Referensi PASS</strong> Anda untuk melihat status persetujuan dan mencetak Kartu Visitor Pass.</p>

<form method="GET" action="cek_status.php">
    <fieldset>
        <legend><strong>Pencarian Kode Referensi PASS</strong></legend>
        <label for="kode"><strong>Kode PASS:</strong></label>
        <input type="text" id="kode" name="kode" value="<?php echo e($kode_input); ?>" required minlength="5" maxlength="30" size="30" placeholder="Contoh: PASS-20261112-1001">
        <button type="submit">Cek Status</button>
    </fieldset>
</form>

<br>

<?php if ($search_error !== ''): ?>
    <fieldset>
        <legend><strong>Hasil Pencarian</strong></legend>
        <p><?php echo e($search_error); ?></p>
    </fieldset>
<?php endif; ?>

<?php if ($pass_data): ?>
    <fieldset>
        <legend><strong>Detail Status Permohonan Kunjungan</strong></legend>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th align="left" width="30%">Kode Referensi PASS</th>
                <td><strong><?php echo e($pass_data['kode_pass']); ?></strong></td>
            </tr>
            <tr>
                <th align="left">Nama Tamu</th>
                <td><?php echo e($pass_data['nama_tamu']); ?></td>
            </tr>
            <tr>
                <th align="left">Nomor Identitas</th>
                <td><?php echo e($pass_data['no_identitas']); ?></td>
            </tr>
            <tr>
                <th align="left">Instansi / Perusahaan</th>
                <td><?php echo e($pass_data['instansi']); ?></td>
            </tr>
            <tr>
                <th align="left">Pegawai Tujuan</th>
                <td><?php echo e($pass_data['nama_pegawai'] . ' - ' . $pass_data['divisi_pegawai']); ?></td>
            </tr>
            <tr>
                <th align="left">Tanggal Rencana Kunjungan</th>
                <td><?php echo e(date('d-m-Y', strtotime($pass_data['tgl_rencana']))); ?></td>
            </tr>
            <tr>
                <th align="left">Keperluan</th>
                <td><?php echo e($pass_data['keperluan']); ?></td>
            </tr>
            <tr>
                <th align="left">Status Persetujuan (Approval)</th>
                <td>
                    <strong>[ <?php echo e(strtoupper($pass_data['status_approval'])); ?> ]</strong>
                    <?php if ($pass_data['status_approval'] === 'Pending'): ?>
                        - Menunggu persetujuan dari pegawai tujuan / admin.
                    <?php elseif ($pass_data['status_approval'] === 'Rejected'): ?>
                        - Permohonan kunjungan ditolak. Anda tidak diizinkan masuk area gedung.
                    <?php else: ?>
                        - Disetujui! Silakan cetak Visitor Pass di bawah ini dan tunjukkan ke Pos Keamanan.
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th align="left">Status Akses Gedung</th>
                <td>
                    <?php if ($log_data): ?>
                        <strong><?php echo e($log_data['status_pass']); ?></strong>
                        (Check-in: <?php echo e($log_data['waktu_checkin']); ?>
                        <?php if ($log_data['waktu_checkout']): ?>
                            | Check-out: <?php echo e($log_data['waktu_checkout']); ?>
                        <?php endif; ?>)
                    <?php else: ?>
                        Belum Check-in di Pos Keamanan
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </fieldset>

    <?php if ($pass_data['status_approval'] === 'Approved'): ?>
        <br>
        <fieldset>
            <legend><strong>KARTU VISITOR PASS (SIAP CETAK)</strong></legend>
            <table border="2" cellpadding="10" cellspacing="0" width="100%">
                <tr>
                    <th colspan="2">
                        <h3>ENTERPRISE VISITOR PASS - IZIN MASUK GEDUNG</h3>
                        <p>STATUS: APPROVED / SAH UNTUK AKSES POS KEAMANAN</p>
                    </th>
                </tr>
                <tr>
                    <td width="35%" align="center">
                        <strong>KODE PASS</strong>
                        <h2><?php echo e($pass_data['kode_pass']); ?></h2>
                        <small>*Tunjukkan kode ini kepada Petugas Keamanan saat Check-in &amp; Check-out*</small>
                    </td>
                    <td>
                        <table border="1" cellpadding="5" cellspacing="0" width="100%">
                            <tr>
                                <th align="left" width="35%">Nama Tamu</th>
                                <td><strong><?php echo e($pass_data['nama_tamu']); ?></strong></td>
                            </tr>
                            <tr>
                                <th align="left">No. Identitas</th>
                                <td><?php echo e($pass_data['no_identitas']); ?></td>
                            </tr>
                            <tr>
                                <th align="left">Instansi</th>
                                <td><?php echo e($pass_data['instansi']); ?></td>
                            </tr>
                            <tr>
                                <th align="left">Pegawai Tujuan</th>
                                <td><?php echo e($pass_data['nama_pegawai'] . ' (' . $pass_data['divisi_pegawai'] . ')'); ?></td>
                            </tr>
                            <tr>
                                <th align="left">Tanggal Berlaku</th>
                                <td><strong><?php echo e(date('d F Y', strtotime($pass_data['tgl_rencana']))); ?></strong></td>
                            </tr>
                            <tr>
                                <th align="left">Keperluan</th>
                                <td><?php echo e($pass_data['keperluan']); ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <br>
            <button type="button" onclick="window.print();">Cetak Kartu Visitor Pass (Print)</button>
        </fieldset>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
