<?php
require_once __DIR__ . '/../../inc/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('error', 'Invalid logout request.');
        redirect('../../views/dashboard.php');
    }

    // Destroy session and JWT
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');

    redirect('../../views/login.php');
    exit;
}
?>
