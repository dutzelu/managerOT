 <?php
$titlu_pg = "Editare donație";
// Asigură-te că fișierul header.php include legăturile CSS/JS pentru Bootstrap 5
include "includes/header.php";

?>


<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
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
        $upload_error = $_GET['upload_error'] ?? '';
        $persoana = ''; // Inițializăm variabila persoana care va fi afișată

        // Verificare dacă ID-ul este setat, altfel redirecționează sau afișează o eroare
        if (empty($id) || !is_numeric($id)) {
            echo '<div class="alert alert-danger container mt-5">ID-ul donației este invalid sau lipsește!</div>';
            include "includes/footer.php";
            exit;
        }

        // 2. Extragerea Datelor Donației din Baza de Date
        try {
            $stmt = $conn->prepare("SELECT `ID`, `id_asistat`, `suma_lei`, `tip_donatie`, `mod_acordare`, `act_doveditor`, `nr_act_doveditor`, `cont_beneficiar`, `numar_ordin_plata`, `sursa_fondurilor`, `link_act`, `proces_verbal`, `link_proces_verbal`, `scop_donatie`, `data`, `observatii_ajutor` FROM `donatii` WHERE `ID` = ?");
            
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
                include "includes/footer.php";
                exit;
            }

            // Alocarea variabilelor (folosind datele extrase)
            $id_asistat = $data['id_asistat'];
            $suma_lei = $data['suma_lei'];
            $tip_donatie = $data['tip_donatie'];
            $mod_acordare = $data['mod_acordare'];
            $act_doveditor = $data['act_doveditor'];
            $nr_act_doveditor = $data['nr_act_doveditor'];
            $cont_beneficiar = $data['cont_beneficiar'];
            $numar_ordin_plata = $data['numar_ordin_plata'];
            $sursa_fondurilor = $data['sursa_fondurilor'];
            $link_act = $data['link_act'];
            $proces_verbal = $data['proces_verbal'];
            $link_proces_verbal = $data['link_proces_verbal'];
            $scop_donatie = $data['scop_donatie'];
            $data_donatiei = $data['data'];
            $observatii_ajutor = $data['observatii_ajutor'];
            $atasamente_donatie = donatie_get_attachments($conn, (int)$id, $link_act);
            $poze_donatie = array_values(array_filter($atasamente_donatie, 'donatie_attachment_is_image'));
            $documente_donatie = array_values(array_filter($atasamente_donatie, function ($attachment) {
                return !donatie_attachment_is_image($attachment);
            }));
            
            
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
            include "includes/footer.php";
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

        if (!empty($upload_error)) {
            echo '<div id="error-form" class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo '<strong>Eroare upload:</strong> ' . htmlspecialchars($upload_error);
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
                <label for="modAcordareSelect" class="form-label">Mod acordare:</label>
                <select name="mod_acordare" id="modAcordareSelect" class="form-select">
                    <option value="">-- Selectează modul --</option>
                    <option <?php if ($mod_acordare == "transfer bancar") echo 'selected';?> value="transfer bancar">Transfer bancar</option>
                    <option <?php if ($mod_acordare == "numerar") echo 'selected';?> value="numerar">Numerar</option>
                    <option <?php if ($mod_acordare == "bunuri") echo 'selected';?> value="bunuri">Bunuri</option>
                    <option <?php if ($mod_acordare == "servicii") echo 'selected';?> value="servicii">Servicii</option>
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

            <div class="col-md-4">
                <label for="contBeneficiarInput" class="form-label">Cont beneficiar:</label>
                <input name="cont_beneficiar" id="contBeneficiarInput" type="text" class="form-control" value="<?php echo htmlspecialchars($cont_beneficiar); ?>">
            </div>

            <div class="col-md-4">
                <label for="ordinPlataInput" class="form-label">Număr ordin de plată:</label>
                <input name="numar_ordin_plata" id="ordinPlataInput" type="text" class="form-control" value="<?php echo htmlspecialchars($numar_ordin_plata); ?>">
            </div>

            <div class="col-md-4">
                <label for="sursaFonduriInput" class="form-label">Sursa fondurilor:</label>
                <input name="sursa_fondurilor" id="sursaFonduriInput" type="text" class="form-control" value="<?php echo htmlspecialchars($sursa_fondurilor); ?>">
            </div>

            <div class="col-12">
                <label for="linkActInput" class="form-label">Link act doveditor:</label>
                <input name="link_act" id="linkActInput" type="text" class="form-control" value="<?php echo htmlspecialchars($link_act); ?>" placeholder="Adresa URL completă sau calea către document">
            </div>

            <div class="col-12">
                <label for="incarcaActInput" class="form-label">Încarcă act doveditor:</label>
                <input type="file" name="act[]" id="incarcaActInput" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                <small class="form-text text-muted">Poți adăuga mai multe poze sau documente odată. Fișierele existente rămân atașate donației.</small>

                <?php if (!empty($poze_donatie)): ?>
                    <div class="donatie-gallery mt-3">
                        <?php foreach ($poze_donatie as $index_poza => $poza): ?>
                            <?php
                                $cale_poza = $poza['cale_fisier'];
                                $nume_poza = trim((string)($poza['nume_original'] ?? ''));
                                $titlu_poza = $nume_poza !== '' ? $nume_poza : 'Fotografie ' . ($index_poza + 1);
                            ?>
                            <button type="button" class="donatie-gallery__item" data-bs-toggle="modal" data-bs-target="#donatieGalleryModal" data-bs-slide-to="<?php echo (int)$index_poza; ?>" aria-label="Deschide fotografia <?php echo (int)($index_poza + 1); ?>">
                                <img src="<?php echo htmlspecialchars($cale_poza); ?>" alt="<?php echo htmlspecialchars($titlu_poza); ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($documente_donatie)): ?>
                    <div class="mt-3 small">
                        <span class="text-muted">Documente curente:</span>
                        <?php echo donatie_render_attachment_links($documente_donatie); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <label for="observatiiAjutorInput" class="form-label">Observații ajutor:</label>
                <textarea name="observatii_ajutor" id="observatiiAjutorInput" class="form-control" rows="3"><?php echo htmlspecialchars($observatii_ajutor); ?></textarea>
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

<?php if (!empty($poze_donatie)): ?>
<div class="modal fade" id="donatieGalleryModal" tabindex="-1" aria-labelledby="donatieGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title" id="donatieGalleryModalLabel">Fotografii act doveditor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Închide"></button>
            </div>
            <div class="modal-body p-0">
                <div id="donatieGalleryCarousel" class="carousel slide" data-bs-interval="false">
                    <div class="carousel-inner">
                        <?php foreach ($poze_donatie as $index_poza => $poza): ?>
                            <?php
                                $cale_poza = $poza['cale_fisier'];
                                $nume_poza = trim((string)($poza['nume_original'] ?? ''));
                                $titlu_poza = $nume_poza !== '' ? $nume_poza : 'Fotografie ' . ($index_poza + 1);
                            ?>
                            <div class="carousel-item <?php echo $index_poza === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($cale_poza); ?>" class="d-block w-100 donatie-gallery__full" alt="<?php echo htmlspecialchars($titlu_poza); ?>">
                                <div class="carousel-caption d-none d-md-block">
                                    <span class="badge bg-dark bg-opacity-75"><?php echo htmlspecialchars($titlu_poza); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (count($poze_donatie) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#donatieGalleryCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#donatieGalleryCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Următor</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('donatieGalleryModal');
    var carouselEl = document.getElementById('donatieGalleryCarousel');
    if (!modal || !carouselEl) {
        return;
    }

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var slideTo = trigger ? parseInt(trigger.getAttribute('data-bs-slide-to') || '0', 10) : 0;
        var carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, { interval: false });
        carousel.to(slideTo);
    });
});
</script>
<?php endif; ?>

</div> 
<?php 
// Notă: Această linie închide structura începută de header.php
// (col-9, row, container, wrapper, body, html)
include "includes/footer.php";
?>
