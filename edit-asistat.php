<?php
$titlu_pg ="Editează Asistat Social";
include "header.php"; 

$success_message = '';
$error_message = '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// --- FUNCȚIE PENTRU CALCUL DATE DIN CNP ---
function extrageDateCNP($cnp) {
    if (strlen($cnp) < 7) return ['data' => 'CNP Invalid', 'sex' => 'N/A'];
    
    $s = $cnp[0];
    $aa = substr($cnp, 1, 2);
    $ll = substr($cnp, 3, 2);
    $zz = substr($cnp, 5, 2);

    // Logica sex conform cerinței: 1-fată, 2-băiat, 5-băiat, 6-fată
    $sex = "Necunoscut";
    if ($s == '1' || $s == '6') $sex = "Feminin (Fată)";
    if ($s == '2' || $s == '5') $sex = "Masculin (Băiat)";

    // Logica secol: 1,2 -> 1900 | 5,6 -> 2000
    $secol = ($s == '1' || $s == '2') ? "19" : "20";
    $data_nasterii = "$zz.$ll.$secol$aa";

    return ['data' => $data_nasterii, 'sex' => $sex];
}

// --- 1. PROCESARE ȘTERGERE ---
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt_del = $conn->prepare("DELETE FROM asistati_social WHERE id = ?");
    $stmt_del->bind_param("i", $delete_id);
    if ($stmt_del->execute()) {
        header("Location: asistati.php?succes_del=$delete_id");
        exit();
    }
}

// --- 2. VALIDARE ID ---
if (empty($id) || !is_numeric($id)) {
    echo '<div class="alert alert-danger mt-4 container">ID invalid sau inexistent.</div>';
    include "footer.php"; exit();
}

// --- 3. PROCESARE UPDATE ---
if (isset($_POST['submit'])) {
    $id_upd = (int)$_POST['id'];
    
    // Preluare date (FĂRĂ sex și data_nasterii care nu sunt în DB)
    $nume           = trim($_POST['nume']);
    $prenume        = trim($_POST['prenume']);
    $cnp            = trim($_POST['cnp']);
    $serie_nr_ci    = trim($_POST['serie_nr_ci']);
    $adresa_completa= trim($_POST['adresa_completa']);
    $localitate     = trim($_POST['localitate']);
    $judet          = trim($_POST['judet']);
    $telefon        = trim($_POST['telefon']);
    $stare_civila   = trim($_POST['stare_civila']);
    $nr_copii       = (int)$_POST['nr_copii'];
    $desc_scurta    = trim($_POST['descriere_scurta']);
    $descriere      = $_POST['descriere']; 
    $cont_spons     = trim($_POST['contract_sponsorizare']);
    $link_cont      = trim($_POST['link_contract']);
    $link_ci        = trim($_POST['link_ci_existent']); 

    // Interogarea SQL corectată conform structurii tale reale
    $sql = "UPDATE asistati_social SET 
                nume=?, prenume=?, cnp=?, serie_nr_ci=?, adresa_completa=?, 
                localitate=?, judet=?, telefon=?, stare_civila=?, nr_copii=?, 
                descriere_scurta=?, descriere=?, contract_sponsorizare=?, 
                link_contract=?, link_ci=? 
            WHERE id=?";
    
    $stmt = $conn->prepare($sql);
    // Tipuri: 9 s, 1 i, 5 s, 1 i
    $stmt->bind_param("sssssssssisssssi", 
        $nume, $prenume, $cnp, $serie_nr_ci, $adresa_completa, 
        $localitate, $judet, $telefon, $stare_civila, $nr_copii, 
        $desc_scurta, $descriere, $cont_spons, $link_cont, $link_ci, $id_upd
    );
    
    if ($stmt->execute()) {
        $success_message = "Datele au fost salvate cu succes!";
    } else {
        $error_message = "Eroare la salvare: " . $conn->error;
    }
}

// --- 4. EXTRAGERE DATE CURENTE ---
$stmt_get = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$res = $stmt_get->get_result();
$asistat = $res->fetch_assoc();

if (!$asistat) { echo "Asistatul nu există."; include "footer.php"; exit(); }

// Calculăm datele din CNP pentru afișare
$info = extrageDateCNP($asistat['cnp']);
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-person-fill-gear me-2"></i> Editare Asistat</h2>
                <div class="btn-group">
                    <a href="asistati.php" class="btn btn-outline-secondary btn-sm">Înapoi</a>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">Șterge</button>
                </div>
            </div>

            <?php if($success_message) echo "<div class='alert alert-success shadow-sm'>$success_message</div>"; ?>
            <?php if($error_message) echo "<div class='alert alert-danger shadow-sm'>$error_message</div>"; ?>

            <form action="edit-asistat.php?id=<?php echo $id; ?>" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <input type="hidden" name="link_ci_existent" value="<?php echo $asistat['link_ci']; ?>">

                <div class="card shadow mb-4">
                    <div class="card-body">
                        
                        <h5 class="text-primary border-bottom pb-2 mb-3"><i class="bi bi-person-badge me-2"></i> Date de Identificare</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nume</label>
                                <input name="nume" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['nume']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prenume</label>
                                <input name="prenume" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['prenume']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">CNP</label>
                                <input name="cnp" type="text" class="form-control border-primary" value="<?php echo htmlspecialchars($asistat['cnp']); ?>" maxlength="13">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Data Nașterii (Auto-CNP)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $info['data']; ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Sex (Auto-CNP)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $info['sex']; ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Serie și Nr. CI</label>
                                <input name="serie_nr_ci" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['serie_nr_ci']); ?>">
                            </div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-geo-alt-fill me-2"></i> Adresă și Contact</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Adresă completă</label>
                                <input name="adresa_completa" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['adresa_completa']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Localitate</label>
                                <input name="localitate" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['localitate']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Județ</label>
                                <input name="judet" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['judet']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Telefon</label>
                                <input name="telefon" type="tel" class="form-control" value="<?php echo htmlspecialchars($asistat['telefon']); ?>">
                            </div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-house-door-fill me-2"></i> Situație Socială</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Stare civilă</label>
                                <select name="stare_civila" class="form-select">
                                    <option value="necasatorit" <?php if($asistat['stare_civila']=='necasatorit') echo 'selected'; ?>>Necăsătorit/ă</option>
                                    <option value="casatorit" <?php if($asistat['stare_civila']=='casatorit') echo 'selected'; ?>>Căsătorit/ă</option>
                                    <option value="divortat" <?php if($asistat['stare_civila']=='divortat') echo 'selected'; ?>>Divorțat/ă</option>
                                    <option value="vaduv" <?php if($asistat['stare_civila']=='vaduv') echo 'selected'; ?>>Văduv/ă</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Număr copii</label>
                                <input name="nr_copii" type="number" class="form-control" value="<?php echo $asistat['nr_copii']; ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Descriere scurtă (Titlu/Rezumat)</label>
                                <input name="descriere_scurta" type="text" class="form-control" value="<?php echo htmlspecialchars($asistat['descriere_scurta'] ?? ''); ?>" placeholder="Rezumatul situației...">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Descriere detaliată situație</label>
                                <textarea name="descriere" class="form-control" rows="6"><?php echo $asistat['descriere']; ?></textarea>
                            </div>
                        </div>

                        <h5 class="text-primary border-bottom pb-2 mb-3 mt-4"><i class="bi bi-file-earmark-text me-2"></i> Documente și Sponsorizare</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Contract sponsorizare?</label>
                                <select name="contract_sponsorizare" class="form-select">
                                    <option value="nu" <?php if($asistat['contract_sponsorizare']=='nu') echo 'selected'; ?>>Nu</option>
                                    <option value="da" <?php if($asistat['contract_sponsorizare']=='da') echo 'selected'; ?>>Da</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Link Contract (Cloud/Drive)</label>
                                <input name="link_contract" type="url" class="form-control" value="<?php echo htmlspecialchars($asistat['link_contract']); ?>">
                            </div>
                            <div class="col-12">
                                <?php if($asistat['link_ci']): ?>
                                    <div class="alert alert-light border small p-2 d-flex align-items-center">
                                        <i class="bi bi-paperclip me-2 fs-5"></i> 
                                        <span>Document CI existent: <a href="<?php echo $asistat['link_ci']; ?>" target="_blank">Deschide fișier</a></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg px-5 shadow-sm">
                                <i class="bi bi-save me-2"></i> Salvează Toate Modificările
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Atenție!</h5></div>
      <div class="modal-body">Sigur doriți să ștergeți definitiv acest profil?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
        <form method="POST">
            <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
            <button type="submit" class="btn btn-danger">Confirmă Ștergerea</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include "footer.php"; ?>