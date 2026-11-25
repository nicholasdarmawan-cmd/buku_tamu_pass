<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$page_title = "Login Petugas & Pegawai - Buku Tamu PASS";
$error_msg = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "Sesi formulir tidak valid. Silakan coba lagi.";
    } else {
        $username_input = trim($_POST['username'] ?? '');
        $password_input = $_POST['password'] ?? '';

        if ($username_input === '' || $password_input === '') {
            $error_msg = "Username dan password wajib diisi.";
        } else {
            $stmt = $conn->prepare("SELECT id_user, nama, divisi, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username_input);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password_input, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['id_user'] = (int)$user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['divisi'] = $user['divisi'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                $_SESSION['flash_success'] = "Selamat datang kembali, " . $user['nama'] . " (" . strtoupper($user['role']) . ").";
                header("Location: dashboard.php");
                exit;
            } else {
                $error_msg = "Username atau kata sandi salah.";
            }
        }
    }
}

include 'includes/header.php';
?>

<h2>Halaman Login Petugas / Pegawai</h2>
<p>Masuk ke sistem internal sesuai hak akses Anda (<strong>Admin</strong>, <strong>Pegawai Tujuan</strong>, atau <strong>Petugas Keamanan</strong>).</p>

<?php if ($error_msg !== ''): ?>
    <fieldset>
        <legend><strong>Login Gagal</strong></legend>
        <p><?php echo e($error_msg); ?></p>
    </fieldset>
    <br>
<?php endif; ?>

<form method="POST" action="login.php">
    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
    <fieldset>
        <legend><strong>Autentikasi Pengguna Internal</strong></legend>
        <table border="0" cellpadding="6" cellspacing="0">
            <tr>
                <td><label for="username"><strong>Username *</strong></label></td>
                <td><input type="text" id="username" name="username" value="<?php echo e($username_input); ?>" required minlength="3" maxlength="50" size="30" autocomplete="username"></td>
            </tr>
            <tr>
                <td><label for="password"><strong>Password *</strong></label></td>
                <td><input type="password" id="password" name="password" required minlength="4" size="30" autocomplete="current-password"></td>
            </tr>
            <tr>
                <td></td>
                <td><button type="submit">Masuk (Login)</button></td>
            </tr>
        </table>
    </fieldset>
</form>

<br>

<fieldset>
    <legend><strong>Informasi Akun Pengujian (Default Seed)</strong></legend>
    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Role / Hak Akses</th>
                <th>Nama Pengguna</th>
                <th>Username</th>
                <th>Password</th>
                <th>Otoritas Modul</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Admin</strong></td>
                <td>Administrator Sistem</td>
                <td><code>admin</code></td>
                <td><code>admin123</code></td>
                <td>Semua Modul (Dashboard, Approval, Pos Keamanan, Log Riwayat, Kelola Akun)</td>
            </tr>
            <tr>
                <td><strong>Pegawai Tujuan</strong></td>
                <td>Budi Santoso, S.Kom.</td>
                <td><code>budi</code></td>
                <td><code>pegawai123</code></td>
                <td>Dashboard Keamanan, Approval Izin Masuk (Tamu Tujuan), Log Riwayat Akses</td>
            </tr>
            <tr>
                <td><strong>Pegawai Tujuan</strong></td>
                <td>Sari Rahmawati, S.E.</td>
                <td><code>sari</code></td>
                <td><code>pegawai123</code></td>
                <td>Dashboard Keamanan, Approval Izin Masuk (Tamu Tujuan), Log Riwayat Akses</td>
            </tr>
            <tr>
                <td><strong>Petugas Keamanan</strong></td>
                <td>Hendro Wijaya</td>
                <td><code>satpam1</code></td>
                <td><code>satpam123</code></td>
                <td>Dashboard Keamanan, Pos Keamanan (Gate Check-in &amp; Check-out), Log Riwayat Akses</td>
            </tr>
        </tbody>
    </table>
</fieldset>

<?php include 'includes/footer.php'; ?>
