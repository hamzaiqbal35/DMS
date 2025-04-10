<?php
session_start();
require_once 'inc/config/auth.php';  
require_once 'inc/helpers.php';      

if (!isUserLoggedIn()) {
    header("Location: views/login.php");
    exit();
}
?>
