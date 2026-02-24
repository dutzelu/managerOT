<?php
$titlu_pg = "Editează Contract";
include "includes/header.php"; // Include conexiunea la baza de date ($conn)

// --- 1. PROCESARE FORMULAR DE ACTUALIZARE ---
$success_message = '';
$error_message = '';

if (isset($_POST['submit'])) {
    // Preluare și sanitizare date din formular
    $id_update      = (int)$_POST['id'];
    $numar          = trim($_POST['numar']);
    $data_semnarii  = trim($_POST['data_semnarii']);
    $continut       = $_POST['continut']; // TinyMCE content
    $sponsor        = trim($_POST['sponsor']);
    $beneficiar     = trim($_POST['beneficiar']);
    $suma           = (float)$_POST['suma'];
    $link_contract = trim($_POST['link_contract']);
    
    // De asemenea, se preia link-ul existent din câmpul ascuns pentru cazul în care se trimite fișierul.
    // (Părți din logica de upload de fișiere sunt omise aici, dar câmpul este inclus)
    
    // Interogarea UPDATE (cu Prepared Statements)
    $sql_update = "UPDATE contracte SET 
                    numar = ?, 
                    data_semnarii = ?, 
                    continut = ?, 
                    sponsor = ?, 
                    beneficiar = ?, 
                    suma = ?, 
                    link_contract = ? 
                   WHERE id = ?";
    
    $stmt_update = $conn->prepare($sql_update);
    
    if ($stmt_update === false) {
        $error_message = "Eroare la pregătirea interogării UPDATE: " . $conn->error;
    } else {
        $stmt_update->bind_param("sssssssi", 
            $numar, 
            $data_semnarii, 
            $continut, 
            $sponsor, 
            $beneficiar, 
            $suma, 
            $link_contract,
            $id_update
        );
        
        if ($stmt_update->execute()) {
            $success_message = "Contractul **" . htmlspecialchars($numar) . "** a fost actualizat cu succes!";
            // Redirecționarea (Post-Redirect-Get) va reîmprospăta datele de mai jos
        } else {
            $error_message = "Eroare la actualizarea contractului: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// --- 2. PROCESARE CERERE DE ȘTERGERE ---
if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    $stmt_delete = $conn->prepare("DELETE FROM contracte WHERE id = ?");
    $stmt_delete->bind_param("i", $delete_id);
    
    if ($stmt_delete->execute()) {
        $stmt_delete->close();
        // Redirecționează către lista de contracte după ștergere
        header("Location: contracte.php?succes_del=" . urlencode($delete_id));
        exit();
    } else {
        $error_message = "Eroare la ștergerea contractului: " . $stmt_delete->error;
        $stmt_delete->close();
    }
}

// --- 3. EXTRAGEREA DATELOR CONTRACTULUI (După Update sau la prima încărcare) ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
} else if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    // Dacă formularul a fost trimis, ID-ul vine din POST
     $id = (int)$_POST['id'];
} else {
    echo '<div class="alert alert-danger mt-4 container">ID-ul contractului lipsește sau este invalid.</div>';
    include "includes/footer.php";
    exit();
}

$stmt_select = $conn->prepare("SELECT * FROM contracte WHERE id = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows === 0) {
    echo '<div class="alert alert-danger mt-4 container">Contractul cu ID-ul ' . htmlspecialchars($id) . ' nu a fost găsit.</div>';
    include "includes/footer.php";
    exit();
}

$contract = $result_select->fetch_assoc();
$stmt_select->close();

// --- VARIABILE PENTRU POPULAREA FORMULARULUI ---
$data_semnarii_formatata = $contract['data_semnarii'] ? date('Y-m-d', strtotime($contract['data_semnarii'])) : '';
$suma_afisata = number_format($contract['suma'] ?? 0, 2, '.', '');
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
            
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-file-earmark-diff me-2"></i> Editează Contractul #<?php echo htmlspecialchars($contract['numar']); ?>
        </h2>
        
        <div class="btn-group" role="group">
             <a href="contracte.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă
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
            <h5 class="mb-0">Detalii Contract ID: <?php echo htmlspecialchars($id); ?></h5>
        </div>
        <div class="card-body">
            
            <form action="edit-contract.php?id=<?php echo htmlspecialchars($id); ?>" method="post" enctype="multipart/form-data">
                
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
                
                <div class="row g-3">
                    
                    <div class="col-md-6">
                        <label for="numar" class="form-label fw-bold">Număr Contract:</label>
                        <input name="numar" id="numar" type="text" class="form-control" value="<?php echo htmlspecialchars($contract['numar'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="data_semnarii" class="form-label fw-bold">Data Semnării:</label>
                        <input name="data_semnarii" id="data_semnarii" type="date" class="form-control" value="<?php echo htmlspecialchars($data_semnarii_formatata); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="sponsor" class="form-label fw-bold">Sponsor:</label>
                        <input name="sponsor" id="sponsor" type="text" class="form-control" value="<?php echo htmlspecialchars($contract['sponsor'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="beneficiar" class="form-label fw-bold">Beneficiar:</label>
                        <input name="beneficiar" id="beneficiar" type="text" class="form-control" value="<?php echo htmlspecialchars($contract['beneficiar'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="suma" class="form-label fw-bold">Sumă (Lei):</label>
                        <input name="suma" id="suma" type="number" step="0.01" min="0" class="form-control" value="<?php echo $suma_afisata; ?>" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="link_contract" class="form-label fw-bold">Link Document Contract (URL/Drive):</label>
                        <input name="link_contract" id="link_contract" type="url" class="form-control" value="<?php echo htmlspecialchars($contract['link_contract'] ?? ''); ?>" placeholder="http://">
                        <?php if (!empty($contract['link_contract'])): ?>
                            <div class="form-text mt-2">
                                <i class="bi bi-paperclip me-1"></i> 
                                Link actual: <a href="<?php echo htmlspecialchars($contract['link_contract']); ?>" target="_blank" class="text-primary">Deschide documentul</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <label for="contract_file" class="form-label fw-bold">Încarcă Fișier Contract (.pdf, .docx):</label>
                        <input type="file" name="contract_file" id="contract_file" class="form-control" accept=".pdf,.docx">
                        <div class="form-text">
                            Lăsați gol dacă nu doriți să actualizați fișierul.
                        </div>
                    </div>


                    <div class="col-12 mt-4">
                        <label for="continut" class="form-label fw-bold">Conținutul Contractului (Folosește editorul TinyMCE):</label>
                        <textarea name="continut" id="continut" class="form-control" rows="10" required><?php echo htmlspecialchars($contract['continut'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="col-12 mt-4 text-center">
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
        Ești sigur că vrei să ștergi contractul **#<?php echo htmlspecialchars($contract['numar']); ?>** (ID: <?php echo htmlspecialchars($id); ?>)? Această acțiune este **ireversibilă**.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anulează</button>
        <form method="POST" action="edit-contract.php?id=<?php echo htmlspecialchars($id); ?>">
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
include "includes/footer.php";
?>