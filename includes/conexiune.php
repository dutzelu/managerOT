<?php


$dbServerName = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'managerot';

$conn = mysqli_connect ($dbServerName, $dbUser, $dbPassword, $dbName);
mysqli_set_charset($conn, "utf8");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// Detectăm protocolul (http / https)
$protocol = 'http://';
if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
) {
    $protocol = 'https://';
}

// Domeniul (de ex. localhost, siteulmeu.ro etc.)
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Calea până la aplicație (bazată pe directorul părinte al includes/, nu pe scriptul curent)
$appDir  = str_replace('\\', '/', dirname(dirname(__FILE__)));
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$basePath = rtrim(str_replace($docRoot, '', $appDir), '/');

// Construim BASE_URL (mereu cu slash la final)
define('BASE_URL', $protocol . $host . ($basePath ? $basePath . '/' : '/'));


?>