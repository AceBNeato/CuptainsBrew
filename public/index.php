<?php
// public/index.php

$url = $_GET['url'] ?? 'home';

switch ($url) {
    case 'admin-menu':
        require_once '../controllers/admin-controller.php';
        showAdminMenu();
        break;

    case 'admin-orders':
        require_once '../controllers/admin-controller.php';
        showAdminOrders();
        break;

    case 'admin-reports':
        require_once '../controllers/admin-controller.php';
        showAdminReports();
        break;

    default:
        echo "404 Page Not Found";
        break;
}
