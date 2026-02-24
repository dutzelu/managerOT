<?php
$titlu_pg = "Pelerinaje";
include "../includes/header.php";

// Interogare pentru a obține toate pelerinajele
$query = "SELECT p.*, 
          COUNT(pel.id) as numar_pelerini,
          SUM(CASE WHEN pel.cu_sau_fara_avion = 'cu avion' THEN 1 ELSE 0 END) as cu_avion,
          SUM(CASE WHEN pel.cu_sau_fara_avion = 'fara avion' THEN 1 ELSE 0 END) as fara_avion,
          SUM(pel.plata_euro) as total_euro,
          SUM(pel.plata_dolari) as total_dolari
          FROM pelerinaje p
          LEFT JOIN pelerini pel ON p.id = pel.pelerinaj_id
          GROUP BY p.id
          ORDER BY p.zi_start DESC";

$result = mysqli_query($conn, $query);
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "../includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-airplane-fill me-2"></i>Pelerinaje
        </h2>
        <a href="add_pelerinaj.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Adaugă Pelerinaj Nou
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <ul class="nav nav-tabs card-header-tabs" id="pelerinajeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
                                <i class="bi bi-check-circle me-2"></i>Active
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="finalizate-tab" data-bs-toggle="tab" data-bs-target="#finalizate" type="button" role="tab">
                                <i class="bi bi-calendar-check me-2"></i>Finalizate
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="anulate-tab" data-bs-toggle="tab" data-bs-target="#anulate" type="button" role="tab">
                                <i class="bi bi-x-circle me-2"></i>Anulate
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="toate-tab" data-bs-toggle="tab" data-bs-target="#toate" type="button" role="tab">
                                <i class="bi bi-list me-2"></i>Toate
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="pelerinajTabsContent">
                        
                        <!-- Tab Active -->
                        <div class="tab-pane fade show active" id="active" role="tabpanel">
                            <?php
                            mysqli_data_seek($result, 0);
                            $found_active = false;
                            while($row = mysqli_fetch_assoc($result)) {
                                if($row['status'] == 'activ') {
                                    $found_active = true;
                                    include 'pelerinaj_card.php';
                                }
                            }
                            if(!$found_active) {
                                echo '<p class="text-muted">Nu există pelerinaje active în acest moment.</p>';
                            }
                            ?>
                        </div>

                        <!-- Tab Finalizate -->
                        <div class="tab-pane fade" id="finalizate" role="tabpanel">
                            <?php
                            mysqli_data_seek($result, 0);
                            $found_finalizate = false;
                            while($row = mysqli_fetch_assoc($result)) {
                                if($row['status'] == 'finalizat') {
                                    $found_finalizate = true;
                                    include 'pelerinaj_card.php';
                                }
                            }
                            if(!$found_finalizate) {
                                echo '<p class="text-muted">Nu există pelerinaje finalizate.</p>';
                            }
                            ?>
                        </div>

                        <!-- Tab Anulate -->
                        <div class="tab-pane fade" id="anulate" role="tabpanel">
                            <?php
                            mysqli_data_seek($result, 0);
                            $found_anulate = false;
                            while($row = mysqli_fetch_assoc($result)) {
                                if($row['status'] == 'anulat') {
                                    $found_anulate = true;
                                    include 'pelerinaj_card.php';
                                }
                            }
                            if(!$found_anulate) {
                                echo '<p class="text-muted">Nu există pelerinaje anulate.</p>';
                            }
                            ?>
                        </div>

                        <!-- Tab Toate -->
                        <div class="tab-pane fade" id="toate" role="tabpanel">
                            <?php
                            mysqli_data_seek($result, 0);
                            while($row = mysqli_fetch_assoc($result)) {
                                include 'pelerinaj_card.php';
                            }
                            ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
