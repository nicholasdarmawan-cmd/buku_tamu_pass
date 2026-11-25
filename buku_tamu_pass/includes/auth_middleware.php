<?php
// Middleware Autentikasi, Role-Based Access Control (RBAC), CSRF & Sanitasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape output untuk mencegah Cross-Site Scripting (XSS)
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Menghasilkan token CSRF untuk form POST
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Memverifikasi token CSRF pada setiap request POST
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Mengecek apakah pengguna sudah login
 */
function is_logged_in() {
    return isset($_SESSION['id_user']) && isset($_SESSION['role']);
}

/**
 * Middleware wajib login
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Silakan login terlebih dahulu untuk mengakses halaman tersebut.";
        header("Location: login.php");
        exit;
    }
}

/**
 * Middleware Pembatasan Hak Akses (Role-Based Access Control)
 * Mencegah akses halaman di luar otoritas melalui manipulasi URL
 */
function require_role(array $allowed_roles) {
    require_login();
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        http_response_code(403);
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>403 Akses Ditolak</title></head><body>";
        echo "<h1>403 - Akses Ditolak (Otoritas Tidak Mencukupi)</h1>";
        echo "<p>Maaf, peran akun Anda (<strong>" . e(strtoupper($_SESSION['role'])) . "</strong>) tidak memiliki hak akses ke halaman ini.</p>";
        echo "<p><a href='dashboard.php'>Kembali ke Dashboard</a> | <a href='logout.php'>Logout</a></p>";
        echo "</body></html>";
        exit;
    }
}

/**
 * Membuat kode pass unik
 */
function generate_kode_pass($conn) {
    do {
        $kode = 'PASS-' . date('Ymd') . '-' . random_int(1000, 9999);
        $stmt = $conn->prepare("SELECT id_pass FROM pengajuan_pass WHERE kode_pass = ?");
        $stmt->bind_param("s", $kode);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
    } while ($exists);

    return $kode;
}
?>
