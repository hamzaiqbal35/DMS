<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
require_once 'inc/config/auth.php';  
require_once 'inc/helpers.php';      

if (!isUserLoggedIn()) {
    header("Location: views/login.php");
    exit();
}
?>
