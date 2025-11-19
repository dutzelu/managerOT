 <?php
$titlu_pg = "Editare donație";
// Asigură-te că fișierul header.php include legăturile CSS/JS pentru Bootstrap 5
include "header.php";

?>


<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

    <div class="row">
        <?php
                // 1. Preluarea și Validarea Parametrilor din URL
        // Folosim operatorul de coalescență null (??) pentru a simplifica preluarea
        $id = $_GET['id'] ?? '';
        // Păstrăm $persoana din GET doar dacă este un mesaj de succes, altfel îl vom suprascrie.
        $persoana_get = $_GET['persoana'] ?? ''; 
        $succes = $_GET['succes'] ?? '';
        $persoana = ''; // Inițializăm variabila persoana care va fi afișată

        // Verificare dacă ID-ul este setat, altfel redirecționează sau afișează o eroare
        if (empty($id) || !is_numeric($id)) {
            echo '<div class="alert alert-danger container mt-5">ID-ul donației este invalid sau lipsește!</div>';
            include "footer.php";
            exit;
        }

        // 2. Extragerea Datelor Donației din Baza de Date
        try {
            $stmt = $conn->prepare("SELECT `ID`, `id_asistat`, `suma_lei`, `tip_donatie`, `act_doveditor`, `nr_act_doveditor`, `link_act`, `proces_verbal`, `link_proces_verbal`, `scop_donatie`, `data` FROM `donatii` WHERE `ID` = ?");
            
            if ($stmt === false) {
                throw new Exception("Eroare la pregătirea interogării: " . $conn->error);
            }
            
            $stmt->bind_param("i", $id); 
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();

            if (!$data) {
                echo '<div class="alert alert-warning container mt-5">Nu s-a găsit nicio donație cu ID-ul specificat.</div>';
                include "footer.php";
                exit;
            }

            // Alocarea variabilelor (folosind datele extrase)
            $id_asistat = $data['id_asistat'];
            $suma_lei = $data['suma_lei'];
            $tip_donatie = $data['tip_donatie'];
            $act_doveditor = $data['act_doveditor'];
            $nr_act_doveditor = $data['nr_act_doveditor'];
            $link_act = $data['link_act'];
            $proces_verbal = $data['proces_verbal'];
            $link_proces_verbal = $data['link_proces_verbal'];
            $scop_donatie = $data['scop_donatie'];
            $data_donatiei = $data['data'];
            
            
            // 3. FIX: Extragerea numelui beneficiarului (Asistat Social)
            if (!empty($id_asistat)) {
                $nume_beneficiar = '';
                $prenume_beneficiar = '';

                $stmt_persoana = $conn->prepare("SELECT `nume`, `prenume` FROM `asistati_social` WHERE `id` = ?");
                if ($stmt_persoana) {
                    $stmt_persoana->bind_param("i", $id_asistat);
                    $stmt_persoana->execute();
                    $result_persoana = $stmt_persoana->get_result();
                    if ($data_persoana = $result_persoana->fetch_assoc()) {
                        $nume_beneficiar = $data_persoana['nume'];
                        $prenume_beneficiar = $data_persoana['prenume'];
                    }
                    $stmt_persoana->close();
                }
                
                // Setăm variabila $persoana care va fi afișată în titlu
                $persoana = trim($nume_beneficiar . ' ' . $prenume_beneficiar);
            }
            
            // Dacă am primit un mesaj de succes prin GET, înseamnă că $persoana_get conține 
            // numele pentru mesajul de succes, îl vom folosi pe acela, altfel îl păstrăm pe cel din baza de date.
            if (!empty($persoana_get)) {
                $persoana = $persoana_get;
            }


        } catch (Exception $e) {
            echo '<div class="alert alert-danger container mt-5">Eroare Bază de Date: ' . $e->getMessage() . '</div>';
            include "footer.php";
            exit;
        }

        // 4. Afișarea Secțiunii de Succes și a Titlului

        echo '<div class="container">'; 
        // Notă: Structura ta include un <div> class="container" exterior în header.php. 
        // Acest container suplimentar nu e necesar, dar îl păstrez pentru compatibilitate.

        // Mesaj de succes
        if (!empty($succes)) {
            // Folosim o alertă Bootstrap modernă
            echo '<div id="success-form" class="alert alert-success alert-dismissible fade show" role="alert">';
            echo '<strong>Succes!</strong> Donația cu id "' . htmlspecialchars($id) . '" a persoanei ' . htmlspecialchars($persoana) . ' a fost modificată cu succes.';
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }

        // Titlul paginii (acum $persoana ar trebui să fie populată)
        echo '<h2 class="mb-4 text-primary">Editare donația nr. <span class="fw-bold text-danger">' . htmlspecialchars($id) . '</span> (' . htmlspecialchars($persoana) . ')</h2>';

        // 5. Structura Formularului în Card Bootstrap
        ?>



<div class="card shadow-lg">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Detalii Modificare Donație</h5>
    </div>
    <div class="card-body">
        <form action="update-edit-donatie.php?id=<?php echo htmlspecialchars($id) . '&persoana=' . urlencode($persoana);?>" method="post" enctype="multipart/form-data" class="row g-3">
            
            <div class="col-md-6">
                <label for="dataInput" class="form-label">Data:</label>
                <input name="data" id="dataInput" type="date" class="form-control" value="<?php echo htmlspecialchars($data_donatiei); ?>">
            </div>

            <div class="col-md-6">
                <label for="sumaInput" class="form-label">Suma (RON):</label>
                <input name="suma_lei" id="sumaInput" type="number" class="form-control" value="<?php echo htmlspecialchars($suma_lei); ?>" step="0.01" min="0">
            </div>

            <div class="col-12">
                <label for="tipDonatieSelect" class="form-label">Tip donație:</label>
                <select name="tip_donatie" id="tipDonatieSelect" class="form-select">
                    <option <?php if ($tip_donatie == "cash") echo 'selected';?> value="cash">Bani cash</option>
                    <option <?php if ($tip_donatie == "cont") echo 'selected';?> value="cont">Transfer în Cont bancar</option>
                    <option <?php if ($tip_donatie == "Posta Romana") echo 'selected';?> value="Posta Romana">Transfer prin Poșta Română</option>
                    <option <?php if ($tip_donatie == "WU") echo 'selected';?> value="WU">Transfer prin Western Union</option>
                    <option <?php if ($tip_donatie == "produse") echo 'selected';?> value="produse">Produse materiale</option>          
                </select>
            </div>

            <div class="col-12">
                <label for="scopDonatieInput" class="form-label">Scop donație:</label>
                <input name="scop_donatie" id="scopDonatieInput" type="text" class="form-control" value="<?php echo htmlspecialchars($scop_donatie);?>" placeholder="Ex: Ajutor chirie și alimente">
            </div>

            <hr class="mt-4 mb-3"> <div class="col-md-6">
                <label for="actDoveditorSelect" class="form-label">Act doveditor:</label>
                <select name="act_doveditor" id="actDoveditorSelect" class="form-select">
                    <option <?php if ($act_doveditor == "factura") echo 'selected';?> value="factura">Factură + chitanță</option>
                    <option <?php if ($act_doveditor == "extras de cont") echo 'selected';?> value="extras de cont">Extras de cont</option>
                    <option <?php if ($act_doveditor == "bon") echo 'selected';?> value="bon">Bon fiscal</option>
                    <option <?php if ($act_doveditor == "proces-verbal") echo 'selected';?> value="proces-verbal">Proces verbal</option>          
                </select>
            </div>

            <div class="col-md-6">
                <label for="nrActDoveditorInput" class="form-label">Nr. act doveditor:</label>
                <input name="nr_act_doveditor" id="nrActDoveditorInput" type="text" class="form-control" value="<?php echo htmlspecialchars($nr_act_doveditor); ?>">
            </div>

            <div class="col-12">
                <label for="linkActInput" class="form-label">Link act doveditor:</label>
                <input name="link_act" id="linkActInput" type="text" class="form-control" value="<?php echo htmlspecialchars($link_act); ?>" placeholder="Adresa URL completă sau calea către document">
            </div>

            <div class="col-12">
                <label for="incarcaActInput" class="form-label">Încarcă act doveditor:</label>
                <input type="file" name="act" id="incarcaActInput" class="form-control">
                <?php if (!empty($link_act)): ?>
                    <small class="form-text text-muted">Document curent: <a href="<?php echo htmlspecialchars($link_act); ?>" target="_blank">Vizualizează</a></small>
                <?php endif; ?>
            </div>

            <hr class="mt-4 mb-3"> <h6 class="mt-3 text-secondary">Detalii Proces Verbal (Opțional)</h6>

            <div class="col-md-6">
                <label for="procesVerbalInput" class="form-label">Proces verbal (Descriere):</label>
                <input name="proces_verbal" id="procesVerbalInput" type="text" class="form-control" value="<?php echo htmlspecialchars($proces_verbal); ?>">
            </div>

            <div class="col-md-6">
                <label for="linkProcesVerbalInput" class="form-label">Link proces verbal:</label>
                <input name="link_proces_verbal" id="linkProcesVerbalInput" type="text" class="form-control" value="<?php echo htmlspecialchars($link_proces_verbal); ?>" placeholder="Adresa URL completă către procesul verbal">
            </div>

            <div class="col-12 mt-4">    
                <button class="btn btn-success btn-lg" name="submit">
                    <i class="bi bi-save"></i> Modifică Donația
                </button>
                <a href="lista-donatii.php" class="btn btn-outline-secondary btn-lg ms-2">Anulează</a>
            </div>
        </form>
    </div>
</div>

</div> 
<?php 
// Notă: Această linie închide structura începută de header.php
// (col-9, row, container, wrapper, body, html)
include "footer.php";
?>