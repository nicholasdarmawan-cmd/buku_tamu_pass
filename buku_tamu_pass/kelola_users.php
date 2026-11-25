<?php
require_once 'config/database.php';
require_once 'includes/auth_middleware.php';

// Hanya Admin yang berhak mengelola akun pengguna
require_role(['admin']);

$page_title = "Kelola Akun Pengguna - Buku Tamu PASS";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Token keamanan tidak valid.";
    } else {
        $nama = trim($_POST['nama'] ?? '');
        $divisi = trim($_POST['divisi'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if ($nama === '' || mb_strlen($nama) < 3) {
            $errors[] = "Nama lengkap minimal 3 karakter.";
        }
        if ($divisi === '') {
            $errors[] = "Divisi wajib diisi.";
        }
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = "Username wajib 3-30 karakter (huruf, angka, garis bawah).";
        }
        if (strlen($password) < 6) {
            $errors[] = "Password minimal 6 karakter.";
        }
        if (!in_array($role, ['admin', 'pegawai', 'keamanan'], true)) {
            $errors[] = "Role pengguna tidak valid.";
        }

        if (empty($errors)) {
            // Cek duplikasi username
            $cek = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
            $cek->bind_param("s", $username);
            $cek->execute();
            $cek->store_result();
            if ($cek->num_rows > 0) {
                $errors[] = "Username '" . $username . "' sudah digunakan.";
            }
            $cek->close();
        }

        if (empty($errors)) {
            // Enkripsi password dengan algoritma bcrypt (PASSWORD_BCRYPT)
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (nama, divisi, username, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nama, $divisi, $username, $hashed_password, $role);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Akun pengguna baru (" . $username . " - " . strtoupper($role) . ") berhasil ditambahkan.";
                $stmt->close();
                header("Location: kelola_users.php");
                exit;
            } else {
                $errors[] = "Gagal menyimpan akun pengguna.";
                $stmt->close();
            }
        }
    }
}

$users = $conn->query("SELECT id_user, nama, divisi, username, role FROM users ORDER BY role ASC, nama ASC")->fetch_all(MYSQLI_ASSOC);

include 'includes/header.php';
?>

<h2>Kelola Akun Pegawai &amp; Petugas (Administrator)</h2>
<p>Tambahkan akun baru untuk Pegawai Tujuan, Petugas Keamanan, atau Administrator. Seluruh kata sandi otomatis dienkripsi menggunakan <strong>bcrypt (password_hash)</strong>.</p>

<?php if (!empty($errors)): ?>
    <fieldset>
        <legend><strong>Gagal Menambahkan Akun</strong></legend>
        <ul>
            <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </fieldset>
    <br>
<?php endif; ?>

<form method="POST" action="kelola_users.php">
    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
    <fieldset>
        <legend><strong>Tambah Pengguna Baru</strong></legend>
        <table border="0" cellpadding="6" cellspacing="0">
            <tr>
                <td><label for="nama"><strong>Nama Lengkap *</strong></label></td>
                <td><input type="text" id="nama" name="nama" required minlength="3" maxlength="100" size="35"></td>
            </tr>
            <tr>
                <td><label for="divisi"><strong>Divisi / Bagian *</strong></label></td>
                <td><input type="text" id="divisi" name="divisi" required maxlength="100" size="35" placeholder="Contoh: Divisi SDM"></td>
            </tr>
            <tr>
                <td><label for="username"><strong>Username *</strong></label></td>
                <td><input type="text" id="username" name="username" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]{3,30}" size="35"></td>
            </tr>
            <tr>
                <td><label for="password"><strong>Password *</strong></label></td>
                <td><input type="password" id="password" name="password" required minlength="6" size="35" placeholder="Minimal 6 karakter"></td>
            </tr>
            <tr>
                <td><label for="role"><strong>Role / Hak Akses *</strong></label></td>
                <td>
                    <select id="role" name="role" required>
                        <option value="pegawai">Pegawai Tujuan (pegawai)</option>
                        <option value="keamanan">Petugas Keamanan (keamanan)</option>
                        <option value="admin">Administrator (admin)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><button type="submit">Simpan Akun Baru</button></td>
            </tr>
        </table>
    </fieldset>
</form>

<br>

<fieldset>
    <legend><strong>Daftar Akun Terdaftar (<?php echo count($users); ?> Pengguna)</strong></legend>
    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Lengkap</th>
                <th>Divisi</th>
                <th>Username</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td align="center"><?php echo (int)$u['id_user']; ?></td>
                    <td><?php echo e($u['nama']); ?></td>
                    <td><?php echo e($u['divisi']); ?></td>
                    <td><code><?php echo e($u['username']); ?></code></td>
                    <td align="center"><strong><?php echo e(strtoupper($u['role'])); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</fieldset>

<?php include 'includes/footer.php'; ?>
