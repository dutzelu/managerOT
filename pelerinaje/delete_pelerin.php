<?php
include "../includes/header.php";

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['pelerinaj_id']) && is_numeric($_GET['pelerinaj_id'])) {
    $id_pelerin    = (int)$_GET['id'];
    $pelerinaj_id  = (int)$_GET['pelerinaj_id'];

    $stmt = $conn->prepare("DELETE FROM pelerini WHERE id = ?");

    if ($stmt === false) {
        die("Eroare la pregătirea interogării: " . $conn->error);
    }

    $stmt->bind_param("i", $id_pelerin);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: pelerinaj.php?id=" . $pelerinaj_id . "&deleted=success");
        exit;
    } else {
        die("Eroare la ștergerea pelerinului: " . $stmt->error);
    }

} else {
    header("Location: pelerinaje.php");
    exit;
}
?>
