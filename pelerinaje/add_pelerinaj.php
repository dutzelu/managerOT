<?php
$titlu_pg = "Adaugă Pelerinaj";
include "../header.php";

$succes_insert = false;
$errori = [];

if(isset($_POST["submit"])) {
    
    $denumire = test_input($_POST['denumire']);
    $locatie = test_input($_POST['locatie']);
    $zi_start = test_input($_POST['zi_start']);
    $zi_sfarsit = test_input($_POST['zi_sfarsit']);
    $descriere = $_POST['descriere']; // nu folosim test_input pentru textarea cu HTML
    $link_ot = test_input($_POST['link_ot']);
    $cost_euro = test_input($_POST['cost_euro']);
    $cost_dolari = test_input($_POST['cost_dolari']);
    $status = test_input($_POST['status']);
    
    // Validări
    if(empty($denumire)) {
        $errori[] = "Denumirea este obligatorie.";
    }
    if(empty($locatie)) {
        $errori[] = "Locația este obligatorie.";
    }
    if(empty($zi_start)) {
        $errori[] = "Ziua de start este obligatorie.";
    }
    if(empty($zi_sfarsit)) {
        $errori[] = "Ziua de sfârșit este obligatorie.";
    }
    
    if(empty($errori)) {
        $query = "INSERT INTO pelerinaje 
                  (denumire, locatie, zi_start, zi_sfarsit, descriere, link_ot, cost_euro, cost_dolari, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        if($stmt) {
            $stmt->bind_param("ssssssdds", 
                $denumire,
                $locatie,
                $zi_start,
                $zi_sfarsit,
                $descriere,
                $link_ot,
                $cost_euro,
                $cost_dolari,
                $status
            );
            
            if($stmt->execute()) {
                $succes_insert = true;
                $pelerinaj_id = $stmt->insert_id;
            } else {
                $errori[] = "Eroare la inserarea datelor: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errori[] = "Eroare la pregătirea query-ului: " . $conn->error;
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "../sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-airplane-fill me-2"></i>Adaugă Pelerinaj Nou
        </h2>
        <a href="pelerinaje.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Înapoi la Lista Pelerinajelor
        </a>
    </div>

    <?php if($succes_insert): ?>
        <div class="alert alert-success alert-dismissible fade show" id="success-form" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Succes!</strong> Pelerinajul a fost adăugat cu succes.
            <a href="pelerinaj.php?id=<?php echo $pelerinaj_id; ?>" class="alert-link">Vezi pelerinajul</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($errori)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Eroare!</strong>
            <ul class="mb-0">
                <?php foreach($errori as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Detalii Pelerinaj</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        
                        <div class="mb-3">
                            <label for="denumire" class="form-label">Denumire Pelerinaj <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="denumire" name="denumire" 
                                   value="<?php echo isset($_POST['denumire']) ? htmlspecialchars($_POST['denumire']) : ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="locatie" class="form-label">Locație <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="locatie" name="locatie" 
                                   value="<?php echo isset($_POST['locatie']) ? htmlspecialchars($_POST['locatie']) : ''; ?>" 
                                   placeholder="ex: Israel, Ierusalim, Betleem" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="zi_start" class="form-label">Data Start <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="zi_start" name="zi_start" 
                                       value="<?php echo isset($_POST['zi_start']) ? $_POST['zi_start'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="zi_sfarsit" class="form-label">Data Sfârșit <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="zi_sfarsit" name="zi_sfarsit" 
                                       value="<?php echo isset($_POST['zi_sfarsit']) ? $_POST['zi_sfarsit'] : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descriere" class="form-label">Descriere</label>
                            <textarea class="form-control tinymce-editor" id="descriere" name="descriere" rows="8"><?php echo isset($_POST['descriere']) ? $_POST['descriere'] : ''; ?></textarea>
                            <small class="form-text text-muted">Descrie detaliile pelerinajului, itinerariul, etc.</small>
                        </div>

                        <div class="mb-3">
                            <label for="link_ot" class="form-label">Link OT</label>
                            <input type="url" class="form-control" id="link_ot" name="link_ot" 
                                   value="<?php echo isset($_POST['link_ot']) ? htmlspecialchars($_POST['link_ot']) : ''; ?>"
                                   placeholder="https://...">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost_euro" class="form-label">Cost (Euro)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_euro" name="cost_euro" 
                                       value="<?php echo isset($_POST['cost_euro']) ? $_POST['cost_euro'] : '0.00'; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost_dolari" class="form-label">Cost (Dolari)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_dolari" name="cost_dolari" 
                                       value="<?php echo isset($_POST['cost_dolari']) ? $_POST['cost_dolari'] : '0.00'; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="activ" <?php echo (isset($_POST['status']) && $_POST['status'] == 'activ') ? 'selected' : ''; ?>>Activ</option>
                                <option value="finalizat" <?php echo (isset($_POST['status']) && $_POST['status'] == 'finalizat') ? 'selected' : ''; ?>>Finalizat</option>
                                <option value="anulat" <?php echo (isset($_POST['status']) && $_POST['status'] == 'anulat') ? 'selected' : ''; ?>>Anulat</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Salvează Pelerinajul
                            </button>
                            <a href="pelerinaje.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Înapoi la Lista Pelerinajelor
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

        </div>
    </div>
</div>

<?php include "../footer.php"; ?>
