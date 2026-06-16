<?php
$current_file = basename($_SERVER['PHP_SELF'] ?? '');
$current_path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

if (!function_exists('sidebar_active_class')) {
    function sidebar_active_class($files = array(), $path_contains = '') {
        global $current_file, $current_path;
        if (in_array($current_file, $files, true)) {
            return ' active';
        }
        if ($path_contains !== '' && strpos($current_path, $path_contains) !== false) {
            return ' active';
        }
        return '';
    }
}
?>

<aside class="menu-principal">
    <nav aria-label="Navigatie principala">
        <h6 class="sidebar-heading">Lucru curent</h6>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('index.php')); ?>" href="<?php echo BASE_URL; ?>index.php">
                    <i class="bi bi-grid-1x2-fill"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('asistati.php', 'edit-asistat.php', 'asistat-social-nou.php', 'export-fisa-sociala.php')); ?>" href="<?php echo BASE_URL; ?>asistati.php">
                    <i class="bi bi-people-fill"></i>
                    Beneficiari
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('total-donatii.php', 'lista-donatii.php', 'adauga-donatie.php', 'edit-donatie.php')); ?>" href="<?php echo BASE_URL; ?>total-donatii.php">
                    <i class="bi bi-cash-coin"></i>
                    Ajutoare acordate
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading">Administrare</h6>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('campanii.php', 'view-campanie.php', 'adauga-campanie.php', 'edit-campanie.php')); ?>" href="<?php echo BASE_URL; ?>campanii.php">
                    <i class="bi bi-megaphone-fill"></i>
                    Campanii
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('contracte.php', 'adauga-contract.php', 'edit-contract.php', 'genereaza-contract-sponsorizare.php')); ?>" href="<?php echo BASE_URL; ?>contracte.php">
                    <i class="bi bi-file-earmark-text-fill"></i>
                    Contracte
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('conta.php', 'tabel-anual-contabilitate.php')); ?>" href="<?php echo BASE_URL; ?>conta.php">
                    <i class="bi bi-calculator-fill"></i>
                    Contabilitate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array(), '/pelerinaje/'); ?>" href="<?php echo BASE_URL; ?>pelerinaje/pelerinaje.php">
                    <i class="bi bi-airplane-fill"></i>
                    Pelerinaje
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading">Adaugare rapida</h6>
        <ul class="nav nav-pills flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>asistat-social-nou.php">
                    <i class="bi bi-person-plus-fill"></i>
                    Beneficiar nou
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>adauga-donatie.php">
                    <i class="bi bi-plus-circle-fill"></i>
                    Ajutor acordat
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>adauga-contract.php">
                    <i class="bi bi-file-earmark-plus-fill"></i>
                    Contract nou
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>adauga-campanie.php">
                    <i class="bi bi-calendar-event-fill"></i>
                    Campanie noua
                </a>
            </li>
        </ul>

        <hr>

        <h6 class="sidebar-heading">Pagini publice</h6>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>donatii-publice.php">
                    <i class="bi bi-globe2"></i>
                    Donații publice
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link<?php echo sidebar_active_class(array('sponsorizare.php', 'sponsorizari.php')); ?>" href="<?php echo BASE_URL; ?>sponsorizare.php">
                    <i class="bi bi-heart-fill"></i>
                    Sponsorizări
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>login/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Deconectare
                </a>
            </li>
        </ul>
    </nav>
</aside>
