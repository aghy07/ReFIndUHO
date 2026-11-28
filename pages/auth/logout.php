<?php
/**
 * pages/auth/logout.php — ReFindUHO
 * Proses logout pengguna.
 */

require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$authController = new AuthController($pdo);
$authController->logout();
