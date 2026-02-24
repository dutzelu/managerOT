<?php
include "includes/header.php";

// Recomandare: Dacă test_input() nu folosește mysqli_real_escape_string sau prepared statements,
// există riscul de SQL Injection. Asigură-te că funcția test_input() este sigură.

if(isset ($_POST["submit"]) ) {

    $nume = test_input($_POST['nume']);
    $data_start = test_input($_POST['data_start']);
    $data_final = test_input($_POST['data_final']);
    $descriere = test_input($_POST['descriere']);
    $link_ot = test_input($_POST['link_ot']);
    
    // Nota: Aici nu este inclusă logica pentru detaliile desfășurare și logo_campanie,
    // deoarece nu erau în query-ul original.

    
    $query="
    INSERT INTO campanii 
    
    (`nume`, `data_start`,`data_final`, `descriere`, `link_ot`)
    
   VALUES 

   ('$nume','$data_start','$data_final','$descriere','$link_ot');";

    $rez=mysqli_query($conn, $query);

    // Verifică dacă inserarea a reușit
    if($rez) {
        // SCHIMBARE: Redirecționează către campanii.php cu un mesaj de succes
        // Folosim urlencode pentru a proteja caracterele speciale din numele campaniei.
        header('Location: campanii.php?mesaj=succes&campanie=' . urlencode($nume));
        exit; // Oprește execuția scriptului după redirecționare
    } else {
        // Eroare la inserare. Oprește scriptul și afișează eroarea.
        die("Eroare la adăugarea campaniei: " . mysqli_error($conn));
    }
} else {
    // Dacă pagina a fost accesată direct sau fără a trimite formularul
    header('Location: adauga-campanie.php');
    exit;
}

?>