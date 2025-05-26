<?php
session_start();

// Clear all session data
$_SESSION = [];
session_destroy();

// Redirect to index.php
header('Location: /views/index.php');
exit;
?>