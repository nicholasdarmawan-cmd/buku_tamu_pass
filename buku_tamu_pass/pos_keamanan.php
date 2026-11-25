<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

// Hanya Petugas Keamanan dan Admin yang berhak mengoperasikan Pos Keamanan
require_role(['admin', 'keamanan']);

$page_title = "Pos Keamanan (Gate Check-in & Check-out) - Buku Tamu PASS";
$id_petugas = (int)$_SESSION['id_user'];

// Proses Check-In atau Check-Out via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Token keamanan tidak valid.";
        header("Location: pos_keamanan.php");
        exit;
    }

    $id_pass = filter_input(INPUT_POST, 'id_pass', FILTER_VALIDATE_INT);
    $gate_action = $_POST['gate_action'] ?? '';
    $kode_redirect = trim($_POST['kode_pass'] ?? '');

    if (!$id_pass || !in_array($gate_action, ['checkin', 'checkout'], true)) {
        $_SESSION['flash_error'] = "Aksi pos keamanan tidak valid.";
        header("Location: pos_keamanan.php");
        exit;
    }

    // Validasi pengajuan pass
    $stmt_p = $conn->prepare("SELECT * FROM pengajuan_pass WHERE id_pass = ?");
    $stmt_p->bind_param("i", $id_pass);
    $stmt_p->execute();
    $pass_row = $stmt_p->get_result()->fetch_assoc();
    $stmt_p->close();

    if (!$pass_row) {
        $_SESSION['flash_error'] = "Data Visitor Pass tidak ditemukan.";
        header("Location: pos_keamanan.php");
        exit;
    }

    if ($pass_row['status_approval'] !== 'Approved') {
        $_SESSION['flash_error'] = "DITOLAK: Visitor Pass " . $pass_row['kode_pass'] . " berstatus " . $pass_row['status_approval'] . " (Belum disetujui).";
        header("Location: pos_keamanan.php?kode_pass=" . urlencode($pass_row['kode_pass']));
        exit;
    }

    // Cek log akses terakhir untuk id_pass ini
    $stmt_l = $conn->prepare("SELECT * FROM log_akses WHERE id_pass = ? ORDER BY id_log DESC LIMIT 1");
    $stmt_l->bind_param("i", $id_pass);
    $stmt_l->execute();
    $existing_log = $stmt_l->get_result()->fetch_assoc();
    $stmt_l->close();

    if ($gate_action === 'checkin') {
        if ($existing_log) {
            $_SESSION['flash_error'] = "Visitor Pass " . $pass_row['kode_pass'] . " sudah pernah melakukan Check-in sebelumnya.";
        } else {
            $status_masuk = 'Di Dalam Gedung';
            $stmt_in = $conn->prepare("INSERT INTO log_akses (id_pass, id_petugas, waktu_checkin, status_pass) VALUES (?, ?, NOW(), ?)");
            $stmt_in->bind_param("iis", $id_pass, $id_petugas, $status_masuk);
            if ($stmt_in->execute()) {
                $_SESSION['flash_success'] = "CHECK-IN BERHASIL: Tamu " . $pass_row['nama_tamu'] . " (" . $pass_row['kode_pass'] . ") dicatat masuk gedung pada " . date('H:i:s') . ".";
            } else {
                $_SESSION['flash_error'] = "Gagal mencatat waktu check-in.";
            }
            $stmt_in->close();
        }
    } elseif ($gate_action === 'checkout') {
        if (!$existing_log || $existing_log['status_pass'] !== 'Di Dalam Gedung') {
            $_SESSION['flash_error'] = "Tamu tidak dalam status berada di dalam gedung, tidak dapat melakukan Check-out.";
        } else {
            $status_keluar = 'Kedaluwarsa';
            $id_log = (int)$existing_log['id_log'];
            $stmt_out = $conn->prepare("UPDATE log_akses SET waktu_checkout = NOW(), status_pass = ?, id_petugas = ? WHERE id_log = ?");
            $stmt_out->bind_param("sii", $status_keluar, $id_petugas, $id_log);
            if ($stmt_out->execute()) {
                $_SESSION['flash_success'] = "CHECK-OUT BERHASIL: Tamu " . $pass_row['nama_tamu'] . " (" . $pass_row['kode_pass'] . ") dicatat keluar gedung pada " . date('H:i:s') . ".";
            } else {
                $_SESSION['flash_error'] = "Gagal mencatat waktu check-out.";
            }
            $stmt_out->close();
        }
    }

    header("Location: pos_keamanan.php?kode_pass=" . urlencode($kode_redirect));
    exit;
}

// Pencarian kode_pass
$kode_cari = trim($_GET['kode_pass'] ?? '');
$found_pass = null;
$found_log = null;
$cari_msg = '';

if ($kode_cari !== '') {
    $stmt = $conn->prepare("
        SELECT p.*, u.nama AS nama_pegawai, u.divisi AS divisi_pegawai
        FROM pengajuan_pass p
        JOIN users u ON p.id_tujuan_pegawai = u.id_user
        WHERE p.kode_pass = ?
    ");
    $stmt->bind_param("s", $kode_cari);
    $stmt->execute();
    $found_pass = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$found_pass) {
        $cari_msg = "Kode PASS '" . $kode_cari . "' tidak terdaftar di dalam sistem.";
    } else {
        $stmt_log = $conn->prepare("
            SELECT l.*, s.nama AS nama_petugas
            FROM log_akses l
            JOIN users s ON l.id_petugas = s.id_user
            WHERE l.id_pass = ?
            ORDER BY l.id_log DESC LIMIT 1
        ");
        $stmt_log->bind_param("i", $found_pass['id_pass']);
        $stmt_log->execute();
        $found_log = $stmt_log->get_result()->fetch_assoc();
        $stmt_log->close();
    }
}

// Ambil daftar antrean pass yang sudah Approved hari ini / aktif untuk memudahkan petugas pos
$queue_list = [];
$res_q = $conn->query("
    SELECT p.kode_pass, p.nama_tamu, p.instansi, p.tgl_rencana, u.nama AS nama_pegawai,
           l.status_pass, l.waktu_checkin
    FROM pengajuan_pass p
    JOIN users u ON p.id_tujuan_pegawai = u.id_user
    LEFT JOIN log_akses l ON p.id_pass = l.id_pass
    WHERE p.status_approval = 'Approved'
      AND (l.status_pass IS NULL OR l.status_pass = 'Di Dalam Gedung')
    ORDER BY p.tgl_rencana ASC, p.id_pass DESC
");
if ($res_q) {
    $queue_list = $res_q->fetch_all(MYSQLI_ASSOC);
}

include 'includes/header.php';
?>

<h2>Pos Keamanan (Gate Check-In &amp; Check-Out Real-Time)</h2>
<p>Masukkan atau pindai <strong>Kode PASS</strong> tamu pada gerbang masuk/keluar untuk mencatat waktu akses gedung secara real-time.</p>

<form method="GET" action="pos_keamanan.php">
    <fieldset>
        <legend><strong>Pencarian Kode PASS Tamu</strong></legend>
        <label for="kode_pass"><strong>Kode PASS:</strong></label>
        <input type="text" id="kode_pass" name="kode_pass" value="<?php echo e($kode_cari); ?>" required minlength="5" maxlength="30" size="30" placeholder="Contoh: PASS-20261112-1001" autofocus>
        <button type="submit">Cari Visitor Pass</button>
        <?php if ($kode_cari !== ''): ?>
            <a href="pos_keamanan.php">Reset Pencarian</a>
        <?php endif; ?>
    </fieldset>
</form>

<br>

<?php if ($cari_msg !== ''): ?>
    <fieldset>
        <legend><strong>Hasil Pencarian Gate</strong></legend>
        <p><?php echo e($cari_msg); ?></p>
    </fieldset>
    <br>
<?php endif; ?>

<?php if ($found_pass): ?>
    <fieldset>
        <legend><strong>Verifikasi Identitas &amp; Kontrol Gerbang (Gate Control)</strong></legend>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th align="left" width="30%">Kode PASS</th>
                <td><strong><?php echo e($found_pass['kode_pass']); ?></strong></td>
            </tr>
            <tr>
                <th align="left">Nama Tamu</th>
                <td><strong><?php echo e($found_pass['nama_tamu']); ?></strong> (No. ID: <?php echo e($found_pass['no_identitas']); ?>)</td>
            </tr>
            <tr>
                <th align="left">Asal Instansi</th>
                <td><?php echo e($found_pass['instansi']); ?></td>
            </tr>
            <tr>
                <th align="left">Pegawai Tujuan</th>
                <td><?php echo e($found_pass['nama_pegawai'] . ' - ' . $found_pass['divisi_pegawai']); ?></td>
            </tr>
            <tr>
                <th align="left">Tanggal Rencana</th>
                <td><?php echo e(date('d-m-Y', strtotime($found_pass['tgl_rencana']))); ?></td>
            </tr>
            <tr>
                <th align="left">Keperluan</th>
                <td><?php echo e($found_pass['keperluan']); ?></td>
            </tr>
            <tr>
                <th align="left">Status Persetujuan</th>
                <td><strong>[ <?php echo e(strtoupper($found_pass['status_approval'])); ?> ]</strong></td>
            </tr>
            <tr>
                <th align="left">Status Keberadaan Gedung</th>
                <td>
                    <?php if (!$found_log): ?>
                        <strong>BELUM MASUK GEDUNG</strong>
                    <?php else: ?>
                        <strong><?php echo e(strtoupper($found_log['status_pass'])); ?></strong><br>
                        Waktu Check-In: <?php echo e($found_log['waktu_checkin']); ?><br>
                        Waktu Check-Out: <?php echo $found_log['waktu_checkout'] ? e($found_log['waktu_checkout']) : '-'; ?><br>
                        Petugas Terakhir: <?php echo e($found_log['nama_petugas']); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th align="left">Aksi Pos Keamanan</th>
                <td>
                    <?php if ($found_pass['status_approval'] !== 'Approved'): ?>
                        <strong>AKSES DITOLAK!</strong> Pengajuan kunjungan berstatus <em><?php echo e($found_pass['status_approval']); ?></em>. Tamu tidak diperbolehkan masuk.
                    <?php elseif (!$found_log): ?>
                        <form method="POST" action="pos_keamanan.php" onsubmit="return confirm('Catat CHECK-IN masuk gedung untuk tamu <?php echo e(addslashes($found_pass['nama_tamu'])); ?>?');">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <input type="hidden" name="id_pass" value="<?php echo (int)$found_pass['id_pass']; ?>">
                            <input type="hidden" name="kode_pass" value="<?php echo e($found_pass['kode_pass']); ?>">
                            <button type="submit" name="gate_action" value="checkin">CATAT CHECK-IN (TAMU MASUK GEDUNG)</button>
                        </form>
                    <?php elseif ($found_log['status_pass'] === 'Di Dalam Gedung'): ?>
                        <form method="POST" action="pos_keamanan.php" onsubmit="return confirm('Catat CHECK-OUT keluar gedung untuk tamu <?php echo e(addslashes($found_pass['nama_tamu'])); ?>?');">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <input type="hidden" name="id_pass" value="<?php echo (int)$found_pass['id_pass']; ?>">
                            <input type="hidden" name="kode_pass" value="<?php echo e($found_pass['kode_pass']); ?>">
                            <button type="submit" name="gate_action" value="checkout">CATAT CHECK-OUT (TAMU KELUAR GEDUNG)</button>
                        </form>
                    <?php else: ?>
                        <strong>KUNJUNGAN SELESAI.</strong> Tamu sudah melakukan Check-Out dan kartu PASS ini telah nonaktif.
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </fieldset>
    <br>
<?php endif; ?>

<fieldset>
    <legend><strong>Daftar Visitor Pass Siap Check-In &amp; Tamu Aktif di Gedung</strong></legend>
    <?php if (empty($queue_list)): ?>
        <p>Tidak ada tamu yang sedang menunggu check-in atau berada di dalam gedung.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode PASS</th>
                    <th>Nama Tamu</th>
                    <th>Instansi</th>
                    <th>Pegawai Tujuan</th>
                    <th>Tanggal Rencana</th>
                    <th>Status Gate</th>
                    <th>Pilih</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($queue_list as $q): ?>
                    <tr>
                        <td align="center"><?php echo $no++; ?></td>
                        <td><strong><?php echo e($q['kode_pass']); ?></strong></td>
                        <td><?php echo e($q['nama_tamu']); ?></td>
                        <td><?php echo e($q['instansi']); ?></td>
                        <td><?php echo e($q['nama_pegawai']); ?></td>
                        <td><?php echo e(date('d-m-Y', strtotime($q['tgl_rencana']))); ?></td>
                        <td>
                            <?php if ($q['status_pass'] === 'Di Dalam Gedung'): ?>
                                <strong>Di Dalam Gedung</strong> (Masuk: <?php echo e($q['waktu_checkin']); ?>)
                            <?php else: ?>
                                Siap Check-In (Approved)
                            <?php endif; ?>
                        </td>
                        <td align="center">
                            <a href="pos_keamanan.php?kode_pass=<?php echo urlencode($q['kode_pass']); ?>">Buka di Gate</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</fieldset>

<?php include 'includes/footer.php'; ?>