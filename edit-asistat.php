<?php
$titlu_pg = "Fisa sociala beneficiar";
include "includes/header.php";

$success_message = '';
$error_message = '';
$id = $_GET['id'] ?? $_POST['id'] ?? null;

function edit_post($key) {
    return trim((string)($_POST[$key] ?? ''));
}

function edit_null_post($key) {
    $value = edit_post($key);
    return $value === '' ? null : $value;
}

function edit_bind_params($stmt, $types, &$values) {
    $refs = array($types);
    foreach ($values as &$value) {
        $refs[] = &$value;
    }
    return call_user_func_array(array($stmt, 'bind_param'), $refs);
}

function edit_update_row($conn, $table, $data, $where_column, $where_value) {
    $set = array();
    foreach (array_keys($data) as $column) {
        $set[] = "`$column` = ?";
    }
    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE `$where_column` = ?";
    $stmt = $conn->prepare($sql);
    $values = array_values($data);
    $values[] = $where_value;
    $types = str_repeat('s', count($values));
    edit_bind_params($stmt, $types, $values);
    $stmt->execute();
    $stmt->close();
}

function edit_insert_row($conn, $table, $data) {
    $columns = array_keys($data);
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")";
    $stmt = $conn->prepare($sql);
    $values = array_values($data);
    $types = str_repeat('s', count($values));
    edit_bind_params($stmt, $types, $values);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function h($value) {
    return htmlspecialchars((string)$value);
}

function selected_opt($value, $selected) {
    return ((string)$value === (string)$selected) ? 'selected' : '';
}

function checked_opt($value, $csv) {
    $items = array_map('trim', explode(',', (string)$csv));
    return in_array($value, $items, true) ? 'checked' : '';
}

function render_select_options($items, $selected = '') {
    foreach ($items as $item) {
        echo '<option value="' . h($item) . '" ' . selected_opt($item, $selected) . '>' . h(ucfirst($item)) . '</option>';
    }
}

function render_checkbox_group($name, $items, $selected_csv = '') {
    foreach ($items as $item) {
        $html_id = $name . '-' . preg_replace('/[^a-z0-9]+/i', '-', $item);
        echo '<div class="form-check form-check-inline">';
        echo '<input class="form-check-input" type="checkbox" name="' . h($name) . '[]" id="' . h($html_id) . '" value="' . h($item) . '" ' . checked_opt($item, $selected_csv) . '>';
        echo '<label class="form-check-label" for="' . h($html_id) . '">' . h($item) . '</label>';
        echo '</div>';
    }
}

if (isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt_del = $conn->prepare("DELETE FROM asistati_social WHERE id = ?");
    $stmt_del->bind_param("i", $delete_id);
    $stmt_del->execute();
    $stmt_del->close();
    header("Location: asistati.php?succes_del=$delete_id");
    exit;
}

if (empty($id) || !is_numeric($id)) {
    echo '<div class="alert alert-danger mt-4 container">ID invalid sau inexistent.</div>';
    include "includes/footer.php";
    exit;
}
$id = (int)$id;

$stmt_get = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$asistat = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$asistat) {
    echo '<div class="alert alert-warning mt-4 container">Beneficiarul nu exista.</div>';
    include "includes/footer.php";
    exit;
}

$fisa = social_get_current_fisa($conn, $id);
if (empty($fisa['id'])) {
    $fisa_id = edit_insert_row($conn, 'fise_sociale', array(
        'beneficiar_id' => $id,
        'nr_copii_minori' => $asistat['nr_copii'] ?? null,
        'observatii_sociale' => $asistat['descriere'] ?? '',
        'concluzie_sociala' => $asistat['descriere_scurta'] ?? '',
        'data_evaluarii' => date('Y-m-d'),
        'status_caz' => 'caz nou'
    ));
    $fisa = social_get_current_fisa($conn, $id);
}

if (isset($_POST['submit'])) {
    $required = array(
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'cnp' => 'CNP',
        'telefon' => 'Telefon',
        'localitate' => 'Localitate',
        'judet' => 'Judet',
        'data_evaluarii' => 'Data evaluarii',
        'status_caz' => 'Status caz'
    );
    $errors = array();
    foreach ($required as $field => $label) {
        if (edit_post($field) === '') {
            $errors[] = "$label este obligatoriu.";
        }
    }
    $tip_sprijin = social_join_post_values('tip_sprijin_solicitat');
    if ($tip_sprijin === '') {
        $errors[] = 'Tip sprijin solicitat este obligatoriu.';
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            $old_asistat = $asistat;
            $old_fisa = $fisa;
            $serie_ci = edit_post('serie_ci');
            $numar_ci = edit_post('numar_ci');
            $serie_nr_ci = trim($serie_ci . ' ' . $numar_ci);
            $nr_copii_minori = edit_post('nr_copii_minori');
            $observatii_sociale = edit_post('observatii_sociale');
            $concluzie_sociala = edit_post('concluzie_sociala');

            $beneficiar_update = array(
                'nume' => edit_post('nume'),
                'prenume' => edit_post('prenume'),
                'cnp' => edit_post('cnp'),
                'serie_ci' => $serie_ci,
                'numar_ci' => $numar_ci,
                'serie_nr_ci' => $serie_nr_ci,
                'data_nasterii' => edit_null_post('data_nasterii'),
                'telefon' => edit_post('telefon'),
                'email' => edit_post('email'),
                'adresa_completa' => edit_post('adresa_completa'),
                'localitate' => edit_post('localitate'),
                'judet' => edit_post('judet'),
                'stare_civila' => edit_post('stare_civila'),
                'ocupatie' => edit_post('ocupatie'),
                'observatii_generale' => edit_post('observatii_generale'),
                'nr_copii' => $nr_copii_minori === '' ? 0 : $nr_copii_minori,
                'descriere' => $observatii_sociale,
                'descriere_scurta' => $concluzie_sociala
            );

            $fisa_update = array(
                'nr_total_membri' => edit_null_post('nr_total_membri'),
                'nr_copii_minori' => $nr_copii_minori === '' ? null : $nr_copii_minori,
                'nr_adulti' => edit_null_post('nr_adulti'),
                'nr_varstnici' => edit_null_post('nr_varstnici'),
                'nr_persoane_dizabilitati' => edit_null_post('nr_persoane_dizabilitati'),
                'persoane_intretinere' => edit_post('persoane_intretinere'),
                'observatii_familie' => edit_post('observatii_familie'),
                'tip_locuinta' => edit_post('tip_locuinta'),
                'nr_camere' => edit_null_post('nr_camere'),
                'conditii_locuire' => edit_post('conditii_locuire'),
                'utilitati' => social_join_post_values('utilitati'),
                'risc_evacuare' => edit_post('risc_evacuare'),
                'observatii_locuinta' => edit_post('observatii_locuinta'),
                'venit_lunar_estimat' => edit_null_post('venit_lunar_estimat'),
                'surse_venit' => social_join_post_values('surse_venit'),
                'datorii_importante' => edit_post('datorii_importante'),
                'descriere_datorii' => edit_post('descriere_datorii'),
                'cheltuieli_lunare_majore' => edit_post('cheltuieli_lunare_majore'),
                'observatii_financiare' => edit_post('observatii_financiare'),
                'probleme_medicale' => edit_post('probleme_medicale'),
                'descriere_probleme_medicale' => edit_post('descriere_probleme_medicale'),
                'persoane_cu_dizabilitati' => edit_post('persoane_cu_dizabilitati'),
                'grad_handicap' => edit_post('grad_handicap'),
                'documente_medicale_disponibile' => edit_post('documente_medicale_disponibile'),
                'alte_vulnerabilitati' => social_join_post_values('alte_vulnerabilitati'),
                'observatii_sociale' => $observatii_sociale,
                'tip_sprijin_solicitat' => $tip_sprijin,
                'descriere_nevoie' => edit_post('descriere_nevoie'),
                'urgenta_caz' => edit_post('urgenta_caz'),
                'suma_estimata_necesara' => edit_null_post('suma_estimata_necesara'),
                'perioada_sprijin' => edit_post('perioada_sprijin'),
                'alte_surse_ajutor' => edit_post('alte_surse_ajutor'),
                'detalii_alte_surse' => edit_post('detalii_alte_surse'),
                'data_evaluarii' => edit_null_post('data_evaluarii'),
                'modalitate_evaluare' => edit_post('modalitate_evaluare'),
                'persoana_recomandare' => edit_post('persoana_recomandare'),
                'nivel_vulnerabilitate' => edit_post('nivel_vulnerabilitate'),
                'recomandare_interna' => edit_post('recomandare_interna'),
                'motivare_recomandare' => edit_post('motivare_recomandare'),
                'status_caz' => edit_post('status_caz'),
                'data_deciziei' => edit_null_post('data_deciziei'),
                'tip_ajutor_aprobat' => edit_post('tip_ajutor_aprobat'),
                'suma_aprobata' => edit_null_post('suma_aprobata'),
                'observatii_decizie' => edit_post('observatii_decizie'),
                'gdpr_informat' => edit_post('gdpr_informat'),
                'gdpr_semnat' => edit_post('gdpr_semnat'),
                'acord_fotografii' => edit_post('acord_fotografii'),
                'acord_poveste_publica' => edit_post('acord_poveste_publica'),
                'data_acord_gdpr' => edit_null_post('data_acord_gdpr'),
                'observatii_interne' => edit_post('observatii_interne'),
                'concluzie_sociala' => $concluzie_sociala,
                'recomandare_finala' => edit_post('recomandare_finala')
            );

            edit_update_row($conn, 'asistati_social', $beneficiar_update, 'id', $id);
            edit_update_row($conn, 'fise_sociale', $fisa_update, 'id', $old_fisa['id']);

            $audit_fields = array('cnp', 'serie_ci', 'numar_ci', 'telefon', 'localitate', 'judet');
            foreach ($audit_fields as $field) {
                social_log_change($conn, $id, $old_fisa['id'], 'beneficiar', $field, $old_asistat[$field] ?? '', $beneficiar_update[$field] ?? '', 'Date beneficiar modificate.');
            }
            $fisa_audit_fields = array('status_caz', 'tip_sprijin_solicitat', 'suma_aprobata', 'recomandare_interna', 'nivel_vulnerabilitate');
            foreach ($fisa_audit_fields as $field) {
                social_log_change($conn, $id, $old_fisa['id'], 'fisa sociala', $field, $old_fisa[$field] ?? '', $fisa_update[$field] ?? '', 'Fisa sociala modificata.');
            }

            $tip_document = edit_post('tip_document');
            if ($tip_document === '' && isset($_FILES['document_social']) && ($_FILES['document_social']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $tip_document = 'alte documente';
            }
            if ($tip_document !== '') {
                [$ok, $message] = social_store_document(
                    $conn,
                    $_FILES['document_social'] ?? null,
                    $id,
                    $old_fisa['id'],
                    null,
                    $tip_document,
                    edit_post('observatii_document')
                );
                if (!$ok) {
                    throw new Exception($message);
                }
            }

            $conn->commit();
            $success_message = 'Fisa sociala a fost salvata.';

            $stmt_get = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $asistat = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();
            $fisa = social_get_current_fisa($conn, $id);
        } catch (Throwable $e) {
            $conn->rollback();
            $error_message = 'Eroare la salvare: ' . $e->getMessage();
        }
    } else {
        $error_message = implode(' ', $errors);
    }
}

$serie_ci = $asistat['serie_ci'] ?? '';
$numar_ci = $asistat['numar_ci'] ?? '';
if (($serie_ci === '' || $numar_ci === '') && !empty($asistat['serie_nr_ci'])) {
    $parts = preg_split('/\s+/', trim($asistat['serie_nr_ci']), 2);
    $serie_ci = $serie_ci ?: ($parts[0] ?? '');
    $numar_ci = $numar_ci ?: ($parts[1] ?? '');
}

$documents = array();
$stmt_docs = $conn->prepare("SELECT * FROM documente_sociale WHERE beneficiar_id = ? ORDER BY data_incarcarii DESC, id DESC");
$stmt_docs->bind_param("i", $id);
$stmt_docs->execute();
$result_docs = $stmt_docs->get_result();
while ($row = $result_docs->fetch_assoc()) {
    $documents[] = $row;
}
$stmt_docs->close();

$ajutoare = array();
$stmt_help = $conn->prepare("SELECT * FROM donatii WHERE id_asistat = ? ORDER BY data DESC, ID DESC LIMIT 50");
$stmt_help->bind_param("i", $id);
$stmt_help->execute();
$result_help = $stmt_help->get_result();
while ($row = $result_help->fetch_assoc()) {
    $ajutoare[] = $row;
}
$stmt_help->close();

$istoric = array();
$stmt_hist = $conn->prepare("SELECT * FROM istoric_modificari WHERE beneficiar_id = ? ORDER BY data_modificarii DESC, id DESC LIMIT 50");
$stmt_hist->bind_param("i", $id);
$stmt_hist->execute();
$result_hist = $stmt_hist->get_result();
while ($row = $result_hist->fetch_assoc()) {
    $istoric[] = $row;
}
$stmt_hist->close();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 d-none d-md-block"><?php include "includes/sidebar.php"; ?></div>
        <div class="col-12 col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-person-vcard me-2"></i> Fisa sociala</h2>
                <div class="btn-group">
                    <a href="export-fisa-sociala.php?id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm" target="_blank">Export PDF</a>
                    <a href="lista-donatii.php?id=<?php echo $id; ?>&persoana=<?php echo urlencode(($asistat['nume'] ?? '') . ' ' . ($asistat['prenume'] ?? '')); ?>&an=<?php echo date('Y'); ?>" class="btn btn-outline-success btn-sm">Ajutoare</a>
                    <a href="asistati.php" class="btn btn-outline-secondary btn-sm">Inapoi</a>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">Sterge</button>
                </div>
            </div>

            <?php if ($success_message): ?><div class="alert alert-success"><?php echo h($success_message); ?></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-danger"><?php echo h($error_message); ?></div><?php endif; ?>

            <form action="edit-asistat.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">1. Date beneficiar</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-6"><label class="form-label fw-bold">Nume *</label><input name="nume" type="text" class="form-control" value="<?php echo h($asistat['nume'] ?? ''); ?>" required></div>
                        <div class="col-md-6"><label class="form-label fw-bold">Prenume *</label><input name="prenume" type="text" class="form-control" value="<?php echo h($asistat['prenume'] ?? ''); ?>" required></div>
                        <div class="col-md-4"><label class="form-label fw-bold">CNP *</label><input name="cnp" type="text" class="form-control" maxlength="13" value="<?php echo h($asistat['cnp'] ?? ''); ?>" required></div>
                        <div class="col-md-2"><label class="form-label">Serie CI</label><input name="serie_ci" type="text" class="form-control" value="<?php echo h($serie_ci); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Numar CI</label><input name="numar_ci" type="text" class="form-control" value="<?php echo h($numar_ci); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Data nasterii</label><input name="data_nasterii" type="date" class="form-control" value="<?php echo h($asistat['data_nasterii'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Telefon *</label><input name="telefon" type="tel" class="form-control" value="<?php echo h($asistat['telefon'] ?? ''); ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?php echo h($asistat['email'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Stare civila</label><select name="stare_civila" class="form-select"><?php render_select_options(array('necasatorit', 'casatorit', 'divortat', 'vaduv'), $asistat['stare_civila'] ?? ''); ?></select></div>
                        <div class="col-12"><label class="form-label">Adresa completa</label><input name="adresa_completa" type="text" class="form-control" value="<?php echo h($asistat['adresa_completa'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Localitate *</label><input name="localitate" type="text" class="form-control" value="<?php echo h($asistat['localitate'] ?? ''); ?>" required></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Judet *</label><input name="judet" type="text" class="form-control" value="<?php echo h($asistat['judet'] ?? ''); ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Ocupatie</label><input name="ocupatie" type="text" class="form-control" value="<?php echo h($asistat['ocupatie'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Observatii generale</label><textarea name="observatii_generale" class="form-control" rows="3"><?php echo h($asistat['observatii_generale'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">2. Componenta familiei</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-2"><label class="form-label">Total membri</label><input name="nr_total_membri" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_total_membri'] ?? ''); ?>"></div>
                        <div class="col-md-2"><label class="form-label">Copii minori</label><input name="nr_copii_minori" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_copii_minori'] ?? ''); ?>"></div>
                        <div class="col-md-2"><label class="form-label">Adulti</label><input name="nr_adulti" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_adulti'] ?? ''); ?>"></div>
                        <div class="col-md-2"><label class="form-label">Varstnici</label><input name="nr_varstnici" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_varstnici'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Persoane cu dizabilitati</label><input name="nr_persoane_dizabilitati" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_persoane_dizabilitati'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Persoane aflate in intretinere</label><textarea name="persoane_intretinere" class="form-control" rows="2"><?php echo h($fisa['persoane_intretinere'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii familie</label><textarea name="observatii_familie" class="form-control" rows="3"><?php echo h($fisa['observatii_familie'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">3. Situatie locativa</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Tip locuinta</label><select name="tip_locuinta" class="form-select"><option value="">--</option><?php render_select_options(social_options('tip_locuinta'), $fisa['tip_locuinta'] ?? ''); ?></select></div>
                        <div class="col-md-2"><label class="form-label">Camere</label><input name="nr_camere" type="number" min="0" class="form-control" value="<?php echo h($fisa['nr_camere'] ?? ''); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Conditii</label><select name="conditii_locuire" class="form-select"><option value="">--</option><?php render_select_options(social_options('conditii_locuire'), $fisa['conditii_locuire'] ?? ''); ?></select></div>
                        <div class="col-md-3"><label class="form-label">Risc evacuare</label><select name="risc_evacuare" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['risc_evacuare'] ?? ''); ?></select></div>
                        <div class="col-12"><label class="form-label">Utilitati</label><br><?php render_checkbox_group('utilitati', social_options('utilitati'), $fisa['utilitati'] ?? ''); ?></div>
                        <div class="col-12"><label class="form-label">Observatii locuinta</label><textarea name="observatii_locuinta" class="form-control" rows="3"><?php echo h($fisa['observatii_locuinta'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">4. Situatie financiara</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Venit lunar estimat</label><input name="venit_lunar_estimat" type="number" min="0" step="0.01" class="form-control" value="<?php echo h($fisa['venit_lunar_estimat'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Datorii importante</label><select name="datorii_importante" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['datorii_importante'] ?? ''); ?></select></div>
                        <div class="col-12"><label class="form-label">Surse venit</label><br><?php render_checkbox_group('surse_venit', social_options('surse_venit'), $fisa['surse_venit'] ?? ''); ?></div>
                        <div class="col-12"><label class="form-label">Descriere datorii</label><textarea name="descriere_datorii" class="form-control" rows="2"><?php echo h($fisa['descriere_datorii'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Cheltuieli lunare majore</label><textarea name="cheltuieli_lunare_majore" class="form-control" rows="2"><?php echo h($fisa['cheltuieli_lunare_majore'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii financiare</label><textarea name="observatii_financiare" class="form-control" rows="3"><?php echo h($fisa['observatii_financiare'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">5. Medical si vulnerabilitati</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Probleme medicale</label><select name="probleme_medicale" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['probleme_medicale'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Persoane cu dizabilitati</label><select name="persoane_cu_dizabilitati" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['persoane_cu_dizabilitati'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Documente medicale disponibile</label><select name="documente_medicale_disponibile" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['documente_medicale_disponibile'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Grad handicap</label><input name="grad_handicap" type="text" class="form-control" value="<?php echo h($fisa['grad_handicap'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Alte vulnerabilitati</label><br><?php render_checkbox_group('alte_vulnerabilitati', social_options('vulnerabilitati'), $fisa['alte_vulnerabilitati'] ?? ''); ?></div>
                        <div class="col-12"><label class="form-label">Descriere probleme medicale</label><textarea name="descriere_probleme_medicale" class="form-control" rows="3"><?php echo h($fisa['descriere_probleme_medicale'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii sociale</label><textarea name="observatii_sociale" class="form-control" rows="3"><?php echo h($fisa['observatii_sociale'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">6. Nevoia de sprijin</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-12"><label class="form-label fw-bold">Tip sprijin solicitat *</label><br><?php render_checkbox_group('tip_sprijin_solicitat', social_options('tip_sprijin'), $fisa['tip_sprijin_solicitat'] ?? ''); ?></div>
                        <div class="col-12"><label class="form-label">Descrierea nevoii</label><textarea name="descriere_nevoie" class="form-control" rows="3"><?php echo h($fisa['descriere_nevoie'] ?? ''); ?></textarea></div>
                        <div class="col-md-3"><label class="form-label">Urgenta</label><select name="urgenta_caz" class="form-select"><option value="">--</option><?php render_select_options(social_options('urgenta'), $fisa['urgenta_caz'] ?? ''); ?></select></div>
                        <div class="col-md-3"><label class="form-label">Suma estimata</label><input name="suma_estimata_necesara" type="number" min="0" step="0.01" class="form-control" value="<?php echo h($fisa['suma_estimata_necesara'] ?? ''); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Perioada</label><input name="perioada_sprijin" type="text" class="form-control" value="<?php echo h($fisa['perioada_sprijin'] ?? ''); ?>"></div>
                        <div class="col-md-3"><label class="form-label">Alte surse</label><select name="alte_surse_ajutor" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu_necunoscut'), $fisa['alte_surse_ajutor'] ?? ''); ?></select></div>
                        <div class="col-12"><label class="form-label">Detalii alte surse</label><textarea name="detalii_alte_surse" class="form-control" rows="2"><?php echo h($fisa['detalii_alte_surse'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">7-8. Evaluare si decizie</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label fw-bold">Data evaluarii *</label><input name="data_evaluarii" type="date" class="form-control" value="<?php echo h($fisa['data_evaluarii'] ?? date('Y-m-d')); ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Modalitate</label><select name="modalitate_evaluare" class="form-select"><option value="">--</option><?php render_select_options(social_options('modalitate_evaluare'), $fisa['modalitate_evaluare'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Recomandat de</label><input name="persoana_recomandare" type="text" class="form-control" value="<?php echo h($fisa['persoana_recomandare'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Nivel vulnerabilitate</label><select name="nivel_vulnerabilitate" class="form-select"><option value="">--</option><?php render_select_options(social_options('nivel_vulnerabilitate'), $fisa['nivel_vulnerabilitate'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Recomandare interna</label><select name="recomandare_interna" class="form-select"><option value="">--</option><?php render_select_options(social_options('recomandare_interna'), $fisa['recomandare_interna'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Status caz *</label><select name="status_caz" class="form-select" required><?php render_select_options(social_options('status_caz'), $fisa['status_caz'] ?? 'caz nou'); ?></select></div>
                        <div class="col-12"><label class="form-label">Motivare recomandare</label><textarea name="motivare_recomandare" class="form-control" rows="3"><?php echo h($fisa['motivare_recomandare'] ?? ''); ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Data deciziei</label><input name="data_deciziei" type="date" class="form-control" value="<?php echo h($fisa['data_deciziei'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Suma aprobata</label><input name="suma_aprobata" type="number" min="0" step="0.01" class="form-control" value="<?php echo h($fisa['suma_aprobata'] ?? ''); ?>"></div>
                        <div class="col-md-4"><label class="form-label">Tip ajutor aprobat</label><input name="tip_ajutor_aprobat" type="text" class="form-control" value="<?php echo h($fisa['tip_ajutor_aprobat'] ?? ''); ?>"></div>
                        <div class="col-12"><label class="form-label">Observatii decizie</label><textarea name="observatii_decizie" class="form-control" rows="3"><?php echo h($fisa['observatii_decizie'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">9-12. Documente, GDPR si concluzii</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">GDPR informat</label><select name="gdpr_informat" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['gdpr_informat'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">GDPR semnat</label><select name="gdpr_semnat" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['gdpr_semnat'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Data acord GDPR</label><input name="data_acord_gdpr" type="date" class="form-control" value="<?php echo h($fisa['data_acord_gdpr'] ?? ''); ?>"></div>
                        <div class="col-md-6"><label class="form-label">Acord fotografii</label><select name="acord_fotografii" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['acord_fotografii'] ?? ''); ?></select></div>
                        <div class="col-md-6"><label class="form-label">Acord poveste publica</label><select name="acord_poveste_publica" class="form-select"><option value="">--</option><?php render_select_options(social_options('da_nu'), $fisa['acord_poveste_publica'] ?? ''); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Tip document nou</label><select name="tip_document" class="form-select"><option value="">--</option><?php render_select_options(social_options('documente')); ?></select></div>
                        <div class="col-md-8"><label class="form-label">Incarca document nou</label><input name="document_social" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                        <div class="col-12"><label class="form-label">Observatii document</label><input name="observatii_document" type="text" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Observatii interne</label><textarea name="observatii_interne" class="form-control" rows="3"><?php echo h($fisa['observatii_interne'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Concluzie sociala scurta</label><textarea name="concluzie_sociala" class="form-control" rows="3"><?php echo h($fisa['concluzie_sociala'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Recomandare finala</label><textarea name="recomandare_finala" class="form-control" rows="3"><?php echo h($fisa['recomandare_finala'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="text-center mb-5">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg px-5"><i class="bi bi-save me-2"></i> Salveaza fisa</button>
                </div>
            </form>

            <div class="card shadow mb-4">
                <div class="card-header bg-light"><h5 class="mb-0">Documente atasate</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($documents)): ?>
                        <div class="p-3 text-muted">Nu exista documente atasate.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Tip</th><th>Fisier</th><th>Data</th><th>Observatii</th></tr></thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td><?php echo h($doc['tip_document']); ?></td>
                                            <td><a href="view-document-social.php?id=<?php echo (int)$doc['id']; ?>" target="_blank"><?php echo h($doc['denumire_fisier']); ?></a></td>
                                            <td><?php echo h($doc['data_incarcarii']); ?></td>
                                            <td><?php echo h($doc['observatii']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header bg-light"><h5 class="mb-0">Istoric ajutoare acordate</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($ajutoare)): ?>
                        <div class="p-3 text-muted">Nu exista ajutoare acordate.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>ID</th><th>Data</th><th>Tip</th><th>Valoare</th><th>Scop</th><th>Documente</th></tr></thead>
                                <tbody>
                                    <?php foreach ($ajutoare as $ajutor): ?>
                                        <tr>
                                            <td><?php echo h($ajutor['ID'] ?? $ajutor['id']); ?></td>
                                            <td><?php echo h($ajutor['data']); ?></td>
                                            <td><?php echo h($ajutor['tip_donatie']); ?></td>
                                            <td><?php echo number_format((float)$ajutor['suma_lei'], 2, ',', '.'); ?> lei</td>
                                            <td><?php echo h($ajutor['scop_donatie']); ?></td>
                                            <td>
                                                <?php if (!empty($ajutor['link_act'])): ?><a href="<?php echo h($ajutor['link_act']); ?>" target="_blank">act</a><?php endif; ?>
                                                <?php if (!empty($ajutor['link_proces_verbal'])): ?> <a href="<?php echo h($ajutor['link_proces_verbal']); ?>" target="_blank">PV</a><?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow mb-5">
                <div class="card-header bg-light"><h5 class="mb-0">Istoric modificari importante</h5></div>
                <div class="card-body p-0">
                    <?php if (empty($istoric)): ?>
                        <div class="p-3 text-muted">Nu exista modificari importante inregistrate.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Data</th><th>Tip</th><th>Camp</th><th>Vechi</th><th>Nou</th></tr></thead>
                                <tbody>
                                    <?php foreach ($istoric as $item): ?>
                                        <tr>
                                            <td><?php echo h($item['data_modificarii']); ?></td>
                                            <td><?php echo h($item['tip_modificare']); ?></td>
                                            <td><?php echo h($item['camp_modificat']); ?></td>
                                            <td><?php echo h($item['valoare_veche']); ?></td>
                                            <td><?php echo h($item['valoare_noua']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white"><h5 class="modal-title">Atentie</h5></div>
      <div class="modal-body">Sigur doriti sa stergeti definitiv acest beneficiar?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuleaza</button>
        <form method="POST">
            <input type="hidden" name="delete_id" value="<?php echo $id; ?>">
            <button type="submit" class="btn btn-danger">Confirma stergerea</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>
