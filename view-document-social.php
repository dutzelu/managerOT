<?php
include __DIR__ . "/includes/conexiune.php";
include __DIR__ . "/includes/functii.php";

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Acces interzis.');
}

$id = $_GET['id'] ?? null;
if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    exit('Document invalid.');
}

$stmt = $conn->prepare("SELECT denumire_fisier, cale_fisier FROM documente_sociale WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$document = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$document) {
    http_response_code(404);
    exit('Documentul nu exista.');
}

$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $document['cale_fisier']);
$full_path = realpath(__DIR__ . DIRECTORY_SEPARATOR . $path);
$allowed_root = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'documente-sociale');

if (!$full_path || !$allowed_root || strpos($full_path, $allowed_root) !== 0 || !is_file($full_path)) {
    http_response_code(404);
    exit('Fisierul nu exista.');
}

$mime = 'application/octet-stream';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected = finfo_file($finfo, $full_path);
    finfo_close($finfo);
    if ($detected) {
        $mime = $detected;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full_path));
header('Content-Disposition: inline; filename="' . basename($document['denumire_fisier']) . '"');
readfile($full_path);
exit;
?>
