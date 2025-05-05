<?php
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$databases = $conn->query("SHOW DATABASES");
echo "Available databases:<br>";
while ($db = $databases->fetch_array()) {
    echo $db[0] . "<br>";
}

if ($conn->select_db('cafe_db')) {
    echo "Successfully selected cafe_db";
} else {
    echo "Failed to select cafe_db: " . $conn->error;
}



$tables = $conn->query("SHOW TABLES IN cafe_db");
while ($table = $tables->fetch_array()) {
    echo "<br>";
    echo $table[0] . "";
}
?>