<?php
// sterge-campanie.php - Gestionează ștergerea unei campanii

include "includes/header.php"; // Include conexiunea ($conn) și verificarea sesiunii

// 1. Verifică ID-ul
$id = $_GET['id'] ?? null;

if (empty($id) || !is_numeric($id)) {
    // Redirecționează la lista de campanii cu eroare dacă ID-ul lipsește sau este invalid
    header('Location: campanii.php?mesaj=eroare&detalii=id_invalid');
    exit;
}

// 2. Interogare SQL pentru ștergere (folosind Prepared Statement pentru siguranță)
$sql = "DELETE FROM campanii WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // În caz de eroare la pregătirea interogării
    header('Location: campanii.php?mesaj=eroare&detalii=' . urlencode('Eroare la pregătirea interogării SQL.'));
    exit;
}

$stmt->bind_param("i", $id);
$success = $stmt->execute();

// 3. Verifică rezultatul și redirecționează
if ($success && $stmt->affected_rows > 0) {
    // Succes: Redirecționează la lista de campanii
    header('Location: campanii.php?mesaj=succes_sters&id=' . htmlspecialchars($id));
    exit;
} else if ($stmt->affected_rows == 0) {
    // Eroare: Campania nu a fost găsită sau ștearsă
    header('Location: campanii.php?mesaj=eroare&detalii=' . urlencode('Campania nu a fost găsită.'));
    exit;
} else {
    // Eroare: Problemă la execuția interogării
    header('Location: campanii.php?mesaj=eroare&detalii=' . urlencode('Eroare la ștergerea din baza de date.'));
    exit;
}

$stmt->close();
?>