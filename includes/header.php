<?php
include __DIR__ . "/conexiune.php";
include __DIR__ . "/functii.php";

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location:" . BASE_URL . "login/login.php");
    exit;
}

setlocale(LC_TIME, array('ro.utf-8', 'ro_RO.UTF-8', 'ro_RO.utf-8', 'ro', 'ro_RO', 'ro_RO.ISO8859-2'));

$titlu_pg = $titlu_pg ?? 'Manager OT';
$current_user = $_SESSION['nume_utilizator'] ?? 'Utilizator';
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titlu_pg); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/ywpqronwp4p5zyx3ymuriis579s5rjamd0k04eqknrk9pd4c/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="<?php echo BASE_URL; ?>js/dataTables.js"></script>
</head>
<body>

<header class="app-topbar">
    <div class="app-topbar__inner">
        <button class="btn btn-outline-secondary app-mobile-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#appMobileNav" aria-controls="appMobileNav" aria-label="Deschide meniul">
            <i class="bi bi-list"></i>
        </button>

        <a class="app-brand" href="<?php echo BASE_URL; ?>index.php">
            <img src="<?php echo BASE_URL; ?>includes/logo-manager-OT.png" alt="Manager OT">
        </a>

        <div class="app-page-title">
            <span>Pagina curenta</span>
            <strong><?php echo htmlspecialchars($titlu_pg); ?></strong>
        </div>

        <div class="app-topbar__actions">
            <a class="btn btn-outline-primary btn-sm" href="<?php echo BASE_URL; ?>asistat-social-nou.php">
                <i class="bi bi-person-plus"></i> Beneficiar
            </a>
            <a class="btn btn-outline-primary btn-sm" href="<?php echo BASE_URL; ?>adauga-donatie.php">
                <i class="bi bi-plus-circle"></i> Ajutor
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo BASE_URL; ?>donatii-publice.php">
                <i class="bi bi-globe2"></i>
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo BASE_URL; ?>sponsorizare.php">
                <i class="bi bi-heart"></i>
            </a>
            <span class="d-none d-lg-inline-flex align-items-center text-muted small px-2">
                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($current_user); ?>
            </span>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo BASE_URL; ?>login/logout.php">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-start" tabindex="-1" id="appMobileNav" aria-labelledby="appMobileNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="appMobileNavLabel">Manager OT</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Inchide"></button>
    </div>
    <div class="offcanvas-body">
        <?php include __DIR__ . "/sidebar.php"; ?>
    </div>
</div>
