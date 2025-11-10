<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple API Test</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
        h2 { color: #4ec9b0; }
    </style>
</head>
<body>
    <h1>🔍 API Test - Diagnóstico Completo</h1>

    <?php
    echo "<h2>Step 1: Test Login</h2>";
    echo "<pre>";

    $login_url = 'http://perfex-local.test/api/login';
    $credentials = json_encode([
        'email' => 'tobiasgarcia183@gmail.com',
        'password' => 'Prueba123!',
        'login_as' => 'client'
    ]);

    $ch = curl_init($login_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $credentials);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $login_response = curl_exec($ch);
    $login_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Login HTTP Code: $login_code\n";
    echo "Response:\n";
    $login_data = json_decode($login_response, true);
    echo json_encode($login_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";

    $token = null;
    if ($login_code == 200 && isset($login_data['data']['token'])) {
        $token = $login_data['data']['token'];
        echo "<p class='success'>✅ Login exitoso! Token: $token</p>";
    } else {
        echo "<p class='error'>❌ Login falló!</p>";
    }

    if ($token) {
        echo "<h2>Step 2: Test Projects Endpoint</h2>";
        echo "<pre>";

        $projects_url = 'http://perfex-local.test/api/projects';

        $ch = curl_init($projects_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        $projects_response = curl_exec($ch);
        $projects_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        echo "Projects HTTP Code: $projects_code\n\n";

        if ($curl_error) {
            echo "CURL Error: $curl_error\n\n";
        }

        echo "Response:\n";
        if ($projects_response) {
            $projects_data = json_decode($projects_response, true);
            if ($projects_data) {
                echo json_encode($projects_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                echo "Raw response (not JSON):\n";
                echo htmlspecialchars($projects_response);
            }
        } else {
            echo "(empty response)";
        }
        echo "</pre>";

        if ($projects_code == 500) {
            echo "<h2 class='error'>❌ Error 500 Detectado - Revisando Logs</h2>";
            echo "<pre>";

            // Check PHP error log
            $log_files = [
                'C:/xampp/apache/logs/error.log',
                'C:/xampp/php/logs/php_error_log.txt',
            ];

            foreach ($log_files as $log_file) {
                if (file_exists($log_file)) {
                    echo "\n=== Últimas 50 líneas de: $log_file ===\n";
                    $lines = file($log_file);
                    $recent = array_slice($lines, -50);
                    echo htmlspecialchars(implode('', $recent));
                    echo "\n";
                }
            }
            echo "</pre>";

            // Try to access the endpoint directly and capture PHP errors
            echo "<h2>Step 2b: Llamada Directa al Endpoint</h2>";
            echo "<pre>";

            // Simulate the request
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/api/projects';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

            echo "Intentando cargar el controlador API directamente...\n\n";

            try {
                ob_start();
                include('application/controllers/api/Api.php');
                $output = ob_get_clean();
                echo "Output capturado:\n";
                echo htmlspecialchars($output);
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString();
            } catch (Error $e) {
                echo "Error: " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString();
            }

            echo "</pre>";
        } elseif ($projects_code == 200) {
            echo "<p class='success'>✅ Projects endpoint funcionando correctamente!</p>";
        }
    }

    echo "<h2>Step 3: Verificación de Base de Datos</h2>";
    echo "<pre>";
    require_once('application/config/database.php');

    try {
        $mysqli = new mysqli(
            $db['default']['hostname'],
            $db['default']['username'],
            $db['default']['password'],
            $db['default']['database']
        );

        if ($mysqli->connect_error) {
            echo "❌ Error de conexión: " . $mysqli->connect_error;
        } else {
            echo "✅ Base de datos conectada\n\n";

            // Check user
            $email = $mysqli->real_escape_string('tobiasgarcia183@gmail.com');
            $result = $mysqli->query("SELECT id, email, userid, active FROM tblcontacts WHERE email = '$email'");

            if ($result && $row = $result->fetch_assoc()) {
                echo "Usuario encontrado:\n";
                echo "  Contact ID: " . $row['id'] . "\n";
                echo "  User ID: " . $row['userid'] . "\n";
                echo "  Active: " . ($row['active'] ? 'Sí' : 'No') . "\n\n";

                $userid = $row['userid'];

                // Check projects
                $projects = $mysqli->query("SELECT id, name, status FROM tblprojects WHERE clientid = $userid LIMIT 5");
                if ($projects) {
                    $count = $projects->num_rows;
                    echo "Proyectos encontrados para el cliente: $count\n\n";

                    if ($count > 0) {
                        echo "Lista de proyectos:\n";
                        while ($proj = $projects->fetch_assoc()) {
                            echo "  - [{$proj['id']}] {$proj['name']} (status: {$proj['status']})\n";
                        }
                    }
                } else {
                    echo "Error al buscar proyectos: " . $mysqli->error . "\n";
                }
            } else {
                echo "❌ Usuario no encontrado\n";
            }

            $mysqli->close();
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
    echo "</pre>";

    echo "<h2>Step 4: Verificar Archivos del API</h2>";
    echo "<pre>";

    $api_file = 'application/controllers/api/Api.php';
    echo "Archivo API: ";
    if (file_exists($api_file)) {
        echo "✅ Existe\n";
        echo "Tamaño: " . filesize($api_file) . " bytes\n";
        echo "Última modificación: " . date("Y-m-d H:i:s", filemtime($api_file)) . "\n";
    } else {
        echo "❌ NO EXISTE\n";
    }

    echo "\nArchivo de rutas: ";
    $routes_file = 'application/config/routes.php';
    if (file_exists($routes_file)) {
        echo "✅ Existe\n";
    } else {
        echo "❌ NO EXISTE\n";
    }

    echo "</pre>";
    ?>

    <h2>📋 Resumen y Próximos Pasos:</h2>
    <ul>
        <li>Si ves un error específico en los logs arriba, ese es el problema</li>
        <li>Copia TODO el contenido de esta página y compártelo</li>
        <li>Especialmente importante es la sección "Error 500 Detectado"</li>
    </ul>

</body>
</html>
