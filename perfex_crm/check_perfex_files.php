<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once('application/config/database.php');
$mysqli = new mysqli(
    $db['default']['hostname'],
    $db['default']['username'],
    $db['default']['password'],
    $db['default']['database']
);

if ($mysqli->connect_error) {
    die("Error: " . $mysqli->connect_error);
}

echo "=== 1. ESTRUCTURA DE tblfiles ===\n\n";
$result = $mysqli->query("DESCRIBE tblfiles");
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-30s | %-20s | %s\n", $row['Field'], $row['Type'], $row['Null']);
}

echo "\n\n=== 2. TABLAS DE DISCUSSIONS ===\n\n";
$tables = $mysqli->query("SHOW TABLES LIKE '%discussion%'");
while ($table = $tables->fetch_array()) {
    echo "\n--- Tabla: " . $table[0] . " ---\n";
    $columns = $mysqli->query("DESCRIBE " . $table[0]);
    while ($col = $columns->fetch_assoc()) {
        echo sprintf("  %-30s | %s\n", $col['Field'], $col['Type']);
    }
}

echo "\n\n=== 3. ARCHIVOS DEL PROYECTO 8 ===\n\n";
$files = $mysqli->query("SELECT * FROM tblfiles WHERE rel_type = 'project' AND relid = 8 ORDER BY dateadded DESC");
if ($files->num_rows > 0) {
    while ($file = $files->fetch_assoc()) {
        echo "\n--- Archivo ID: " . $file['id'] . " ---\n";
        foreach ($file as $key => $value) {
            echo sprintf("  %-30s: %s\n", $key, $value ?: '(null)');
        }
    }
} else {
    echo "No hay archivos\n";
}

echo "\n\n=== 4. DISCUSSIONS DE ARCHIVOS ===\n\n";
$discussions = $mysqli->query("SELECT * FROM tblprojectdiscussions WHERE rel_type = 'file' ORDER BY id DESC LIMIT 3");
if ($discussions->num_rows > 0) {
    while ($disc = $discussions->fetch_assoc()) {
        echo "\n--- Discussion ID: " . $disc['id'] . " ---\n";
        foreach ($disc as $key => $value) {
            echo sprintf("  %-30s: %s\n", $key, $value ?: '(null)');
        }
    }
} else {
    echo "No hay discussions\n";
}

$mysqli->close();
?>
