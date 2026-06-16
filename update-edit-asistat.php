<?php
// Endpoint vechi pastrat doar pentru compatibilitate.
// Salvarea fisei sociale se face acum direct in edit-asistat.php.
include "includes/conexiune.php";

$id = $_GET['id'] ?? $_POST['id'] ?? null;
if (!empty($id) && is_numeric($id)) {
    header('Location: edit-asistat.php?id=' . (int)$id);
    exit;
}

header('Location: asistati.php');
exit;
?>
