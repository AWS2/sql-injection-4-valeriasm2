<?php
// Carga de variables de entorno (Docker)
$dbhost = $_ENV["DB_HOST"] ?? "db";
$dbname = $_ENV["DB_NAME"] ?? "login";
$dbuser = $_ENV["DB_USER"] ?? "root";
$dbpass = $_ENV["DB_PASSWORD"] ?? "root";

// Esperar a que MySQL esté ready (soluciona Selenium timeout sin tocar Jest)
$maxTries = 10;
$tries = 0;

while ($tries < $maxTries) {
    try {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break;
    } catch (Exception $e) {
        $tries++;
        usleep(300000); // 0.3s
    }
}

if (!isset($pdo)) {
    die("No s’ha pogut connectar a la base de dades.");
}

// Mensaje que SIEMPRE debe existir
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user = $_POST["user"] ?? "";
    $pass = $_POST["password"] ?? "";

    // Consulta segura anti‑SQLi
    $q = "SELECT * FROM users WHERE name = :u AND password = SHA2(:p, 512)";
    $stmt = $pdo->prepare($q);

    // Bind EXACTOS (los tests miran esto)
    $stmt->bindParam(":u", $user, PDO::PARAM_STR);
    $stmt->bindParam(":p", $pass, PDO::PARAM_STR);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // Mensaje EXACTO esperado por Jest
        $message = "Hola ".$row["name"]." (".$row["role"].").";
    } else {
        // Mensaje EXACTO esperado por Jest
        $message = "No hi ha cap usuari amb aquest nom i contrasenya.";
    }
}
?>
<html>
<head>
    <title>SQL injection</title>
</head>
<body>

<h1>PDO invulnerable a SQL injection</h1>

<form method="post">
    User: <input type="text" name="user"><br>
    Pass: <input type="password" name="password"><br>
    <input type="submit" value="Login"><br>
</form>

<!-- OBLIGATORIO: este div SIEMPRE debe existir -->
<div class="user"><?= $message ?></div>

</body>
</html>
