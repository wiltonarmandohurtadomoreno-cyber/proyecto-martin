<?php
// Configuración y utilidades comunes
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'sitio_sueno';
const DB_USER = 'root';      // cambia según tu servidor
const DB_PASS = '';          // cambia según tu servidor

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Muestra un mensaje y un enlace para volver
function mensaje(string $texto, string $volverUrl, string $volverTxt = 'Volver'): never {
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Aviso</title><link rel="stylesheet" href="../css/style.css"></head><body>'
       . '<main class="page"><div class="form-card"><h2>Aviso</h2><p>' . e($texto) . '</p>'
       . '<p><a href="' . e($volverUrl) . '">' . e($volverTxt) . '</a></p></div></main></body></html>';
    exit;
}

function requiere_login(): int {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ../pages/login.html');
        exit;
    }
    return (int) $_SESSION['usuario_id'];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_validar(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403);
        mensaje('Sesión inválida. Recarga la página e inténtalo de nuevo.', 'diario.php');
    }
}