<?php
$titlu_pg = "Detalii Pelerin";
include "../includes/header.php";

// Verifică dacă avem ID-ul pelerinului
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerin_id = intval($_GET['id']);

$succes_update = false;
$errori = [];

// Procesare formular de editare
if(isset($_POST["submit"])) {
    
    $nume = test_input($_POST['nume']);
    $prenume = test_input($_POST['prenume']);
    $nume_mama = test_input($_POST['nume_mama']);
    $prenume_mama = test_input($_POST['prenume_mama']);
    $nume_tata = test_input($_POST['nume_tata']);
    $prenume_tata = test_input($_POST['prenume_tata']);
    $data_nasterii = test_input($_POST['data_nasterii']);
    $telefon = test_input($_POST['telefon']);
    $email = test_input($_POST['email']);
    $tara_domiciliu = test_input($_POST['tara_domiciliu']);
    $oras_domiciliu = test_input($_POST['oras_domiciliu']);
    $ocupatie = test_input($_POST['ocupatie']);
    $nume_angajator = test_input($_POST['nume_angajator']);
    $telefon_angajator = test_input($_POST['telefon_angajator']);
    $ultima_vizita_israel = test_input($_POST['ultima_vizita_israel']);
    $aplicat_viza = test_input($_POST['aplicat_viza']);
    $stare_civila = test_input($_POST['stare_civila']);
    $plata_dolari = test_input($_POST['plata_dolari']);
    $plata_euro = test_input($_POST['plata_euro']);
    $cu_sau_fara_avion = test_input($_POST['cu_sau_fara_avion']);
    $afectiuni_medicale = test_input($_POST['afectiuni_medicale']);
    $telefon_persoana_apropiata = test_input($_POST['telefon_persoana_apropiata']);
    
    // Validări
    if(empty($nume)) $errori[] = "Numele este obligatoriu.";
    if(empty($prenume)) $errori[] = "Prenumele este obligatoriu.";
    if(empty($data_nasterii)) $errori[] = "Data nașterii este obligatorie.";
    if(empty($telefon)) $errori[] = "Telefonul este obligatoriu.";
    
    // Upload pașaport nou (dacă există)
    $upload_pasaport_path = $_POST['pasaport_actual']; // Păstrează calea veche ca default
    
    if(isset($_FILES['upload_pasaport']) && $_FILES['upload_pasaport']['error'] == 0) {
        $file = $_FILES['upload_pasaport'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $target_dir = "../pelerini_pasapoarte";
        
        if(!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $allowed_ext = array("jpeg", "jpg", "png", "pdf");
        
        if(!in_array($file_ext, $allowed_ext)) {
            $errori[] = "Extensie fișier pașaport nepermisă. Acceptăm: JPEG, PNG, PDF.";
        }
        
        if($file_size > 10485760) { // 10MB
            $errori[] = "Fișierul pașaport este prea mare (max 10MB).";
        }
        
        if(empty($errori)) {
            $unique_name = uniqid() . '_' . $file_name;
            $upload_pasaport_path = $target_dir . '/' . $unique_name;
            
            if(!move_uploaded_file($file_tmp, $upload_pasaport_path)) {
                $errori[] = "Eroare la încărcarea pașaportului.";
                $upload_pasaport_path = $_POST['pasaport_actual'];
            }
        }
    }
    
    if(empty($errori)) {
        $query = "UPDATE pelerini SET
                  nume = ?, prenume = ?, nume_mama = ?, prenume_mama = ?, nume_tata = ?, prenume_tata = ?,
                  data_nasterii = ?, telefon = ?, email = ?, tara_domiciliu = ?, oras_domiciliu = ?,
                  ocupatie = ?, nume_angajator = ?, telefon_angajator = ?, ultima_vizita_israel = ?,
                  aplicat_viza = ?, stare_civila = ?, upload_pasaport = ?, plata_dolari = ?, plata_euro = ?,
                  cu_sau_fara_avion = ?, afectiuni_medicale = ?, telefon_persoana_apropiata = ?
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        
        if($stmt) {
            $stmt->bind_param("ssssssssssssssssssddsssi",
                $nume, $prenume, $nume_mama, $prenume_mama, $nume_tata, $prenume_tata,
                $data_nasterii, $telefon, $email, $tara_domiciliu, $oras_domiciliu,
                $ocupatie, $nume_angajator, $telefon_angajator, $ultima_vizita_israel,
                $aplicat_viza, $stare_civila, $upload_pasaport_path, $plata_dolari, $plata_euro,
                $cu_sau_fara_avion, $afectiuni_medicale, $telefon_persoana_apropiata, $pelerin_id
            );
            
            if($stmt->execute()) {
                $succes_update = true;
            } else {
                $errori[] = "Eroare la actualizare: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errori[] = "Eroare la pregătirea query-ului.";
        }
    }
}

// Obține datele pelerinului
$query = "SELECT p.*, pel.denumire as pelerinaj_denumire, pel.id as pelerinaj_id
          FROM pelerini p
          INNER JOIN pelerinaje pel ON p.pelerinaj_id = pel.id
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pelerin_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: pelerinaje.php");
    exit;
}

$pelerin = $result->fetch_assoc();
$stmt->close();
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "../includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">
    
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-person-fill me-2"></i>
            <?php echo htmlspecialchars($pelerin['nume'] . ' ' . $pelerin['prenume']); ?>
        </h2>
        <a href="pelerinaj.php?id=<?php echo $pelerin['pelerinaj_id']; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Înapoi la Pelerinaj
        </a>
    </div>

    <?php if($succes_update): ?>
        <div class="alert alert-success alert-dismissible fade show" id="success-form" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Succes!</strong> Datele pelerinului au fost actualizate cu succes.
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
        <div class="col-md-9">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Editează Date Pelerin</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="pasaport_actual" value="<?php echo htmlspecialchars($pelerin['upload_pasaport']); ?>">

                        <!-- Date Personale -->
                        <h6 class="text-primary mb-3"><i class="bi bi-person-fill me-2"></i>Date Personale</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume" class="form-label">Nume <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nume" name="nume" 
                                       value="<?php echo htmlspecialchars($pelerin['nume']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenume" class="form-label">Prenume <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prenume" name="prenume" 
                                       value="<?php echo htmlspecialchars($pelerin['prenume']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume_mama" class="form-label">Nume Mamă</label>
                                <input type="text" class="form-control" id="nume_mama" name="nume_mama" 
                                       value="<?php echo htmlspecialchars($pelerin['nume_mama']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenume_mama" class="form-label">Prenume Mamă</label>
                                <input type="text" class="form-control" id="prenume_mama" name="prenume_mama" 
                                       value="<?php echo htmlspecialchars($pelerin['prenume_mama']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume_tata" class="form-label">Nume Tată</label>
                                <input type="text" class="form-control" id="nume_tata" name="nume_tata" 
                                       value="<?php echo htmlspecialchars($pelerin['nume_tata']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenume_tata" class="form-label">Prenume Tată</label>
                                <input type="text" class="form-control" id="prenume_tata" name="prenume_tata" 
                                       value="<?php echo htmlspecialchars($pelerin['prenume_tata']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_nasterii" class="form-label">Data Nașterii <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="data_nasterii" name="data_nasterii" 
                                       value="<?php echo $pelerin['data_nasterii']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stare_civila" class="form-label">Stare Civilă</label>
                                <select class="form-select" id="stare_civila" name="stare_civila">
                                    <option value="">Selectează...</option>
                                    <option value="casatorit" <?php echo ($pelerin['stare_civila'] == 'casatorit') ? 'selected' : ''; ?>>Căsătorit</option>
                                    <option value="necasatorit" <?php echo ($pelerin['stare_civila'] == 'necasatorit') ? 'selected' : ''; ?>>Necăsătorit</option>
                                    <option value="divortat" <?php echo ($pelerin['stare_civila'] == 'divortat') ? 'selected' : ''; ?>>Divorțat</option>
                                    <option value="vaduv" <?php echo ($pelerin['stare_civila'] == 'vaduv') ? 'selected' : ''; ?>>Văduv</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date de Contact -->
                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-telephone-fill me-2"></i>Date de Contact</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telefon" class="form-label">Telefon <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="telefon" name="telefon" 
                                       value="<?php echo htmlspecialchars($pelerin['telefon']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($pelerin['email']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tara_domiciliu" class="form-label">Țara Domiciliu</label>
                                <input type="text" class="form-control" id="tara_domiciliu" name="tara_domiciliu" 
                                       value="<?php echo htmlspecialchars($pelerin['tara_domiciliu']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="oras_domiciliu" class="form-label">Oraș Domiciliu</label>
                                <input type="text" class="form-control" id="oras_domiciliu" name="oras_domiciliu" 
                                       value="<?php echo htmlspecialchars($pelerin['oras_domiciliu']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefon_persoana_apropiata" class="form-label">Telefon Persoană Apropiată (urgență)</label>
                            <input type="tel" class="form-control" id="telefon_persoana_apropiata" name="telefon_persoana_apropiata" 
                                   value="<?php echo htmlspecialchars($pelerin['telefon_persoana_apropiata']); ?>">
                        </div>

                        <!-- Informații Profesionale -->
                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-briefcase-fill me-2"></i>Informații Profesionale</h6>

                        <div class="mb-3">
                            <label for="ocupatie" class="form-label">Ocupație</label>
                            <input type="text" class="form-control" id="ocupatie" name="ocupatie" 
                                   value="<?php echo htmlspecialchars($pelerin['ocupatie']); ?>">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume_angajator" class="form-label">Nume Angajator</label>
                                <input type="text" class="form-control" id="nume_angajator" name="nume_angajator" 
                                       value="<?php echo htmlspecialchars($pelerin['nume_angajator']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="telefon_angajator" class="form-label">Telefon Angajator</label>
                                <input type="tel" class="form-control" id="telefon_angajator" name="telefon_angajator" 
                                       value="<?php echo htmlspecialchars($pelerin['telefon_angajator']); ?>">
                            </div>
                        </div>

                        <!-- Informații Israel și Vize -->
                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-flag-fill me-2"></i>Informații Israel</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ultima_vizita_israel" class="form-label">Ultima vizită în Israel (an)</label>
                                <input type="number" class="form-control" id="ultima_vizita_israel" name="ultima_vizita_israel" 
                                       min="1900" max="2026" value="<?php echo $pelerin['ultima_vizita_israel']; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="aplicat_viza" class="form-label">Aplicat viză lucru/locuire/studiu?</label>
                                <select class="form-select" id="aplicat_viza" name="aplicat_viza">
                                    <option value="Nu" <?php echo ($pelerin['aplicat_viza'] == 'Nu') ? 'selected' : ''; ?>>Nu</option>
                                    <option value="Da" <?php echo ($pelerin['aplicat_viza'] == 'Da') ? 'selected' : ''; ?>>Da</option>
                                </select>
                            </div>
                        </div>

                        <!-- Documente și Plată -->
                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-file-earmark-text-fill me-2"></i>Documente și Plată</h6>

                        <div class="mb-3">
                            <label for="upload_pasaport" class="form-label">
                                Încarcă Copie Pașaport (PDF, JPEG, PNG - max 10MB)
                            </label>
                            <?php if(!empty($pelerin['upload_pasaport'])): 
                                $ext = strtolower(pathinfo($pelerin['upload_pasaport'], PATHINFO_EXTENSION));
                            ?>
                                <div class="mb-2">
                                    <?php if(in_array($ext, ['jpg','jpeg','png'])): ?>
                                        <img src="<?php echo htmlspecialchars($pelerin['upload_pasaport']); ?>" 
                                             class="img-fluid rounded border" style="max-height:320px;" alt="Pașaport">
                                    <?php elseif($ext === 'pdf'): ?>
                                        <embed src="<?php echo htmlspecialchars($pelerin['upload_pasaport']); ?>" 
                                               type="application/pdf" width="100%" height="320px" class="border rounded">
                                    <?php endif; ?>
                                    <div class="mt-1">
                                        <a href="<?php echo htmlspecialchars($pelerin['upload_pasaport']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Deschide în tab nou
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="upload_pasaport" name="upload_pasaport" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Lasă gol pentru a păstra pașaportul actual</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="plata_euro" class="form-label">Plată Euro</label>
                                <input type="number" step="0.01" class="form-control" id="plata_euro" name="plata_euro" 
                                       value="<?php echo $pelerin['plata_euro']; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="plata_dolari" class="form-label">Plată Dolari</label>
                                <input type="number" step="0.01" class="form-control" id="plata_dolari" name="plata_dolari" 
                                       value="<?php echo $pelerin['plata_dolari']; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="cu_sau_fara_avion" class="form-label">Călătorie</label>
                                <select class="form-select" id="cu_sau_fara_avion" name="cu_sau_fara_avion">
                                    <option value="cu avion" <?php echo ($pelerin['cu_sau_fara_avion'] == 'cu avion') ? 'selected' : ''; ?>>Cu avion</option>
                                    <option value="fara avion" <?php echo ($pelerin['cu_sau_fara_avion'] == 'fara avion') ? 'selected' : ''; ?>>Fără avion</option>
                                </select>
                            </div>
                        </div>

                        <!-- Informații Medicale -->
                        <h6 class="text-primary mb-3 mt-4"><i class="bi bi-heart-pulse-fill me-2"></i>Informații Medicale</h6>

                        <div class="mb-3">
                            <label for="afectiuni_medicale" class="form-label">Afecțiuni Medicale / Alergii</label>
                            <textarea class="form-control" id="afectiuni_medicale" name="afectiuni_medicale" rows="4"><?php echo htmlspecialchars($pelerin['afectiuni_medicale']); ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save me-2"></i>Salvează Modificările
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-3 bg-light">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Informații</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Pelerinaj:</strong><br>
                        <a href="pelerinaj.php?id=<?php echo $pelerin['pelerinaj_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($pelerin['pelerinaj_denumire']); ?>
                        </a>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong>Data Înscriere:</strong><br>
                        <small><?php echo date('d.m.Y H:i', strtotime($pelerin['data_inscriere'])); ?></small>
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
