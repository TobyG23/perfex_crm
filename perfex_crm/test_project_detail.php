<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Project Detail</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Test Project Detail Endpoint</h1>

    <?php
    $project_id = isset($_GET['id']) ? $_GET['id'] : 7;
    $token = isset($_GET['token']) ? $_GET['token'] : 'client_7';

    echo "<h2>Testing: /api/project/$project_id</h2>";
    echo "<p>Token: $token</p>";
    echo "<pre>";

    $api_url = "http://perfex-local.test/api/project/$project_id";

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $http_code\n\n";

    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo "Raw response:\n";
            echo htmlspecialchars($response);
        }
    } else {
        echo "(empty response)";
    }

    echo "</pre>";

    if ($http_code == 200) {
        echo "<p class='success'>✅ Endpoint funcionando correctamente!</p>";
    } elseif ($http_code == 401) {
        echo "<p class='error'>❌ Error 401: Token inválido o no enviado</p>";
    } elseif ($http_code == 404) {
        echo "<p class='error'>❌ Error 404: Proyecto no encontrado o ruta incorrecta</p>";
    }
    ?>

    <h2>Verifica en la App Móvil:</h2>
    <ol>
        <li>Abre DevTools (F12)</li>
        <li>Ve a Console</li>
        <li>Escribe: <code>localStorage.getItem('auth_token')</code></li>
        <li>Si es null, necesitas hacer login de nuevo</li>
        <li>Si tiene un token, verifica que las peticiones incluyan el header Authorization</li>
    </ol>

</body>
</html>
