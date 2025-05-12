<?php
require_once __DIR__ . '/../../../config.php';
if ($conn) {
    echo "Connection successful";
} else {
    echo "Connection failed";
}
?>