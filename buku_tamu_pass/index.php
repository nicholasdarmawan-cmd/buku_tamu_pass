<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

$page_title = "Pengajuan Janji Kunjungan - Buku Tamu PASS";
$errors = [];
$generated_pass = null;

// Ambil daftar pegawai tujuan dari tabel users (role pegawai atau admin)
$pegawai_list = [];
$res_pegawai = $conn->query("SELECT id_user, nama, divisi FROM users WHERE role IN ('pegawai', 'admin') ORDER BY nama ASC");
if ($res_pegawai) {
    while ($row = $res_pegawai->fetch_assoc()) {
        $pegawai_list[] = $row;
    }
}

$old = [
    'nama_tamu' => '',
    'no_identitas' => '',
    'instansi' => '',
    'id_tujuan_pegawai' => '',
    'tgl_rencana' => date('Y-m-d'),
    'keperluan' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token keamanan form tidak valid. Silakan muat ulang halaman.";
    }

    $old['nama_tamu'] = trim($_POST['nama_tamu'] ?? '');
    $old['no_identitas'] = trim($_POST['no_identitas'] ?? '');
    $old['instansi'] = trim($_POST['instansi'] ?? '');
    $old['id_tujuan_pegawai'] = trim($_POST['id_tujuan_pegawai'] ?? '');
    $old['tgl_rencana'] = trim($_POST['tgl_rencana'] ?? '');
    $old['keperluan'] = trim($_POST['keperluan'] ?? '');

    // Validasi Server-Side
    if ($old['nama_tamu'] === '' || mb_strlen($old['nama_tamu']) < 3 || mb_strlen($old['nama_tamu']) > 100) {
        $errors[] = "Nama tamu wajib diisi (3 sampai 100 karakter).";
    }
    if ($old['no_identitas'] === '' || !preg_match('/^[A-Za-z0-9\-]{5,30}$/', $old['no_identitas'])) {
        $errors[] = "Nomor identitas (KTP/SIM/Paspor) wajib diisi (5-30 karakter huruf/angka).";
    }
    if ($old['instansi'] === '' || mb_strlen($old['instansi']) < 2 || mb_strlen($old['instansi']) > 100) {
        $errors[] = "Asal instansi/perusahaan wajib diisi (2 sampai 100 karakter).";
    }

    $id_pegawai_int = filter_var($old['id_tujuan_pegawai'], FILTER_VALIDATE_INT);
    if (!$id_pegawai_int) {
        $errors[] = "Silakan pilih pegawai tujuan yang valid.";
    } else {
        $cek_peg = $conn->prepare("SELECT id_user FROM users WHERE id_user = ? AND role IN ('pegawai', 'admin')");
        $cek_peg->bind_param("i", $id_pegawai_int);
        $cek_peg->execute();
        $cek_peg->store_result();
        if ($cek_peg->num_rows === 0) {
            $errors[] = "Pegawai tujuan tidak ditemukan di dalam sistem.";
        }
        $cek_peg->close();
    }

    $date_obj = DateTime::createFromFormat('Y-m-d', $old['tgl_rencana']);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $old['tgl_rencana']) {
        $errors[] = "Format tanggal rencana kunjungan tidak valid.";
    } elseif ($old['tgl_rencana'] < date('Y-m-d')) {
        $errors[] = "Tanggal rencana kunjungan tidak boleh kurang dari hari ini (" . date('d-m-Y') . ").";
    }

    if ($old['keperluan'] === '' || mb_strlen($old['keperluan']) < 5) {
        $errors[] = "Keperluan kunjungan wajib diisi minimal 5 karakter.";
    }

    if (empty($errors)) {
        $kode_pass = generate_kode_pass($conn);
        $status_default = 'Pending';

        $stmt = $conn->prepare("INSERT INTO pengajuan_pass (kode_pass, nama_tamu, no_identitas, instansi, id_tujuan_pegawai, keperluan, tgl_rencana, status_approval) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssisss",
            $kode_pass,
            $old['nama_tamu'],
            $old['no_identitas'],
            $old['instansi'],
            $id_pegawai_int,
            $old['keperluan'],
            $old['tgl_rencana'],
            $status_default
        );

        if ($stmt->execute()) {
            $generated_pass = [
                'kode_pass' => $kode_pass,
                'nama_tamu' => $old['nama_tamu'],
                'tgl_rencana' => $old['tgl_rencana']
            ];
            $old = [
                'nama_tamu' => '',
                'no_identitas' => '',
                'instansi' => '',
                'id_tujuan_pegawai' => '',
                'tgl_rencana' => date('Y-m-d'),
                'keperluan' => ''
            ];
        } else {
            $errors[] = "Gagal menyimpan data pengajuan kunjungan: " . $stmt->error;
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<h2>Halaman Pengajuan Janji Kunjungan (Visitor Pre-Registration)</h2>
<p>Silakan lengkapi formulir di bawah ini untuk mengajukan permohonan kunjungan gedung. Simpan <strong>Kode Referensi PASS</strong> setelah pendaftaran berhasil.</p>

<?php if (!empty($errors)): ?>
    <fieldset>
        <legend><strong>Gagal Mengajukan Kunjungan</strong></legend>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </fieldset>
    <br>
<?php endif; ?>

<?php if ($generated_pass !== null): ?>
    <fieldset>
        <legend><strong>PENGAJUAN JANJI KUNJUNGAN BERHASIL TERDAFTAR</strong></legend>
        <p>Permohonan kunjungan atas nama <strong><?php echo e($generated_pass['nama_tamu']); ?></strong> pada tanggal <strong><?php echo e($generated_pass['tgl_rencana']); ?></strong> telah diterima dengan status <strong>PENDING</strong>.</p>
        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>KODE REFERENSI VISITOR PASS ANDA</th>
            </tr>
            <tr>
                <td align="center">
                    <h2><?php echo e($generated_pass['kode_pass']); ?></h2>
                </td>
            </tr>
        </table>
        <p>Harap catat atau salin kode referensi di atas untuk memantau persetujuan dan mencetak Kartu Visitor Pass Anda.</p>
        <p><a href="cek_status.php?kode=<?php echo urlencode($generated_pass['kode_pass']); ?>"><strong>&raquo; Klik di sini untuk Cek Status &amp; Cetak Visitor Pass</strong></a></p>
    </fieldset>
    <br>
<?php endif; ?>

<form method="POST" action="index.php">
    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
    <fieldset>
        <legend><strong>Formulir Permohonan Kunjungan Tamu</strong></legend>
        <table border="0" cellpadding="6" cellspacing="0" width="100%">
            <tr>
                <td width="25%"><label for="nama_tamu"><strong>Nama Lengkap Tamu *</strong></label></td>
                <td>
                    <input type="text" id="nama_tamu" name="nama_tamu" value="<?php echo e($old['nama_tamu']); ?>" required minlength="3" maxlength="100" size="45" placeholder="Contoh: Andi Wijaya">
                </td>
            </tr>
            <tr>
                <td><label for="no_identitas"><strong>No. Identitas (NIK / SIM / Paspor) *</strong></label></td>
                <td>
                    <input type="text" id="no_identitas" name="no_identitas" value="<?php echo e($old['no_identitas']); ?>" required minlength="5" maxlength="30" pattern="[A-Za-z0-9\-]{5,30}" size="45" placeholder="Contoh: 3275011205900001">
                </td>
            </tr>
            <tr>
                <td><label for="instansi"><strong>Asal Instansi / Perusahaan *</strong></label></td>
                <td>
                    <input type="text" id="instansi" name="instansi" value="<?php echo e($old['instansi']); ?>" required minlength="2" maxlength="100" size="45" placeholder="Contoh: PT Maju Bersama">
                </td>
            </tr>
            <tr>
                <td><label for="id_tujuan_pegawai"><strong>Pegawai / Divisi Tujuan *</strong></label></td>
                <td>
                    <select id="id_tujuan_pegawai" name="id_tujuan_pegawai" required>
                        <option value="">-- Pilih Pegawai Tujuan --</option>
                        <?php foreach ($pegawai_list as $peg): ?>
                            <option value="<?php echo (int)$peg['id_user']; ?>" <?php echo ($old['id_tujuan_pegawai'] == $peg['id_user']) ? 'selected' : ''; ?>>
                                <?php echo e($peg['nama'] . ' (' . $peg['divisi'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="tgl_rencana"><strong>Tanggal Rencana Kunjungan *</strong></label></td>
                <td>
                    <input type="date" id="tgl_rencana" name="tgl_rencana" value="<?php echo e($old['tgl_rencana']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </td>
            </tr>
            <tr>
                <td valign="top"><label for="keperluan"><strong>Keperluan Kunjungan *</strong></label></td>
                <td>
                    <textarea id="keperluan" name="keperluan" rows="4" cols="47" required minlength="5" placeholder="Jelaskan maksud dan tujuan kunjungan Anda secara singkat..."><?php echo e($old['keperluan']); ?></textarea>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <button type="submit">Kirim Pengajuan Kunjungan</button>
                    <button type="reset">Reset Form</button>
                </td>
            </tr>
        </table>
    </fieldset>
</form>

<?php include 'includes/footer.php'; ?>
