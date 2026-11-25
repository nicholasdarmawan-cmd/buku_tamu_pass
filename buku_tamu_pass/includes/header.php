<?php
if (!isset($page_title)) {
    $page_title = "Buku Tamu PASS - Enterprise Visitor Management";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>SISTEM BUKU TAMU PASS (ENTERPRISE VISITOR MANAGEMENT)</h1>
        <p>Portal Manajemen Kunjungan &amp; Keamanan Akses Gedung</p>
        <nav aria-label="Menu Utama">
            <table border="1" cellpadding="6" cellspacing="0" width="100%">
                <tr>
                    <td>
                        <strong>Portal Tamu (Publik):</strong>
                        <a href="index.php">Pengajuan Janji Kunjungan</a> |
                        <a href="cek_status.php">Cek Status &amp; Cetak Visitor Pass</a>
                    </td>
                    <td align="right">
                        <?php if (is_logged_in()): ?>
                            Login sebagai: <strong><?php echo e($_SESSION['nama']); ?></strong>
                            (Role: <strong><?php echo e(strtoupper($_SESSION['role'])); ?></strong>) |
                            <a href="logout.php" onclick="return confirm('Yakin ingin keluar dari sistem?');">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login Petugas / Pegawai</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (is_logged_in()): ?>
                <tr>
                    <td colspan="2">
                        <strong>Menu Internal Petugas:</strong>
                        <a href="dashboard.php">Dashboard Keamanan</a>
                        <?php if (in_array($_SESSION['role'], ['admin', 'pegawai'], true)): ?>
                            | <a href="approval.php">Approval Izin Masuk</a>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['role'], ['admin', 'keamanan'], true)): ?>
                            | <a href="pos_keamanan.php">Pos Keamanan (Check-in &amp; Check-out)</a>
                        <?php endif; ?>
                        | <a href="log_riwayat.php">Log Riwayat Akses Gedung</a>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            | <a href="kelola_users.php">Kelola Akun Pengguna</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </nav>
    </header>
    <hr>
    <main>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <fieldset>
                <legend><strong>INFORMASI BERHASIL</strong></legend>
                <p><?php echo e($_SESSION['flash_success']); ?></p>
            </fieldset>
            <br>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <fieldset>
                <legend><strong>PERINGATAN / KESALAHAN</strong></legend>
                <p><?php echo e($_SESSION['flash_error']); ?></p>
            </fieldset>
            <br>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>
