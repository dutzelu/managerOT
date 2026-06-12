 <?php 
$titlu_pg ="Contract nou";
include "includes/header.php"; // Presupune includerea conexiunii la baza de date ($conn)

// Variabila pentru mesajul de succes, preluată din URL dacă a avut loc o redirecționare după inserare
$numar_contract_succes = $_GET['contract'] ?? null; 
$beneficiar_succes = $_GET['beneficiar_nume'] ?? null;

// --- LOGICA DE PROCESARE FORMULAR (POST) INCEPE AICI ---
if (isset($_POST['submit'])) {
    
    // 1. Preluarea și curățarea valorilor din formular
    // Nu folosim $_POST['numar'] deoarece îl vom genera automat
    $data_semnarii = $_POST['data_semnarii'];
    $continut      = $_POST['continut']; // Va fi curățat mai jos
    $sponsor      = $_POST['sponsor'];
    $beneficiar   = $_POST['beneficiar'];
    $suma         = $_POST['suma'];
    $link_act_doveditor = $_POST['link_act_doveditor'] ?? '';

    // Extragem anul din data semnării
    $year = date("Y", strtotime($data_semnarii));

    // 2. Determinăm numărul recurent pentru anul respectiv (funcție din includes/functii.php)
    $numar_contract = genereaza_numar_contract($conn, $year);

    // 3. Inserarea în baza de date
    // Folosim prepared statement pentru inserare
    $sql = "INSERT INTO contracte (numar, continut, sponsor, beneficiar, suma, data_semnarii, link_contract) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt_insert = $conn->prepare($sql);
    
    if ($stmt_insert) {
        // Legare parametri: s=string, s=string, s=string, s=string, s=string, s=string, s=string
        // ATENȚIE: suma este varchar în SQL, deci folosim 's'
        $stmt_insert->bind_param("sssssss", 
            $numar_contract, 
            $continut, // Dacă folosești TinyMCE, asigură-te că valoarea este curată sau folosește test_input()
            $sponsor, 
            $beneficiar, 
            $suma, 
            $data_semnarii,
            $link_act_doveditor
        );

        if ($stmt_insert->execute()) {
            // Redirecționare PRG (Post-Redirect-Get) cu mesaj de succes
            header("Location: adauga-contract.php?contract=" . urlencode($numar_contract) . "&beneficiar_nume=" . urlencode($beneficiar));
            exit();
        } else {
            // Eroare la execuție
            echo "<div class='alert alert-danger'>Eroare la inserare: " . htmlspecialchars($stmt_insert->error) . "</div>";
        }
        $stmt_insert->close();
    } else {
        // Eroare la pregătirea interogării
        echo "<div class='alert alert-danger'>Eroare la pregătirea interogării: " . htmlspecialchars($conn->error) . "</div>";
    }
}
// --- LOGICA DE PROCESARE FORMULAR (POST) SE TERMINA AICI ---

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-file-earmark-plus me-2"></i> Adaugă Contract Nou
        </h2>
        
        <a href="contracte.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă
        </a>
    </div>

    <?php if (!empty($numar_contract_succes) && !empty($beneficiar_succes)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i> 
            Contractul **"<?php echo htmlspecialchars($numar_contract_succes); ?>"** pentru **"<?php echo htmlspecialchars($beneficiar_succes); ?>"** a fost introdus cu succes!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detalii Contract</h5>
        </div>
        <div class="card-body">
            
            <form action="adauga-contract.php" method="post" enctype="multipart/form-data">
                
                <div class="row g-3">
                    
                    <div class="col-md-6">
                        <label for="data_semnarii" class="form-label fw-bold">Data Semnării:</label>
                        <input name="data_semnarii" id="data_semnarii" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="sponsor" class="form-label fw-bold">Sponsor:</label>
                        <input name="sponsor" id="sponsor" type="text" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="beneficiar" class="form-label fw-bold">Beneficiar:</label>
                        <input name="beneficiar" id="beneficiar" type="text" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="suma" class="form-label fw-bold">Sumă (Lei):</label>
                        <input name="suma" id="suma" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="link_act_doveditor" class="form-label fw-bold">Link Document Contract (URL/Drive):</label>
                        <input name="link_act_doveditor" id="link_act_doveditor" type="url" class="form-control" placeholder="http://">
                    </div>

                    <div class="col-12 mt-4">
                        <label for="continut" class="form-label fw-bold">Conținutul Contractului:</label>
                        <textarea name="continut" id="continut" class="form-control" rows="10" placeholder="Introduceți clauzele contractului..."></textarea>
                    </div>
                    
                    <div class="col-12 mt-4 text-center">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-save-fill me-2"></i> Salvează Contractul
                        </button>
                    </div>

                </div>
            </form>
            
        </div>
    </div>
</div>

<?php 
include "includes/footer.php";
?>