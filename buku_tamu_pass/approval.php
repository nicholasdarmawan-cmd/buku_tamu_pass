<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

// Hanya Admin dan Pegawai Tujuan yang berhak menyetujui/menolak kunjungan
require_role(['admin', 'pegawai']);

$page_title = "Approval Izin Masuk - Buku Tamu PASS";
$current_user_id = (int)$_SESSION['id_user'];
$current_role = $_SESSION['role'];

// Proses Approval / Reject via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Token keamanan tidak valid.";
        header("Location: approval.php");
        exit;
    }

    $id_pass = filter_input(INPUT_POST, 'id_pass', FILTER_VALIDATE_INT);
    $aksi = $_POST['aksi'] ?? '';

    if (!$id_pass || !in_array($aksi, ['Approved', 'Rejected'], true)) {
        $_SESSION['flash_error'] = "Permintaan aksi persetujuan tidak valid.";
        header("Location: approval.php");
        exit;
    }

    // Cek kepemilikan pengajuan (Pegawai hanya bisa approve tamu yang ditujukan kepadanya; Admin bisa semua)
    if ($current_role === 'pegawai') {
        $stmt_cek = $conn->prepare("SELECT id_pass, kode_pass, status_approval FROM pengajuan_pass WHERE id_pass = ? AND id_tujuan_pegawai = ?");
        $stmt_cek->bind_param("ii", $id_pass, $current_user_id);
    } else {
        $stmt_cek = $conn->prepare("SELECT id_pass, kode_pass, status_approval FROM pengajuan_pass WHERE id_pass = ?");
        $stmt_cek->bind_param("i", $id_pass);
    }

    $stmt_cek->execute();
    $target = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if (!$target) {
        $_SESSION['flash_error'] = "Data pengajuan tidak ditemukan atau bukan wewenang Anda.";
        header("Location: approval.php");
        exit;
    }

    $stmt_upd = $conn->prepare("UPDATE pengajuan_pass SET status_approval = ? WHERE id_pass = ?");
    $stmt_upd->bind_param("si", $aksi, $id_pass);
    if ($stmt_upd->execute()) {
        $label_aksi = ($aksi === 'Approved') ? 'DISETUJUI (Approved)' : 'DITOLAK (Rejected)';
        $_SESSION['flash_success'] = "Pengajuan dengan kode " . $target['kode_pass'] . " berhasil diperbarui menjadi " . $label_aksi . ".";
    } else {
        $_SESSION['flash_error'] = "Gagal memperbarui status pengajuan.";
    }
    $stmt_upd->close();

    header("Location: approval.php");
    exit;
}

// Filter status tampilan
$filter_status = $_GET['status'] ?? 'Semua';
if (!in_array($filter_status, ['Semua', 'Pending', 'Approved', 'Rejected'], true)) {
    $filter_status = 'Semua';
}

$sql = "
    SELECT p.*, u.nama AS nama_pegawai, u.divisi AS divisi_pegawai
    FROM pengajuan_pass p
    JOIN users u ON p.id_tujuan_pegawai = u.id_user
    WHERE 1=1
";
$params = [];
$types = "";

if ($current_role === 'pegawai') {
    $sql .= " AND p.id_tujuan_pegawai = ?";
    $params[] = $current_user_id;
    $types .= "i";
}

if ($filter_status !== 'Semua') {
    $sql .= " AND p.status_approval = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY FIELD(p.status_approval, 'Pending', 'Approved', 'Rejected'), p.tgl_rencana ASC, p.id_pass DESC";

$stmt_list = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_list->bind_param($types, ...$params);
}
$stmt_list->execute();
$list_pengajuan = $stmt_list->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_list->close();

include 'includes/header.php';
?>

<h2>Halaman Approval Izin Masuk Tamu</h2>
<p>
    <?php if ($current_role === 'pegawai'): ?>
        Menampilkan daftar permohonan kunjungan yang ditujukan khusus kepada Anda (<strong><?php echo e($_SESSION['nama']); ?></strong>).
    <?php else: ?>
        Menampilkan seluruh daftar permohonan kunjungan gedung (Mode Administrator).
    <?php endif; ?>
</p>

<form method="GET" action="approval.php">
    <fieldset>
        <legend><strong>Filter Status Persetujuan</strong></legend>
        <label for="status"><strong>Status Approval:</strong></label>
        <select id="status" name="status">
            <?php foreach (['Semua', 'Pending', 'Approved', 'Rejected'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo ($filter_status === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Tampilkan</button>
    </fieldset>
</form>

<br>

<fieldset>
    <legend><strong>Daftar Permohonan Kunjungan (<?php echo count($list_pengajuan); ?> Data)</strong></legend>
    <?php if (empty($list_pengajuan)): ?>
        <p>Tidak ada data permohonan kunjungan untuk filter yang dipilih.</p>
    <?php else: ?>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode PASS</th>
                    <th>Tanggal Rencana</th>
                    <th>Nama Tamu &amp; Identitas</th>
                    <th>Instansi</th>
                    <th>Pegawai Tujuan</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                    <th>Tindakan (Approval)</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($list_pengajuan as $row): ?>
                    <tr>
                        <td align="center"><?php echo $no++; ?></td>
                        <td><strong><?php echo e($row['kode_pass']); ?></strong></td>
                        <td><?php echo e(date('d-m-Y', strtotime($row['tgl_rencana']))); ?></td>
                        <td>
                            <strong><?php echo e($row['nama_tamu']); ?></strong><br>
                            <small>ID: <?php echo e($row['no_identitas']); ?></small>
                        </td>
                        <td><?php echo e($row['instansi']); ?></td>
                        <td><?php echo e($row['nama_pegawai'] . ' (' . $row['divisi_pegawai'] . ')'); ?></td>
                        <td><?php echo e($row['keperluan']); ?></td>
                        <td align="center"><strong><?php echo e(strtoupper($row['status_approval'])); ?></strong></td>
                        <td align="center">
                            <?php if ($row['status_approval'] === 'Pending'): ?>
                                <form method="POST" action="approval.php" onsubmit="return confirm('Konfirmasi tindakan persetujuan untuk tamu <?php echo e(addslashes($row['nama_tamu'])); ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                    <input type="hidden" name="id_pass" value="<?php echo (int)$row['id_pass']; ?>">
                                    <button type="submit" name="aksi" value="Approved">Approve (Setujui)</button>
                                    <button type="submit" name="aksi" value="Rejected">Reject (Tolak)</button>
                                </form>
                            <?php else: ?>
                                <small>Sudah diproses</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</fieldset>

<?php include 'includes/footer.php'; ?>
