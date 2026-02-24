<?php
// Asigură-te că sesiunea este pornită dacă folosești o verificare de autentificare
// session_start(); 

include "includes/header.php"; 


if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_donatie = (int)$_GET['id'];
    
    // Conexiune și interogare securizată
    // Folosim prepared statement pentru a preveni SQL injection
    $stmt = $conn->prepare("DELETE FROM donatii WHERE ID = ?");
    
    if ($stmt === false) {
        die("Eroare la pregătirea interogării: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id_donatie);
    
    if ($stmt->execute()) {
        // Ștergere reușită: Redirecționare înapoi la tabel cu un mesaj de succes
        // Folosim un parametru GET 'deleted' pentru a afișa un mesaj de notificare
        header("Location: total-donatii.php?deleted=success&id=" . $id_donatie);
        exit;
    } else {
        // Eroare la execuție
        die("Eroare la ștergerea donației: " . $stmt->error);
    }
    
    $stmt->close();
    
} else {
    // ID lipsă sau invalid
    header("Location: total-donatii.php?deleted=error&msg=invalid_id");
    exit;
}

$conn->close();
?>