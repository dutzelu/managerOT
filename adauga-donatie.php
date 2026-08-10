<?php
ob_start();

$titlu_pg = "Adaugă Donație";
include "includes/header.php";

$donatie_id_asistat = null;
$suma_lei_succes = null;
$nume_beneficiar = '';
$prenume_beneficiar = '';
$succes_insert = false;
$upload_errors = array();

if(isset($_POST["submit"])) {

    $id_asistat = test_input($_POST['id_asistat']);
    $data = test_input($_POST['data']);
    $suma_lei = test_input($_POST['suma_lei']);
    $tip_donatie = test_input($_POST['tip_donatie']);
    $mod_acordare = test_input($_POST['mod_acordare'] ?? '');
    $scop_donatie = test_input($_POST['scop_donatie']);
    $act_doveditor = test_input($_POST['act_doveditor']);
    $nr_act_doveditor = test_input($_POST['nr_act_doveditor']);
    $observatii_ajutor = test_input($_POST['observatii_ajutor'] ?? '');
    $link_act = test_input($_POST['link_act']);

    $final_link_act = $link_act;
    $uploaded_files = donatie_normalize_uploaded_files('file_act');
    $upload_errors = array_merge($upload_errors, donatie_validate_uploaded_files($uploaded_files));

    if(empty($upload_errors)) {
        $fisa_id = null;
        $stmt_fisa = $conn->prepare("SELECT id FROM fise_sociale WHERE beneficiar_id = ? ORDER BY id DESC LIMIT 1");
        if ($stmt_fisa) {
            $stmt_fisa->bind_param("i", $id_asistat);
            $stmt_fisa->execute();
            $result_fisa = $stmt_fisa->get_result();
            if ($row_fisa = $result_fisa->fetch_assoc()) {
                $fisa_id = $row_fisa['id'];
            }
            $stmt_fisa->close();
        }

        $query = "
            INSERT INTO donatii
            (`id_asistat`, `fisa_id`, `suma_lei`, `tip_donatie`, `mod_acordare`, `act_doveditor`, `nr_act_doveditor`, `link_act`, `scop_donatie`, `data`, `observatii_ajutor`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
        ";

        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("iidssssssss",
                $id_asistat,
                $fisa_id,
                $suma_lei,
                $tip_donatie,
                $mod_acordare,
                $act_doveditor,
                $nr_act_doveditor,
                $final_link_act,
                $scop_donatie,
                $data,
                $observatii_ajutor
            );

            if ($stmt->execute()) {
                $donatie_id = $stmt->insert_id;
                if (!empty($link_act)) {
                    donatie_add_external_attachment($conn, $donatie_id, $link_act);
                }

                $upload_result = donatie_upload_attachments($conn, $donatie_id, $data, 'file_act');
                $upload_errors = array_merge($upload_errors, $upload_result['errors']);
                if (!empty($upload_result['paths'])) {
                    $final_link_act = $upload_result['paths'][0];
                    $stmt_update_link = $conn->prepare("UPDATE donatii SET link_act = ? WHERE ID = ?");
                    $stmt_update_link->bind_param("si", $final_link_act, $donatie_id);
                    $stmt_update_link->execute();
                    $stmt_update_link->close();
                }

                $succes_insert = true;
                $donatie_id_asistat = $id_asistat;
                $suma_lei_succes = $suma_lei;
            } else {
                $upload_errors[] = "Eroare MySQL: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $upload_errors[] = "Eroare la pregatirea interogarii MySQL: " . $conn->error;
        }
    }

    if ($succes_insert && empty($upload_errors)) {
        ob_end_clean();
        header('Location: adauga-donatie.php?donatiepentruid=' . $id_asistat . '&suma=' . urlencode($suma_lei));
        exit();
    }
}

$donatie_id_asistat = $_GET['donatiepentruid'] ?? null;
$suma_lei_succes = $_GET['suma'] ?? null;

if (!empty($donatie_id_asistat) && is_numeric($donatie_id_asistat)) {
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
            <i class="bi bi-wallet2 me-2"></i> Adaugă o donație
        </h2>

        <a href="total-donatii.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la Lista Donațiilor
        </a>
    </div>

    <?php if (!empty($donatie_id_asistat) && !empty($nume_beneficiar)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i>
            A fost introdusă cu succes donația de <strong><?php echo htmlspecialchars($suma_lei_succes); ?> lei</strong> pentru <strong><?php echo htmlspecialchars($nume_beneficiar . ' ' . $prenume_beneficiar); ?></strong>
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
                        $selected_asistat = $_POST['id_asistat'] ?? null;
                        if ($rezultate_asistati && mysqli_num_rows($rezultate_asistati) > 0) {
                            while ($data = mysqli_fetch_assoc($rezultate_asistati)) {
                                $nume_complet = htmlspecialchars($data['nume'] . ' ' . $data['prenume']);
                                $id_asistat_opt = htmlspecialchars($data['id']);
                                $selected = ($id_asistat_opt == $selected_asistat) ? 'selected' : '';
                                echo "<option value=\"{$id_asistat_opt}\" {$selected}>{$nume_complet}</option>";
                            }
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

                <div class="col-md-6">
                    <label for="mod_acordare" class="form-label fw-bold">Mod acordare:</label>
                    <select name="mod_acordare" id="mod_acordare" class="form-select">
                        <?php $selected_mod = $_POST['mod_acordare'] ?? ''; ?>
                        <option value="">-- Selectează modul --</option>
                        <option value="transfer bancar" <?php echo ($selected_mod == 'transfer bancar') ? 'selected' : ''; ?>>Transfer bancar</option>
                        <option value="numerar" <?php echo ($selected_mod == 'numerar') ? 'selected' : ''; ?>>Numerar</option>
                        <option value="bunuri" <?php echo ($selected_mod == 'bunuri') ? 'selected' : ''; ?>>Bunuri</option>
                        <option value="servicii" <?php echo ($selected_mod == 'servicii') ? 'selected' : ''; ?>>Servicii</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="scop_donatie" class="form-label fw-bold">Scopul Donației:</label>
                    <input name="scop_donatie" id="scop_donatie" type="text" class="form-control"
                           placeholder="Ex: Chirie ianuarie, Medicamente, Haine, etc."
                           value="<?php echo htmlspecialchars($_POST['scop_donatie'] ?? ''); ?>" required>
                </div>

                <div class="col-md-6">
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

                <div class="col-md-6">
                    <label for="nr_act_doveditor" class="form-label fw-bold">Nr. Act Doveditor:</label>
                    <input name="nr_act_doveditor" id="nr_act_doveditor" type="text" class="form-control" placeholder="Număr act (Ex: Seria + Nr. chitanță/factură)"
                           value="<?php echo htmlspecialchars($_POST['nr_act_doveditor'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="link_act" class="form-label fw-bold">Link Act Doveditor:</label>
                    <input name="link_act" id="link_act" type="url" class="form-control" placeholder="http:// sau link Google Drive"
                           value="<?php echo htmlspecialchars($_POST['link_act'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="file_act" class="form-label fw-bold">Încarcă Act Doveditor (Fișier):</label>
                    <input type="file" name="file_act[]" id="file_act" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                    <div class="form-text">
                        Opțional: poți selecta mai multe poze sau documente odată. Se acceptă PDF, imagini, DOC sau DOCX (max 10 MB/fișier).
                    </div>
                </div>

                <div class="col-12">
                    <label for="observatii_ajutor" class="form-label fw-bold">Observații ajutor:</label>
                    <textarea name="observatii_ajutor" id="observatii_ajutor" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['observatii_ajutor'] ?? ''); ?></textarea>
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
ob_end_flush();
?>
