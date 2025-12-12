<?php
// Carga de variables de entorno (Docker)
$dbhost = $_ENV["DB_HOST"] ?? "db";
$dbname = $_ENV["DB_NAME"] ?? "login";
$dbuser = $_ENV["DB_USER"] ?? "root";
$dbpass = $_ENV["DB_PASSWORD"] ?? "root";

// ===== Esperar a que MySQL esté ready =====
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

// ===== Lógica del registre =====
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user = $_POST["user"] ?? "";
    $pass = $_POST["password"] ?? "";

    if ($user !== "" && $pass !== "") {

        // insertar usuario con role=user
        $q = "INSERT INTO users (name, password, role) VALUES (:u, SHA2(:p, 512), 'user')";
        $stmt = $pdo->prepare($q);
        $stmt->bindParam(":u", $user, PDO::PARAM_STR);
        $stmt->bindParam(":p", $pass, PDO::PARAM_STR);

        try {
            $stmt->execute();
            // mensaje EXACTO que el test espera
            $message = "Usuari $user creat correctament.";
        } catch (Exception $e) {
            // por si el user existe
            $message = "No s'ha pogut crear l'usuari.";
        }
    }
}
?>
<html>
<head>
    <title>Registre</title>
</head>
<body>

<h1>Registre d’usuari</h1>

<form method="post">
    User: <input type="text" name="user"><br>
    Pass: <input type="password" name="password"><br>
    <input type="submit" value="Registre"><br>
</form>

<div class="user"><?= $message ?></div>

</body>
</html>
