<?php 
include "conexiune.php";
include "functii.php";

// 1. PORNEȘTE SESIUNEA PRIMA DATĂ
session_start();

// 2. VERIFICĂ AUTENTIFICAREA:
// Dacă utilizatorul NU este logat, redirecționează-l la pagina de login.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location:" . BASE_URL . "login/login.php");
    exit;
}
// Dacă ajunge aici, utilizatorul este autentificat și poate vedea pagina.

setlocale(LC_TIME, array('ro.utf-8', 'ro_RO.UTF-8', 'ro_RO.utf-8', 'ro', 'ro_RO', 'ro_RO.ISO8859-2'));  

?>

<!DOCTYPE html>
<html lang="en">
<head>
 
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titlu_pg; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL;?>/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/ywpqronwp4p5zyx3ymuriis579s5rjamd0k04eqknrk9pd4c/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="<?php echo BASE_URL;?>/js/dataTables.js"></script>

 

</head>
<body>
    


<nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL;?>index.php">
            <img src="<?php echo BASE_URL;?>logo-manager-OT.png" alt="Logo Manager OT" height="80">
            </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" aria-current="page" href="index.php">
                        <i class="bi bi-house-door-fill me-1"></i>Acasă
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'asistati.php' || basename($_SERVER['PHP_SELF']) == 'view-asistat.php' || basename($_SERVER['PHP_SELF']) == 'adauga-asistat.php') ? 'active' : ''; ?>" href="asistati.php">
                        <i class="bi bi-people-fill me-1"></i>Asistați
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="asistat-social-nou.php">
                        + Asistat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'donatii.php' || basename($_SERVER['PHP_SELF']) == 'total-donatii.php' || basename($_SERVER['PHP_SELF']) == 'adauga-donatie.php') ? 'active' : ''; ?>" href="total-donatii.php">
                        <i class="bi bi-cash-stack me-1"></i>Donații
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="adauga-donatie.php">
                        + Donație
                    </a>
                </li>
                 <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'campanii.php' || basename($_SERVER['PHP_SELF']) == 'view-campanie.php' || basename($_SERVER['PHP_SELF']) == 'adauga-campanie.php') ? 'active' : ''; ?>" href="campanii.php">
                        <i class="bi bi-megaphone-fill me-1"></i>Campanii
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL;?>login/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://ortodoxiatinerilor.ro/managerot/donatii-publice.php">
                        <i class="bi bi-globe me-1"></i>
                       Donații publice
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

