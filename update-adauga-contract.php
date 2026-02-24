<?php
include "includes/header.php";
// Verificăm dacă formularul a fost trimis
if (isset($_POST['submit'])) {
    // Preluăm valorile din formular
    $data_semnarii = $_POST['data_semnarii'];
    $continut      = $conn->real_escape_string($_POST['continut']);
    $sponsor      = $conn->real_escape_string($_POST['sponsor']);
    $beneficiar   = $conn->real_escape_string($_POST['beneficiar']);
    $suma         = $conn->real_escape_string($_POST['suma']);

    // Extragem anul din data semnării
    $year = date("Y", strtotime($data_semnarii));

    // Determinăm numărul recurent pentru anul respectiv
    // Se caută ultima înregistrare cu anul dorit din numărul contractului
    $query  = "SELECT numar FROM contracte 
               WHERE RIGHT(numar, 4) = '$year' 
               ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row    = $result->fetch_assoc();
        $numar_exist = $row['numar'];
        // Se presupune că formatul stocat este: "AOT" + număr recurent + anul (ex: AOT12025)
        // Se extrage partea numerică a numărului contractului (entre poziția 3 și ultimele 4 caractere)
        $serial = substr($numar_exist, 3, -4);
        $next_serial = (int)$serial + 1;
    } else {
        $next_serial = 1;
    }

    // Se compune numărul contractului
    $numar_contract = "AOT" . $next_serial . $year;

    // Se pregătește interogarea de inserare
    $sql = "INSERT INTO contracte (numar, continut, sponsor, beneficiar, suma, data_semnarii) 
            VALUES ('$numar_contract', '$continut', '$sponsor', '$beneficiar', '$suma', '$data_semnarii')";

    // Se execută inserarea
    if ($conn->query($sql) === TRUE) {
        // Redirecționare sau afișare mesaj de succes
        header("Location: adauga-contract.php?contract=" . urlencode($numar_contract));
        exit();
    } else {
        echo "Eroare la inserare: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
