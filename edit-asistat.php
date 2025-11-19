<?php
$titlu_pg ="Editează Asistat Social";
include "header.php"; // Include conexiunea la baza de date ($conn)

$success_message = '';
$error_message = '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// --- 1. PROCESARE CERERE DE ȘTERGERE (MUTATĂ LA ÎNCEPUT) ---
// Această secțiune este prima care se execută, astfel încât să nu fie blocată de validarea ID-ului principal.
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    $stmt_delete = $conn->prepare("DELETE FROM asistati_social WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    
    if ($stmt_delete->execute()) {
        $stmt_delete->close();
        // Redirecționează către lista de asistati după ștergere
        header("Location: asistati.php?succes_del=" . urlencode($delete_id));
        exit();
    } else {
        $error_message = "Eroare la ștergerea asistatului: " . $stmt_delete->error;
        $stmt_delete->close();
        // Dacă ștergerea eșuează, reîncărcăm pagina asistatului care nu a putut fi șters
        $id = $delete_id; 
    }
}

// --- 2. VALIDARE ID PRINCIPAL ---
if (empty($id) || !is_numeric($id)) {
    echo '<div class="alert alert-danger mt-4 container">ID-ul asistatului lipsește sau este invalid.</div>';
    include "footer.php";
    exit();
}

// --- 3. PROCESARE FORMULAR DE ACTUALIZARE ---
if (isset($_POST['submit'])) {
    $id_update = (int)$_POST['id'];
    
    // Preluare și sanitizare date
    $nume           = trim($_POST['nume']);
    $prenume        = trim($_POST['prenume']);
    $cnp            = trim($_POST['cnp']);
    $serie_nr_ci    = trim($_POST['serie_nr_ci']);
    $data_nasterii  = trim($_POST['data_nasterii']);
    $sex            = trim($_POST['sex']);
    $adresa_completa= trim($_POST['adresa_completa']);
    $localitate     = trim($_POST['localitate']);
    $judet          = trim($_POST['judet']);
    $telefon        = trim($_POST['telefon']);
    $stare_civila   = trim($_POST['stare_civila']);
    $nr_copii       = (int)$_POST['nr_copii'];
    $descriere      = $_POST['descriere']; // Lăsăm TinyMCE content ca HTML
    $contract_sponsorizare = trim($_POST['contract_sponsorizare']);
    $link_contract  = trim($_POST['link_contract']);
    $link_ci_existent = trim($_POST['link_ci_existent']); 
    $link_ci = $link_ci_existent; 
    
    // --- LOGICA PENTRU UPLOAD NOU COPIE CI (Dacă este cazul) ---
    if (isset($_FILES['copiebuletin']) && $_FILES['copiebuletin']['error'] == 0 && $_FILES['copiebuletin']['size'] > 0) {
        $error_message .= " *Notă: Logica de upload a noului fișier CI trebuie implementată aici. ";
        // AICI SE VA FACE UPLOAD-ul ȘI SE VA ACTUALIZA $link_ci
    }

    // Interogarea UPDATE (cu Prepared Statements)
    $sql_update = "UPDATE asistati_social SET 
                    nume = ?, prenume = ?, cnp = ?, serie_nr_ci = ?, data_nasterii = ?, sex = ?, 
                    adresa_completa = ?, localitate = ?, judet = ?, telefon = ?, 
                    stare_civila = ?, nr_copii = ?, descriere = ?, contract_sponsorizare = ?, 
                    link_contract = ?, link_ci = ?
                   WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    
    if ($stmt_update === false) {
        $error_message = "Eroare la pregătirea interogării UPDATE: " . $conn->error;
    } else {
        $stmt_update->bind_param("sssssssssssissssi", 
            $nume, $prenume, $cnp, $serie_nr_ci, $data_nasterii, $sex, 
            $adresa_completa, $localitate, $judet, $telefon, 
            $stare_civila, $nr_copii, $descriere, $contract_sponsorizare, 
            $link_contract, $link_ci, $id_update
        );
        
        if ($stmt_update->execute()) {
            $success_message = "Datele pentru **" . htmlspecialchars($nume . ' ' . $prenume) . "** au fost actualizate cu succes!";
            // Important: Reîncărcăm datele de afișat după un UPDATE reușit
        } else {
            $error_message = "Eroare la actualizarea asistatului: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}


// --- 4. EXTRAGEREA DATELOR ASISTATULUI (după UPDATE sau DELETE eșuat) ---
$stmt_select = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows === 0) {
    // Mesajul de aici se va afișa doar dacă asistantul nu mai există în baza de date
    echo '<div class="alert alert-danger mt-4 container">Asistatul cu ID-ul ' . htmlspecialchars($id) . ' nu a fost găsit.</div>';
    include "footer.php";
    exit();
}

$asistat = $result_select->fetch_assoc();
$stmt_select->close();


// --- 5. VARIABILE PENTRU POPULAREA FORMULARULUI ---

$nume = $asistat['nume'] ?? '';
$prenume = $asistat['prenume'] ?? '';
$cnp = $asistat['cnp'] ?? '';
$serie_nr_ci = $asistat['serie_nr_ci'] ?? '';
$data_nasterii = $asistat['data_nasterii'] ?? ''; 
$sex = $asistat['sex'] ?? '';
$adresa_completa = $asistat['adresa_completa'] ?? '';
$localitate = $asistat['localitate'] ?? '';
$judet = $asistat['judet'] ?? '';
$telefon = $asistat['telefon'] ?? '';
$stare_civila = $asistat['stare_civila'] ?? '';
$nr_copii = $asistat['nr_copii'] ?? 0;
// ATENȚIE: $descriere nu mai folosește htmlspecialchars() la citire din baza de date
$descriere = $asistat['descriere'] ?? ''; 
$contract_sponsorizare = $asistat['contract_sponsorizare'] ?? 'nu';
$link_contract = $asistat['link_contract'] ?? '';
$link_ci = $asistat['link_ci'] ?? '';


// Formatarea datei
$data_nasterii_formatata = !empty($data_nasterii) ? date('Y-m-d', strtotime($data_nasterii)) : '';

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-person-fill-gear me-2"></i> Editează Asistat Social: <?php echo htmlspecialchars($nume . ' ' . $prenume); ?>
        </h2>
        
        <div class="btn-group" role="group">
             <a href="asistati.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Înapoi
            </a>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash me-1"></i> Șterge
            </button>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detalii Asistat ID: <?php echo htmlspecialchars($id); ?></h5>
        </div>
        <div class="card-body">
            
            <form action="edit-asistat.php?id=<?php echo htmlspecialchars($id); ?>" method="post" enctype="multipart/form-data">
                
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="link_ci_existent" value="<?php echo htmlspecialchars($link_ci); ?>">
                
                <div class="row g-4">
                    
                    <div class="col-12">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-person-badge me-2"></i> Date de Identificare</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nume" class="form-label fw-bold">Nume:</label>
                        <input name="nume" id="nume" type="text" class="form-control" value="<?php echo htmlspecialchars($nume); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="prenume" class="form-label fw-bold">Prenume:</label>
                        <input name="prenume" id="prenume" type="text" class="form-control" value="<?php echo htmlspecialchars($prenume); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cnp" class="form-label fw-bold">Cod Numeric Personal (CNP):</label>
                        <input name="cnp" id="cnp" type="text" class="form-control" value="<?php echo htmlspecialchars($cnp); ?>" maxlength="13">
                    </div>
                    <div class="col-md-6">
                        <label for="serie_nr_ci" class="form-label fw-bold">Serie și Număr CI:</label>
                        <input name="serie_nr_ci" id="serie_nr_ci" type="text" class="form-control" value="<?php echo htmlspecialchars($serie_nr_ci); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="sex" class="form-label fw-bold">Sex:</label>
                        <select name="sex" id="sex" class="form-select">
                            <option value="masculin" <?php echo ($sex == 'masculin') ? 'selected' : ''; ?>>Masculin</option>
                            <option value="feminin" <?php echo ($sex == 'feminin') ? 'selected' : ''; ?>>Feminin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nasterii" class="form-label fw-bold">Data Nașterii:</label>
                        <input name="data_nasterii" id="data_nasterii" type="date" class="form-control" value="<?php echo htmlspecialchars($data_nasterii_formatata); ?>">
                    </div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-geo-alt-fill me-2"></i> Adresă și Contact</h4>
                    </div>

                    <div class="col-12">
                        <label for="adresa_completa" class="form-label fw-bold">Adresă completă:</label>
                        <input name="adresa_completa" id="adresa_completa" type="text" class="form-control" value="<?php echo htmlspecialchars($adresa_completa); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="localitate" class="form-label fw-bold">Localitate:</label>
                        <input name="localitate" id="localitate" type="text" class="form-control" value="<?php echo htmlspecialchars($localitate); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="judet" class="form-label fw-bold">Județ:</label>
                        <input name="judet" id="judet" type="text" class="form-control" value="<?php echo htmlspecialchars($judet); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="telefon" class="form-label fw-bold">Telefon:</label>
                        <input name="telefon" id="telefon" type="tel" class="form-control" value="<?php echo htmlspecialchars($telefon); ?>">
                    </div>
                    <div class="col-md-6"></div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-house-door-fill me-2"></i> Situație Socială</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="stare_civila" class="form-label fw-bold">Stare civilă:</label>
                        <select name="stare_civila" id="stare_civila" class="form-select">
                            <option value="necasatorit" <?php echo ($stare_civila == 'necasatorit') ? 'selected' : ''; ?>>Necăsătorit/ă</option>
                            <option value="casatorit" <?php echo ($stare_civila == 'casatorit') ? 'selected' : ''; ?>>Căsătorit/ă</option>
                            <option value="divortat" <?php echo ($stare_civila == 'divortat') ? 'selected' : ''; ?>>Divorțat/ă</option>
                            <option value="vaduv" <?php echo ($stare_civila == 'vaduv') ? 'selected' : ''; ?>>Văduv/ă</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nr_copii" class="form-label fw-bold">Număr copii în întreținere:</label>
                        <input name="nr_copii" id="nr_copii" type="number" min="0" class="form-control" value="<?php echo htmlspecialchars($nr_copii); ?>">
                    </div>

                    <div class="col-12">
                        <label for="descriere" class="form-label fw-bold">Descriere situație personală:</label>
                        <textarea name="descriere" id="descriere" class="form-control" rows="10"><?php echo $descriere; ?></textarea>
                    </div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-file-earmark-text me-2"></i> Documente și Sponsorizare</h4>
                    </div>

                    <div class="col-md-6">
                        <label for="contract_sponsorizare" class="form-label fw-bold">Are contract de sponsorizare?</label>
                        <select name="contract_sponsorizare" id="contract_sponsorizare" class="form-select">
                            <option value="nu" <?php echo ($contract_sponsorizare == 'nu') ? 'selected' : ''; ?>>Nu</option>
                            <option value="da" <?php echo ($contract_sponsorizare == 'da') ? 'selected' : ''; ?>>Da</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="link_contract" class="form-label fw-bold">Link Contract Sponsorizare (URL/Drive):</label>
                        <input name="link_contract" id="link_contract" type="url" class="form-control" value="<?php echo htmlspecialchars($link_contract); ?>" placeholder="http://">
                        <?php if (!empty($link_contract)): ?>
                            <div class="form-text mt-2">
                                <i class="bi bi-paperclip me-1"></i> 
                                Link contract: <a href="<?php echo htmlspecialchars($link_contract); ?>" target="_blank" class="text-primary">Deschide documentul</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12">
                        <label for="copiebuletin" class="form-label fw-bold">Încarcă copia după buletin (CI):</label>
                        <input type="file" name="copiebuletin" id="copiebuletin" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">
                            Lăsați gol dacă nu doriți să actualizați fișierul CI.
                        </div>
                        <?php if (!empty($link_ci)): ?>
                            <div class="alert alert-warning mt-2 mb-0 p-2">
                                <i class="bi bi-file-earmark-person-fill me-1"></i> 
                                Fișier CI actual: <a href="<?php echo htmlspecialchars($link_ci); ?>" target="_blank" class="alert-link">Deschide copia CI</a>
                            </div>
                        <?php endif; ?>
                    </div>


                    <div class="col-12 mt-5 text-center">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-save-fill me-2"></i> Salvează Modificările
                        </button>
                    </div>

                </div>
            </form>
            
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmă Ștergerea</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Ești sigur că vrei să ștergi asistatul **<?php echo htmlspecialchars($nume . ' ' . $prenume); ?>** (ID: <?php echo htmlspecialchars($id); ?>)? Această acțiune este **ireversibilă** și va șterge toate datele asociate.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
        <form method="POST" action="edit-asistat.php">
            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($id); ?>">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash me-1"></i> Șterge Definitiv
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php 
include "footer.php";
?>