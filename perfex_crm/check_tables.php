<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once('application/config/database.php');

$mysqli = new mysqli(
    $db['default']['hostname'],
    $db['default']['username'],
    $db['default']['password'],
    $db['default']['database']
);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificar Tablas</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; }
        h2 { color: #4ec9b0; }
    </style>
</head>
<body>
    <h1>🔍 Verificación de Tablas y Datos</h1>

    <h2>1. ¿Existe la tabla tblproject_designs?</h2>
    <pre><?php
    $result = $mysqli->query("SHOW TABLES LIKE 'tblproject_designs'");
    if ($result->num_rows > 0) {
        echo "✅ La tabla tblproject_designs EXISTE\n\n";

        // Count designs
        $count = $mysqli->query("SELECT COUNT(*) as count FROM tblproject_designs")->fetch_assoc();
        echo "Número de diseños: " . $count['count'] . "\n\n";

        if ($count['count'] > 0) {
            echo "Diseños en la tabla:\n";
            $designs = $mysqli->query("SELECT id, project_id, title, status, created_at FROM tblproject_designs ORDER BY id DESC LIMIT 10");
            while ($design = $designs->fetch_assoc()) {
                echo "  ID: {$design['id']}, Project: {$design['project_id']}, Título: {$design['title']}, Estado: {$design['status']}\n";
            }
        }
    } else {
        echo "❌ La tabla tblproject_designs NO EXISTE\n";
        echo "Esta tabla es necesaria para el sistema de aprobación de diseños.\n";
    }
    ?></pre>

    <h2>2. Archivos del Proyecto 8</h2>
    <pre><?php
    $files = $mysqli->query("SELECT id, file_name, filetype, dateadded, staffid FROM tblfiles WHERE rel_type = 'project' AND relid = 8 ORDER BY dateadded DESC");

    if ($files->num_rows > 0) {
        echo "✅ Encontrados " . $files->num_rows . " archivo(s) en el proyecto 8:\n\n";
        while ($file = $files->fetch_assoc()) {
            echo "  ID: {$file['id']}\n";
            echo "  Nombre: {$file['file_name']}\n";
            echo "  Tipo: {$file['filetype']}\n";
            echo "  Subido por staff: {$file['staffid']}\n";
            echo "  Fecha: {$file['dateadded']}\n\n";
        }
    } else {
        echo "❌ No hay archivos en el proyecto 8\n";
    }
    ?></pre>

    <h2>3. ¿Qué quieres hacer?</h2>
    <div style="background: #2d2d2d; padding: 20px; border-radius: 5px; margin-top: 20px;">
        <p><strong>Opción 1: Sistema de Aprobación de Diseños</strong></p>
        <p>Crear las tablas necesarias para que los clientes puedan aprobar diseños específicos desde la app móvil.</p>
        <ul>
            <li>Los staff suben diseños usando un formulario especial</li>
            <li>Los clientes ven y aprueban/rechazan desde la app</li>
            <li>Se registra quién aprobó y cuándo</li>
        </ul>

        <p><strong>Opción 2: Mostrar Archivos Normales</strong></p>
        <p>Modificar la app móvil para que muestre los archivos normales del proyecto (los que ya subiste).</p>
        <ul>
            <li>Más simple</li>
            <li>No requiere crear tablas nuevas</li>
            <li>Los clientes solo ven los archivos, no hay sistema de aprobación</li>
        </ul>
    </div>

    <?php $mysqli->close(); ?>
</body>
</html>
