<?php
include "../conexiune.php"; 

$nume_utilizator = "claudiuBalan_managerOT"; // Schimbă-l!
$parola = "Parola*0920"; // Schimbă-l!

// Criptarea parolei
$hashed_parola = password_hash($parola, PASSWORD_DEFAULT);

$sql = "INSERT INTO utilizatori (nume_utilizator, parola) VALUES (?, ?)";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ss", $nume_utilizator, $hashed_parola);
    if (mysqli_stmt_execute($stmt)) {
        echo "Utilizatorul '$nume_utilizator' a fost creat cu succes! <br> ACUM ȘTERGE ACEST FIȘIER!";
    } else {
        echo "Eroare: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}
?>