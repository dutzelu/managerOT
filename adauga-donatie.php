<?php

$titlu_pg = "Adaugă Donație";
include "includes/header.php";

// Functia test_input ar trebui sa fie definita in header.php sau intr-un fisier inclus de acesta.
// Asigurati-va ca aceasta functie exista.

// Initializare variabile pentru mesajul de succes
$donatie_id_asistat = null;
$suma_lei_succes = null;
$nume_beneficiar = '';
$prenume_beneficiar = '';
$succes_insert = false;
$upload_errors = array();

// --- LOGICA DE PROCESARE FORMULAR (POST) INCEPE AICI ---
if(isset ($_POST["submit"]) ) {
    
    // 1. Preluarea si curatarea datelor din formular
    $id_asistat = test_input($_POST['id_asistat']);
    $data = test_input($_POST['data']);
    $suma_lei = test_input($_POST['suma_lei']);
    $tip_donatie = test_input($_POST['tip_donatie']);
    $scop_donatie = test_input($_POST['scop_donatie']);
    $act_doveditor = test_input($_POST['act_doveditor']);
    $nr_act_doveditor = test_input($_POST['nr_act_doveditor']);
    $link_act = test_input($_POST['link_act']); // Preluare URL-ul din formular
 
    $anul = substr ($data, 0,4);
    $luna = substr ($data, 5,2);
    
    // Variabila care va retine link-ul final (URL-ul din formular sau calea fisierului incarcat)
    $final_link_act = $link_act; 

    // 2. LOGICA DE INCARCARE FISIER (daca un fisier a fost selectat)
    if(isset($_FILES['file_act']) && $_FILES['file_act']['error'] == 0){
        $file = $_FILES['file_act'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name,PATHINFO_EXTENSION));
        
        // Directorul unde se vor încarca actele doveditoare
        $target_dir = "donatii/" . $anul . '/' . $luna;
        
        if (!file_exists($target_dir)) {
            // Creeaza directorul recursiv
            if (!mkdir($target_dir, 0777, true)) {
                 $upload_errors[] = "Eroare la crearea directorului de incarcare.";
            }
        }
        
        // Extensiile permise (extinse conform formularului)
        $allowed_extensions = array("jpeg","jpg","png","pdf","docx");
        
        if(!in_array($file_ext,$allowed_extensions)){
            $upload_errors[] = "Extensie fisier nepermisa, te rog alege JPEG, PNG, PDF sau DOCX.";
        }
        
        // 5MB = 5 * 1024 * 1024 bytes
        if($file_size > 5242880) { 
            $upload_errors[] = 'Dimensiunea fisierului trebuie sa fie maxim 5 MB.';
        }
        
        // Daca nu exista erori de incarcare, muta fisierul
        if(empty($upload_errors)) {
            $unique_file_name = uniqid() . '_' . $file_name; // Asigura un nume unic
            $target_file_path = $target_dir . '/' . $unique_file_name;
            
            if(move_uploaded_file($file_tmp, $target_file_path)) {
                // Seteaza link-ul final la calea fisierului incarcat
                $final_link_act = $target_file_path; 
            } else {
                $upload_errors[] = "Eroare la mutarea fisierului incarcat.";
            }
        }
    } 
    
    // 3. INSERARE IN BAZA DE DATE (DOAR daca nu sunt erori de upload critice)
    if(empty($upload_errors)) {
        // ATENTIE: Am inlocuit 'link_ci' din codul vechi cu 'link_act' pentru a folosi numele campului din baza de date
        $query = "
            INSERT INTO donatii 
            (`id_asistat`, `suma_lei`, `tip_donatie`, `act_doveditor`, `nr_act_doveditor`, `link_act`, `scop_donatie`, `data`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?);
        ";
        
        // Foloseste prepared statement pentru securitate
        $stmt = $conn->prepare($query);

        if ($stmt) {
            // Legare parametri: i=integer, d=double, s=string
            $stmt->bind_param("idssssss", 
                $id_asistat, 
                $suma_lei, 
                $tip_donatie, 
                $act_doveditor, 
                $nr_act_doveditor, 
                $final_link_act, // Foloseste link-ul final (URL sau Cale Fisier)
                $scop_donatie, 
                $data
            );

            if ($stmt->execute()) {
                // Inserare reusita - setam variabilele pentru mesajul de succes
                $succes_insert = true;
                $donatie_id_asistat = $id_asistat;
                $suma_lei_succes = $suma_lei;
            } else {
                // Eroare la executia interogarii
                $upload_errors[] = "Eroare MySQL: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Eroare la pregatirea interogarii
            $upload_errors[] = "Eroare la pregatirea interogarii MySQL: " . $conn->error;
        }

    }

    // 4. Redirectionare sau afisare mesaj de succes/eroare
    if ($succes_insert) {
        // Redirectionare pentru a preveni re-trimiterea formularului (PRG pattern)
        // Am pastrat logica de redirectionare pentru a transmite succesul prin GET
        header('Location: adauga-donatie.php?donatiepentruid=' .  $id_asistat . '&suma=' . $suma_lei);
        exit();
    }
    // Daca nu s-a facut redirectionarea, inseamna ca sunt erori si vor fi afisate mai jos.
}
// --- LOGICA DE PROCESARE FORMULAR (POST) SE TERMINA AICI ---


// --- LOGICA DE PRELUARE DATE PENTRU AFISARE ---

// Preluare date pentru mesajul de succes de la redirectionarea POST
// Daca redirectionarea a reusit, aceste variabile vor fi setate.
$donatie_id_asistat = $_GET['donatiepentruid'] ?? null;
$suma_lei_succes = $_GET['suma'] ?? null;

// Interogare pentru a obține numele și prenumele beneficiarului (pentru mesajul de succes)
if (!empty($donatie_id_asistat) && is_numeric($donatie_id_asistat)) {
    // Folosim prepared statement si aici
    $stmt_succes = $conn->prepare("SELECT `nume`, `prenume` FROM `asistati_social` WHERE `id` = ?");
    if ($stmt_succes) {
        $stmt_succes->bind_param("i", $donatie_id_asistat);
        $stmt_succes->execute();
        $result_succes = $stmt_succes->get_result();
        
        if ($data1 = $result_succes->fetch_assoc()){
            $nume_beneficiar = $data1['nume'];
            $prenume_beneficiar = $data1['prenume'];
        }
        $stmt_succes->close();
    }
}


// Interogare pentru lista de asistați sociali (pentru dropdown)
$sql_asistati = "SELECT `id`,`nume`,`prenume` FROM `asistati_social` ORDER BY `nume` ASC, `prenume` ASC";
$rezultate_asistati = mysqli_query($conn, $sql_asistati);

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="container mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 text-primary">
            <i class="bi bi-wallet2 me-2"></i> Adaugă o nouă donație
        </h2>
        
        <a href="total-donatii.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la Lista Donațiilor
        </a>
    </div>

    <?php if (!empty($donatie_id_asistat) && !empty($nume_beneficiar)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i> 
            A fost introdusă cu succes donația de **<?php echo htmlspecialchars($suma_lei_succes); ?> lei** pentru **"<?php echo htmlspecialchars($nume_beneficiar . ' ' . $prenume_beneficiar); ?>"**
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($upload_errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" id="error-form">
            <h5 class="alert-heading"><i class="bi bi-exclamation-octagon-fill me-2"></i> Eroare la Salvarea Donației</h5>
            <ul class="mb-0">
                <?php foreach ($upload_errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form action="adauga-donatie.php" method="post" enctype="multipart/form-data" class="row g-3">
            
                <div class="col-md-6">
                    <label for="data_donatie" class="form-label fw-bold">Data Donației:</label>
                    <input name="data" id="data_donatie" type="date" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['data'] ?? date('Y-m-d')); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="id_asistat" class="form-label fw-bold">Donație pentru Asistat Social:</label>
                    <select name="id_asistat" id="id_asistat" class="form-select" required>
                        <option value="">-- Selectează beneficiarul --</option>
                        <?php
                        // Afișează opțiunile pentru asistații sociali
                        $selected_asistat = $_POST['id_asistat'] ?? null;
                        if ($rezultate_asistati && mysqli_num_rows($rezultate_asistati) > 0) {
                            while ($data = mysqli_fetch_assoc($rezultate_asistati)) {
                                $nume_complet = htmlspecialchars($data['nume'] . ' ' . $data['prenume']);
                                $id_asistat = htmlspecialchars($data['id']);
                                $selected = ($id_asistat == $selected_asistat) ? 'selected' : '';
                                echo "<option value=\"{$id_asistat}\" {$selected}>{$nume_complet}</option>";
                            }
                            // Resetam pointer-ul daca mai e nevoie de el, desi nu e necesar aici
                            mysqli_data_seek($rezultate_asistati, 0); 
                            mysqli_free_result($rezultate_asistati);
                        } else {
                             echo "<option value=\"\" disabled>Nu există asistați sociali în baza de date.</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="suma_lei" class="form-label fw-bold">Sumă (lei):</label>
                    <input name="suma_lei" id="suma_lei" type="number" step="0.01" min="0.01" class="form-control" placeholder="Ex: 50.00" 
                           value="<?php echo htmlspecialchars($_POST['suma_lei'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="tip_donatie" class="form-label fw-bold">Tip Donație:</label>
                    <select name="tip_donatie" id="tip_donatie" class="form-select" required>
                        <option value="">-- Selectează tipul --</option>
                        <?php $selected_tip = $_POST['tip_donatie'] ?? ''; ?>
                        <option value="cash" <?php echo ($selected_tip == 'cash') ? 'selected' : ''; ?>>cash</option>
                        <option value="produse" <?php echo ($selected_tip == 'produse') ? 'selected' : ''; ?>>produse</option>
                        <option value="cont" <?php echo ($selected_tip == 'cont') ? 'selected' : ''; ?>>cont</option>
                        <option value="WU" <?php echo ($selected_tip == 'WU') ? 'selected' : ''; ?>>WU</option>
                        <option value="Posta Romana" <?php echo ($selected_tip == 'Posta Romana') ? 'selected' : ''; ?>>Poșta Romana</option>
                        <option value="servicii medicale" <?php echo ($selected_tip == 'servicii medicale') ? 'selected' : ''; ?>>servicii medicale</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label for="scop_donatie" class="form-label fw-bold">Scopul Donației:</label>
                    <input name="scop_donatie" id="scop_donatie" type="text" class="form-control" 
                           placeholder="Ex: Chirie ianuarie, Medicamente, Haine, etc." 
                           value="<?php echo htmlspecialchars($_POST['scop_donatie'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label for="act_doveditor" class="form-label fw-bold">Act Doveditor:</label>
                    <select name="act_doveditor" id="act_doveditor" class="form-select" required>
                        <option value="">-- Selectează tipul de act --</option>
                        <?php $selected_act = $_POST['act_doveditor'] ?? ''; ?>
                        <option value="factura" <?php echo ($selected_act == 'factura') ? 'selected' : ''; ?>>Factură + Chitanță</option>
                        <option value="extras de cont" <?php echo ($selected_act == 'extras de cont') ? 'selected' : ''; ?>>Extras de Cont</option>
                        <option value="bon" <?php echo ($selected_act == 'bon') ? 'selected' : ''; ?>>Bon Fiscal</option>
                        <option value="proces-verbal" <?php echo ($selected_act == 'proces-verbal') ? 'selected' : ''; ?>>Proces Verbal</option>
                        <option value="chitanta" <?php echo ($selected_act == 'chitanta') ? 'selected' : ''; ?>>Chitanță</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="nr_act_doveditor" class="form-label fw-bold">Nr. Act Doveditor:</label>
                    <input name="nr_act_doveditor" id="nr_act_doveditor" type="text" class="form-control" placeholder="Număr act (Ex: Seria + Nr. chitanță/factură)"
                           value="<?php echo htmlspecialchars($_POST['nr_act_doveditor'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="link_act" class="form-label fw-bold">Link Act Doveditor:</label>
                    <input name="link_act" id="link_act" type="url" class="form-control" placeholder="http:// sau link Google Drive"
                           value="<?php echo htmlspecialchars($_POST['link_act'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label for="file_act" class="form-label fw-bold">Încarcă Act Doveditor (Fișier):</label>
                    <input type="file" name="file_act" id="file_act" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.docx">
                    <div class="form-text">
                        Opțional: Dacă nu ai un link, încarcă fișierul. Se acceptă PDF, imagini sau DOCX (max 5 MB).
                    </div>
                </div>

                <div class="col-12 mt-4 text-center">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-check-circle-fill me-2"></i> Salvează Donația
                    </button>
                </div>

            </form>
            
        </div>
    </div>
</div>

<?php 
include "includes/footer.php";
?>