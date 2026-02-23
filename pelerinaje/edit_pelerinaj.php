<?php
$titlu_pg = "Editează Pelerinaj";
include "../header.php";

// Verifică dacă avem ID-ul pelerinajului
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerinaj_id = intval($_GET['id']);

$succes_update = false;
$errori = [];

// Procesare formular de editare
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
        $query = "UPDATE pelerinaje SET
                  denumire = ?, locatie = ?, zi_start = ?, zi_sfarsit = ?, 
                  descriere = ?, link_ot = ?, cost_euro = ?, cost_dolari = ?, status = ?
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        
        if($stmt) {
            $stmt->bind_param("ssssssddsi", 
                $denumire, $locatie, $zi_start, $zi_sfarsit,
                $descriere, $link_ot, $cost_euro, $cost_dolari, $status,
                $pelerinaj_id
            );
            
            if($stmt->execute()) {
                $succes_update = true;
            } else {
                $errori[] = "Eroare la actualizare: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errori[] = "Eroare la pregătirea query-ului: " . $conn->error;
        }
    }
}

// Obține datele pelerinajului
$query = "SELECT * FROM pelerinaje WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pelerinaj_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerinaj = $result->fetch_assoc();
$stmt->close();
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
            <i class="bi bi-pencil-square me-2"></i>Editează Pelerinaj
        </h2>
        <a href="pelerinaj.php?id=<?php echo $pelerinaj_id; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Înapoi
        </a>
    </div>

    <?php if($succes_update): ?>
        <div class="alert alert-success alert-dismissible fade show" id="success-form" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Succes!</strong> Pelerinajul a fost actualizat cu succes.
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
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Detalii Pelerinaj</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        
                        <div class="mb-3">
                            <label for="denumire" class="form-label">Denumire Pelerinaj <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="denumire" name="denumire" 
                                   value="<?php echo htmlspecialchars($pelerinaj['denumire']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="locatie" class="form-label">Locație <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="locatie" name="locatie" 
                                   value="<?php echo htmlspecialchars($pelerinaj['locatie']); ?>" 
                                   placeholder="ex: Israel, Ierusalim, Betleem" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="zi_start" class="form-label">Data Start <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="zi_start" name="zi_start" 
                                       value="<?php echo $pelerinaj['zi_start']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="zi_sfarsit" class="form-label">Data Sfârșit <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="zi_sfarsit" name="zi_sfarsit" 
                                       value="<?php echo $pelerinaj['zi_sfarsit']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descriere" class="form-label">Descriere</label>
                            <textarea class="form-control tinymce-editor" id="descriere" name="descriere" rows="8"><?php echo $pelerinaj['descriere']; ?></textarea>
                            <small class="form-text text-muted">Descrie detaliile pelerinajului, itinerariul, etc.</small>
                        </div>

                        <div class="mb-3">
                            <label for="link_ot" class="form-label">Link OT</label>
                            <input type="url" class="form-control" id="link_ot" name="link_ot" 
                                   value="<?php echo htmlspecialchars($pelerinaj['link_ot']); ?>"
                                   placeholder="https://...">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cost_euro" class="form-label">Cost (Euro)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_euro" name="cost_euro" 
                                       value="<?php echo $pelerinaj['cost_euro']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cost_dolari" class="form-label">Cost (Dolari)</label>
                                <input type="number" step="0.01" class="form-control" id="cost_dolari" name="cost_dolari" 
                                       value="<?php echo $pelerinaj['cost_dolari']; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="activ" <?php echo ($pelerinaj['status'] == 'activ') ? 'selected' : ''; ?>>Activ</option>
                                <option value="finalizat" <?php echo ($pelerinaj['status'] == 'finalizat') ? 'selected' : ''; ?>>Finalizat</option>
                                <option value="anulat" <?php echo ($pelerinaj['status'] == 'anulat') ? 'selected' : ''; ?>>Anulat</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Salvează Modificările
                            </button>
                            <a href="pelerinaj.php?id=<?php echo $pelerinaj_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Anulează
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm bg-light">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Info</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Pelerinaj ID:</strong><br>
                        #<?php echo $pelerinaj['id']; ?>
                    </p>
                    <hr>
                    <p class="mb-0">
                        <strong>Data Adăugare:</strong><br>
                        <small><?php echo date('d.m.Y H:i', strtotime($pelerinaj['data_adaugare'])); ?></small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

        </div>
    </div>
</div>

<?php include "../footer.php"; ?>
