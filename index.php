<?php
require_once(__DIR__ . '/inc/session_config.php');
session_start();
if (empty($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

switch ($_SESSION['role']) {
    case 'admin':
        header('Location: admin/index.php');
        break;
    case 'resepsionis':
        header('Location: resepsionis/index.php');
        break;
    case 'keamanan':
        header('Location: keamanan/index.php');
        break;
    default:
        header('Location: login.php');
}
exit();
?>
