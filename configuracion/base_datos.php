<?php
define('BD_SERVIDOR',  'localhost');
define('BD_NOMBRE',    'GESTAC');
define('BD_USUARIO',   'root');
define('BD_CONTRASENA', '');
define('BD_CONJUNTO_CARACTERES', 'utf8mb4');

function obtenerConexion(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $pdo = new PDO(
        "mysql:host=" . BD_SERVIDOR . ";dbname=" . BD_NOMBRE . ";charset=" . BD_CONJUNTO_CARACTERES,
        BD_USUARIO,
        BD_CONTRASENA,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    return $pdo;
}
