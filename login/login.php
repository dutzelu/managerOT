<?php
// Pornește sesiunea
session_start();
 
// Include conexiunea la baza de date
include "../includes/conexiune.php"; // Folosește variabila $conn
// Presupunem că BASE_URL este definită în conexiune.php sau config.php
if (!defined('BASE_URL')) {
    // Defineste o valoare implicită dacă nu e definită (pentru a evita erori)
    define('BASE_URL', '/');
}

// Verifică dacă utilizatorul este deja logat
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location:" . BASE_URL . "index.php");
    exit;
}

$eroare = '';
// Procesează datele formularului când este trimis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nume_utilizator = trim($_POST["nume_utilizator"]);
    $parola_introdusa = trim($_POST["parola"]);

    // Pregătește interogarea SELECT sigură (folosind $conn)
    $sql = "SELECT id, nume_utilizator, parola FROM utilizatori WHERE nume_utilizator = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $nume_utilizator);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {                    
                mysqli_stmt_bind_result($stmt, $id, $nume_utilizator_db, $hashed_parola);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($parola_introdusa, $hashed_parola)) {
                        // Parolă corectă, setează variabilele de sesiune
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["nume_utilizator"] = $nume_utilizator_db;
                        
                        // Redirecționează utilizatorul la pagina principală
                        header("location:" . BASE_URL . "index.php");
                    } else {
                        // Parolă incorectă
                        $eroare = "Nume utilizator sau parolă incorectă.";
                    }
                }
            } else {
                // Nume utilizator incorect
                $eroare = "Nume utilizator sau parolă incorectă.";
            }
        } else {
            $eroare = "Eroare la execuția interogării.";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manager OT</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
    <style>
        /* Centrarea se va face prin clasele Bootstrap */
        body {
            background-color: #f8f9fa; /* Fundal deschis */
        }
        .login-container {
            /* Stil pentru a face caseta de login să arate bine */
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            background-color: #ffffff;
        }

        img {max-width: 100%; height: auto;}
    </style>
</head>
<body class="d-flex justify-content-center align-items-center vh-100"> 
    
    <div class="col-11 col-md-4 login-container"> 
        
        <a class="mb-4 d-block text-center" href="<?php echo BASE_URL;?>index.php">
            <img src="../includes/logo-manager-OT.png" alt="Logo Manager OT">
        </a>
        
        <h4 class="text-center mb-4">Autentificare</h4>
        
        <?php if (!empty($eroare)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $eroare; ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="text" name="nume_utilizator" class="form-control" required placeholder="Nume utilizator" value="<?php echo htmlspecialchars($nume_utilizator ?? ''); ?>">
            </div>    
            <div class="form-group">
                <input type="password" name="parola" class="form-control" required placeholder="Parolă">
            </div>
            <div class="form-group text-center mt-4">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
        </form>
    </div>

</body>
</html>