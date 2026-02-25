<?php
include "../includes/conexiune.php";
include "../includes/functii.php";

$succes_insert = false;
$errori = [];
$pelerinaj_selectat = null;

// Verifică dacă avem un pelerinaj preselecționat din URL
if(isset($_GET['pelerinaj']) && is_numeric($_GET['pelerinaj'])) {
    $pelerinaj_id_url = intval($_GET['pelerinaj']);
    
    // Verifică dacă pelerinajul există și este activ
    $check_query = "SELECT * FROM pelerinaje WHERE id = ? AND status = 'activ'";
    $stmt_check = $conn->prepare($check_query);
    $stmt_check->bind_param("i", $pelerinaj_id_url);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if($result_check->num_rows > 0) {
        $pelerinaj_selectat = $result_check->fetch_assoc();
    }
    $stmt_check->close();
}

// Procesare formular
if(isset($_POST["submit"])) {
    
    $pelerinaj_id = test_input($_POST['pelerinaj_dorit']);
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
    
    // Validări de bază
    if(empty($nume)) $errori[] = "Numele este obligatoriu.";
    if(empty($prenume)) $errori[] = "Prenumele este obligatoriu.";
    if(empty($nume_mama)) $errori[] = "Numele mamei este obligatoriu.";
    if(empty($prenume_mama)) $errori[] = "Prenumele mamei este obligatoriu.";
    if(empty($nume_tata)) $errori[] = "Numele tatălui este obligatoriu.";
    if(empty($prenume_tata)) $errori[] = "Prenumele tatălui este obligatoriu.";
    if(empty($data_nasterii)) $errori[] = "Data nașterii este obligatorie.";
    if(empty($stare_civila)) $errori[] = "Starea civilă este obligatorie.";
    if(empty($telefon)) $errori[] = "Telefonul este obligatoriu.";
    if(empty($email)) $errori[] = "Email-ul este obligatoriu.";
    if(empty($tara_domiciliu)) $errori[] = "Țara domiciliului este obligatorie.";
    if(empty($oras_domiciliu)) $errori[] = "Orașul domiciliului este obligatoriu.";
    if(empty($telefon_persoana_apropiata)) $errori[] = "Telefonul persoanei apropiate este obligatoriu.";
    if(empty($ocupatie)) $errori[] = "Ocupația este obligatorie.";
    if(empty($pelerinaj_id)) $errori[] = "Trebuie să selectați un pelerinaj.";
    if(!isset($_FILES['upload_pasaport']) || $_FILES['upload_pasaport']['error'] != 0) {
        $errori[] = "Copia pașaportului este obligatorie.";
    }
    
    // Upload pașaport
    $upload_pasaport_path = null;
    if(isset($_FILES['upload_pasaport']) && $_FILES['upload_pasaport']['error'] == 0) {
        $file = $_FILES['upload_pasaport'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $target_dir = "pelerini_pasapoarte";
        
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
                $upload_pasaport_path = null;
            }
        }
    }
    
    if(empty($errori)) {
        $query = "INSERT INTO pelerini 
                  (pelerinaj_id, nume, prenume, nume_mama, prenume_mama, nume_tata, prenume_tata,
                   data_nasterii, telefon, email, tara_domiciliu, oras_domiciliu, ocupatie,
                   nume_angajator, telefon_angajator, ultima_vizita_israel, aplicat_viza,
                   stare_civila, upload_pasaport, plata_dolari, plata_euro, cu_sau_fara_avion,
                   afectiuni_medicale, telefon_persoana_apropiata)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        
        if($stmt) {
            $stmt->bind_param("isssssssssssssissssddsss",
                $pelerinaj_id, $nume, $prenume, $nume_mama, $prenume_mama, $nume_tata, $prenume_tata,
                $data_nasterii, $telefon, $email, $tara_domiciliu, $oras_domiciliu, $ocupatie,
                $nume_angajator, $telefon_angajator, $ultima_vizita_israel, $aplicat_viza,
                $stare_civila, $upload_pasaport_path, $plata_dolari, $plata_euro, $cu_sau_fara_avion,
                $afectiuni_medicale, $telefon_persoana_apropiata
            );
            
            if($stmt->execute()) {
                $succes_insert = true;
            } else {
                $errori[] = "Eroare la înregistrare: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errori[] = "Eroare la pregătirea query-ului.";
        }
    }
}

// Obține lista pelerinajelor active
$pelerinaje_query = "SELECT * FROM pelerinaje WHERE status = 'activ' ORDER BY zi_start ASC";
$pelerinaje_result = mysqli_query($conn, $pelerinaje_query);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formular Înscriere Pelerinaj</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .form-header h1 {
            color: #667eea;
            font-weight: bold;
        }
        .section-title {
            color: #667eea;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        @media (max-width: 576px) {
            .form-container {
                padding: 20px 15px;
            }
            body {
                padding: 15px 0;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        
        <?php if($succes_insert): ?>
            <div class="alert alert-success text-center" role="alert">
                <i class="bi bi-check-circle-fill me-2" style="font-size: 2rem;"></i>
                <h4 class="alert-heading">Înscriere Reușită!</h4>
                <p>Mulțumim pentru înregistrare!</p>
                <hr>
                <a href="formular_pelerin.php<?php echo isset($_GET['pelerinaj']) ? '?pelerinaj='.$_GET['pelerinaj'] : ''; ?>" class="btn btn-primary">
                    Înscrie o altă persoană
                </a>
            </div>
        <?php else: ?>
        
        <div class="form-header">
            <i class="bi bi-airplane-fill" style="font-size: 3rem; color: #667eea;"></i>
            <h1>Formular înscriere pelerinaj</h1>
            <?php if($pelerinaj_selectat): ?>
                <p class="lead mb-0"><?php echo htmlspecialchars($pelerinaj_selectat['denumire']); ?></p>
            <?php endif; ?>
        </div>

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

        <form action="" method="POST" enctype="multipart/form-data">
            
            <!-- Selecție Pelerinaj -->
            <div class="mb-4">
                <label for="pelerinaj_dorit" class="form-label required-field">Selectează pelerinajul</label>
                <select class="form-select" id="pelerinaj_dorit" name="pelerinaj_dorit" required>
                    <option value="">Alege un pelerinaj...</option>
                    <?php while($pel = mysqli_fetch_assoc($pelerinaje_result)): ?>
                        <option value="<?php echo $pel['id']; ?>"
                                <?php 
                                if(($pelerinaj_selectat && $pelerinaj_selectat['id'] == $pel['id']) || 
                                   (isset($_POST['pelerinaj_dorit']) && $_POST['pelerinaj_dorit'] == $pel['id'])) {
                                    echo 'selected';
                                }
                                ?>>
                            <?php echo htmlspecialchars($pel['denumire']); ?> 
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>


            <div class="row">
                <p>
                    Vă rog completați toate câmpurile cu atenție și corectitudine.  <b>Datele dvs. vor fi folosite pentru obținerea unei autorizații electronice de călătorie (Electronic Travel Authorization – ETA-IL) în Israel. </b></p>
                <p>
                    <b>Fotografia pașaportului trebuie să fie clară</b> și să conțină toate cele 4 colțuri ale paginii cu fotografia și datele personale vizibile. Asigurați-vă că încărcați un fișier în format JPEG sau PNG, cu o dimensiune maximă de 10MB.
                </p>
            </div>

            <!-- Date Personale -->
            <h5 class="section-title"><i class="bi bi-person-fill me-2"></i>Date personale</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nume" class="form-label required-field">Nume</label>
                    <input type="text" class="form-control" id="nume" name="nume" 
                           value="<?php echo isset($_POST['nume']) ? htmlspecialchars($_POST['nume']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenume" class="form-label required-field">Prenume</label>
                    <input type="text" class="form-control" id="prenume" name="prenume" 
                           value="<?php echo isset($_POST['prenume']) ? htmlspecialchars($_POST['prenume']) : ''; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nume_mama" class="form-label required-field">Nume mamă</label>
                    <input type="text" class="form-control" id="nume_mama" name="nume_mama" 
                           value="<?php echo isset($_POST['nume_mama']) ? htmlspecialchars($_POST['nume_mama']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenume_mama" class="form-label required-field">Prenume mamă</label>
                    <input type="text" class="form-control" id="prenume_mama" name="prenume_mama" 
                           value="<?php echo isset($_POST['prenume_mama']) ? htmlspecialchars($_POST['prenume_mama']) : ''; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nume_tata" class="form-label required-field">Nume tată</label>
                    <input type="text" class="form-control" id="nume_tata" name="nume_tata" 
                           value="<?php echo isset($_POST['nume_tata']) ? htmlspecialchars($_POST['nume_tata']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prenume_tata" class="form-label required-field">Prenume tată</label>
                    <input type="text" class="form-control" id="prenume_tata" name="prenume_tata" 
                           value="<?php echo isset($_POST['prenume_tata']) ? htmlspecialchars($_POST['prenume_tata']) : ''; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="data_nasterii" class="form-label required-field">Data nașterii</label>
                    <input type="date" class="form-control" id="data_nasterii" name="data_nasterii" 
                           value="<?php echo isset($_POST['data_nasterii']) ? $_POST['data_nasterii'] : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="stare_civila" class="form-label required-field">Stare civilă</label>
                    <select class="form-select" id="stare_civila" name="stare_civila" required>
                        <option value="">Selectează...</option>
                        <option value="casatorit" <?php echo (isset($_POST['stare_civila']) && $_POST['stare_civila'] == 'casatorit') ? 'selected' : ''; ?>>Căsătorit</option>
                        <option value="necasatorit" <?php echo (isset($_POST['stare_civila']) && $_POST['stare_civila'] == 'necasatorit') ? 'selected' : ''; ?>>Necăsătorit</option>
                        <option value="divortat" <?php echo (isset($_POST['stare_civila']) && $_POST['stare_civila'] == 'divortat') ? 'selected' : ''; ?>>Divorțat</option>
                        <option value="vaduv" <?php echo (isset($_POST['stare_civila']) && $_POST['stare_civila'] == 'vaduv') ? 'selected' : ''; ?>>Văduv</option>
                    </select>
                </div>
            </div>

            <!-- Date de contact -->
            <h5 class="section-title"><i class="bi bi-telephone-fill me-2"></i>Date de contact</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telefon" class="form-label required-field">Telefon</label>
                    <input type="tel" class="form-control" id="telefon" name="telefon" 
                           value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label required-field">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tara_domiciliu" class="form-label required-field">Țara domiciliu</label>
                    <input type="text" class="form-control" id="tara_domiciliu" name="tara_domiciliu" 
                           value="<?php echo isset($_POST['tara_domiciliu']) ? htmlspecialchars($_POST['tara_domiciliu']) : ''; ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="oras_domiciliu" class="form-label required-field">Oraș domiciliu</label>
                    <input type="text" class="form-control" id="oras_domiciliu" name="oras_domiciliu" 
                           value="<?php echo isset($_POST['oras_domiciliu']) ? htmlspecialchars($_POST['oras_domiciliu']) : ''; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="telefon_persoana_apropiata" class="form-label required-field">Telefon persoană apropiată (urgență)</label>
                <input type="tel" class="form-control" id="telefon_persoana_apropiata" name="telefon_persoana_apropiata" 
                       value="<?php echo isset($_POST['telefon_persoana_apropiata']) ? htmlspecialchars($_POST['telefon_persoana_apropiata']) : ''; ?>" required>
            </div>

            <!-- Informații Profesionale -->
            <h5 class="section-title"><i class="bi bi-briefcase-fill me-2"></i>Informații profesionale</h5>

            <div class="mb-3">
                <label for="ocupatie" class="form-label required-field">Ocupație (dacă nu aveți scrieți "fără ocupație")</label>
                <input type="text" class="form-control" id="ocupatie" name="ocupatie" 
                       value="<?php echo isset($_POST['ocupatie']) ? htmlspecialchars($_POST['ocupatie']) : ''; ?>" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nume_angajator" class="form-label">Nume angajator</label>
                    <input type="text" class="form-control" id="nume_angajator" name="nume_angajator" 
                           value="<?php echo isset($_POST['nume_angajator']) ? htmlspecialchars($_POST['nume_angajator']) : ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="telefon_angajator" class="form-label">Telefon angajator</label>
                    <input type="tel" class="form-control" id="telefon_angajator" name="telefon_angajator" 
                           value="<?php echo isset($_POST['telefon_angajator']) ? htmlspecialchars($_POST['telefon_angajator']) : ''; ?>">
                </div>
            </div>

            <!-- Informații Israel și Vize -->
            <h5 class="section-title"><i class="bi bi-flag-fill me-2"></i>Informații referitoare la Israel</h5>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ultima_vizita_israel" class="form-label">În ce an ați fost ultima dată în Israel?</label>
                    <select class="form-select" id="ultima_vizita_israel" name="ultima_vizita_israel">
                        <option value="Nu am fost" <?php echo (!isset($_POST['ultima_vizita_israel']) || $_POST['ultima_vizita_israel'] == 'Nu am fost') ? 'selected' : ''; ?>>Nu am fost</option>
                        <?php for($y = 2026; $y >= 1960; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo (isset($_POST['ultima_vizita_israel']) && $_POST['ultima_vizita_israel'] == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="aplicat_viza" class="form-label">Ați aplicat pentru viză de lucru, locuire sau studiu în Israel?</label>
                    <select class="form-select" id="aplicat_viza" name="aplicat_viza">
                        <option value="Nu" <?php echo (isset($_POST['aplicat_viza']) && $_POST['aplicat_viza'] == 'Nu') ? 'selected' : 'selected'; ?>>Nu</option>
                        <option value="Da" <?php echo (isset($_POST['aplicat_viza']) && $_POST['aplicat_viza'] == 'Da') ? 'selected' : ''; ?>>Da</option>
                    </select>
                </div>
            </div>

            <!-- Documente și plată -->
            <h5 class="section-title"><i class="bi bi-file-earmark-text-fill me-2"></i>Documente și plată</h5>

            <div class="mb-3">
                <label class="form-label required-field">Încarcă Copie Pașaport (PDF, JPEG, PNG - max 10MB)</label>
                <div class="input-group">
                    <label class="btn btn-outline-secondary mb-0" for="upload_pasaport">
                        <i class="bi bi-upload me-1"></i>Alege fotografie
                    </label>
                    <input type="file" class="d-none" id="upload_pasaport" name="upload_pasaport" accept=".pdf,.jpg,.jpeg,.png">
                    <span class="form-control text-muted" id="file_name_display">Niciun fișier ales</span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="plata_euro" class="form-label">Total euro plătit până acum</label>
                    <input type="number" step="0.01" class="form-control" id="plata_euro" name="plata_euro" 
                           value="<?php echo isset($_POST['plata_euro']) ? $_POST['plata_euro'] : '0'; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="plata_dolari" class="form-label">Total dolari plătit până acum</label>
                    <input type="number" step="0.01" class="form-control" id="plata_dolari" name="plata_dolari" 
                           value="<?php echo isset($_POST['plata_dolari']) ? $_POST['plata_dolari'] : '0'; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cu_sau_fara_avion" class="form-label">Călătoriți cu avion cu grupul sau separat?</label>
                    <select class="form-select" id="cu_sau_fara_avion" name="cu_sau_fara_avion">
                        <option value="cu avion" <?php echo (isset($_POST['cu_sau_fara_avion']) && $_POST['cu_sau_fara_avion'] == 'cu grupul') ? 'selected' : 'selected'; ?>>Cu grupul</option>
                        <option value="fara avion" <?php echo (isset($_POST['cu_sau_fara_avion']) && $_POST['cu_sau_fara_avion'] == 'separat') ? 'selected' : ''; ?>>Separat</option>
                    </select>
                </div>
            </div>

            <!-- Informații medicale -->
            <h5 class="section-title"><i class="bi bi-heart-pulse-fill me-2"></i>Informații medicale</h5>

            <div class="mb-3">
                <label for="afectiuni_medicale" class="form-label">Afecțiuni medicale / alergii</label>
                <textarea class="form-control" id="afectiuni_medicale" name="afectiuni_medicale" rows="4" 
                          placeholder="Menționați orice afecțiuni medicale, alergii sau condiții de care ar trebui să fim la curent..."><?php echo isset($_POST['afectiuni_medicale']) ? htmlspecialchars($_POST['afectiuni_medicale']) : ''; ?></textarea>
            </div>

            <!-- Submit Button -->
            <div class="d-grid gap-2 mt-4">
                <button type="submit" name="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle-fill me-2"></i>Trimite înscriere
                </button>
            </div>

            <p class="text-center text-muted mt-3 mb-0">
                <small>Câmpurile marcate cu <span class="text-danger">*</span> sunt obligatorii</small>
            </p>

        </form>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('upload_pasaport').addEventListener('change', function() {
    var display = document.getElementById('file_name_display');
    display.textContent = this.files[0] ? this.files[0].name : 'Niciun fișier ales';
    display.classList.toggle('text-muted', !this.files[0]);
});
</script>
<script>
setTimeout(function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        if(alert.classList.contains('alert-success')) {
            setTimeout(function() {
                bsAlert.close();
            }, 5000);
        }
    });
}, 1000);
</script>

</body>
</html>
