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

function edit_uploaded_files($field) {
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) {
        return array();
    }

    $normalized = array();
    foreach ($files['name'] as $index => $name) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $normalized[] = array(
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => $files['size'][$index] ?? 0
        );
    }

    return $normalized;
}

function edit_document_is_image($document) {
    $filename = strtolower((string)($document['denumire_fisier'] ?? $document['cale_fisier'] ?? ''));
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return in_array($ext, array('jpg', 'jpeg', 'png'), true);
}

function edit_external_labels() {
    return array(
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'cnp' => 'CNP',
        'serie_ci' => 'Serie CI',
        'numar_ci' => 'Numar CI',
        'data_nasterii' => 'Data nasterii',
        'telefon' => 'Telefon',
        'email' => 'Email',
        'adresa_completa' => 'Adresa completa',
        'localitate' => 'Localitate',
        'judet' => 'Judet',
        'stare_civila' => 'Stare civila',
        'ocupatie' => 'Ocupatie',
        'observatii_generale' => 'Observatii generale',
        'nr_total_membri' => 'Total membri',
        'nr_copii_minori' => 'Copii minori',
        'nr_adulti' => 'Adulti',
        'nr_varstnici' => 'Varstnici',
        'nr_persoane_dizabilitati' => 'Persoane cu dizabilitati',
        'persoane_intretinere' => 'Persoane in intretinere',
        'observatii_familie' => 'Observatii familie',
        'tip_locuinta' => 'Tip locuinta',
        'nr_camere' => 'Camere',
        'conditii_locuire' => 'Conditii locuire',
        'utilitati' => 'Utilitati',
        'risc_evacuare' => 'Risc evacuare',
        'observatii_locuinta' => 'Observatii locuinta',
        'venit_lunar_estimat' => 'Venit lunar estimat',
        'surse_venit' => 'Surse venit',
        'datorii_importante' => 'Datorii importante',
        'descriere_datorii' => 'Descriere datorii',
        'cheltuieli_lunare_majore' => 'Cheltuieli lunare majore',
        'observatii_financiare' => 'Observatii financiare',
        'probleme_medicale' => 'Probleme medicale',
        'descriere_probleme_medicale' => 'Descriere probleme medicale',
        'persoane_cu_dizabilitati' => 'Persoane cu dizabilitati',
        'grad_handicap' => 'Grad handicap',
        'documente_medicale_disponibile' => 'Documente medicale disponibile',
        'alte_vulnerabilitati' => 'Alte vulnerabilitati',
        'observatii_sociale' => 'Observatii sociale',
        'tip_sprijin_solicitat' => 'Tip sprijin solicitat',
        'descriere_nevoie' => 'Descriere nevoie',
        'urgenta_caz' => 'Urgenta caz',
        'suma_estimata_necesara' => 'Suma estimata necesara',
        'perioada_sprijin' => 'Perioada sprijin',
        'alte_surse_ajutor' => 'Alte surse ajutor',
        'detalii_alte_surse' => 'Detalii alte surse',
        'gdpr_informat' => 'GDPR informat',
        'gdpr_semnat' => 'GDPR semnat',
        'acord_fotografii' => 'Acord fotografii',
        'acord_poveste_publica' => 'Acord poveste publica',
        'data_acord_gdpr' => 'Data acord GDPR'
    );
}

function edit_payload_value($payload, $key) {
    return trim((string)($payload[$key] ?? ''));
}

function edit_payload_null($payload, $key) {
    $value = edit_payload_value($payload, $key);
    return $value === '' ? null : $value;
}

function edit_render_external_payload($payload) {
    $labels = edit_external_labels();
    echo '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
    foreach ($labels as $key => $label) {
        $value = trim((string)($payload[$key] ?? ''));
        if ($value === '') {
            continue;
        }
        echo '<tr><th style="width: 230px;">' . h($label) . '</th><td>' . nl2br(h($value)) . '</td></tr>';
    }
    echo '</table></div>';
}

function edit_apply_external_payload($conn, $beneficiar_id, $fisa_id, $payload, $current_user) {
    $serie_ci = edit_payload_value($payload, 'serie_ci');
    $numar_ci = edit_payload_value($payload, 'numar_ci');
    $nr_copii_minori = edit_payload_value($payload, 'nr_copii_minori');
    $observatii_sociale = edit_payload_value($payload, 'observatii_sociale');

    $beneficiar_update = array(
        'nume' => edit_payload_value($payload, 'nume'),
        'prenume' => edit_payload_value($payload, 'prenume'),
        'cnp' => edit_payload_value($payload, 'cnp'),
        'serie_ci' => $serie_ci,
        'numar_ci' => $numar_ci,
        'serie_nr_ci' => trim($serie_ci . ' ' . $numar_ci),
        'data_nasterii' => edit_payload_null($payload, 'data_nasterii'),
        'telefon' => edit_payload_value($payload, 'telefon'),
        'email' => edit_payload_value($payload, 'email'),
        'adresa_completa' => edit_payload_value($payload, 'adresa_completa'),
        'localitate' => edit_payload_value($payload, 'localitate'),
        'judet' => edit_payload_value($payload, 'judet'),
        'stare_civila' => edit_payload_value($payload, 'stare_civila'),
        'ocupatie' => edit_payload_value($payload, 'ocupatie'),
        'observatii_generale' => edit_payload_value($payload, 'observatii_generale'),
        'nr_copii' => $nr_copii_minori === '' ? 0 : $nr_copii_minori,
        'descriere' => $observatii_sociale
    );

    $fisa_update = array(
        'nr_total_membri' => edit_payload_null($payload, 'nr_total_membri'),
        'nr_copii_minori' => $nr_copii_minori === '' ? null : $nr_copii_minori,
        'nr_adulti' => edit_payload_null($payload, 'nr_adulti'),
        'nr_varstnici' => edit_payload_null($payload, 'nr_varstnici'),
        'nr_persoane_dizabilitati' => edit_payload_null($payload, 'nr_persoane_dizabilitati'),
        'persoane_intretinere' => edit_payload_value($payload, 'persoane_intretinere'),
        'observatii_familie' => edit_payload_value($payload, 'observatii_familie'),
        'tip_locuinta' => edit_payload_value($payload, 'tip_locuinta'),
        'nr_camere' => edit_payload_null($payload, 'nr_camere'),
        'conditii_locuire' => edit_payload_value($payload, 'conditii_locuire'),
        'utilitati' => edit_payload_value($payload, 'utilitati'),
        'risc_evacuare' => edit_payload_value($payload, 'risc_evacuare'),
        'observatii_locuinta' => edit_payload_value($payload, 'observatii_locuinta'),
        'venit_lunar_estimat' => edit_payload_null($payload, 'venit_lunar_estimat'),
        'surse_venit' => edit_payload_value($payload, 'surse_venit'),
        'datorii_importante' => edit_payload_value($payload, 'datorii_importante'),
        'descriere_datorii' => edit_payload_value($payload, 'descriere_datorii'),
        'cheltuieli_lunare_majore' => edit_payload_value($payload, 'cheltuieli_lunare_majore'),
        'observatii_financiare' => edit_payload_value($payload, 'observatii_financiare'),
        'probleme_medicale' => edit_payload_value($payload, 'probleme_medicale'),
        'descriere_probleme_medicale' => edit_payload_value($payload, 'descriere_probleme_medicale'),
        'persoane_cu_dizabilitati' => edit_payload_value($payload, 'persoane_cu_dizabilitati'),
        'grad_handicap' => edit_payload_value($payload, 'grad_handicap'),
        'documente_medicale_disponibile' => edit_payload_value($payload, 'documente_medicale_disponibile'),
        'alte_vulnerabilitati' => edit_payload_value($payload, 'alte_vulnerabilitati'),
        'observatii_sociale' => $observatii_sociale,
        'tip_sprijin_solicitat' => edit_payload_value($payload, 'tip_sprijin_solicitat'),
        'descriere_nevoie' => edit_payload_value($payload, 'descriere_nevoie'),
        'urgenta_caz' => edit_payload_value($payload, 'urgenta_caz'),
        'suma_estimata_necesara' => edit_payload_null($payload, 'suma_estimata_necesara'),
        'perioada_sprijin' => edit_payload_value($payload, 'perioada_sprijin'),
        'alte_surse_ajutor' => edit_payload_value($payload, 'alte_surse_ajutor'),
        'detalii_alte_surse' => edit_payload_value($payload, 'detalii_alte_surse'),
        'gdpr_informat' => edit_payload_value($payload, 'gdpr_informat'),
        'gdpr_semnat' => edit_payload_value($payload, 'gdpr_semnat'),
        'acord_fotografii' => edit_payload_value($payload, 'acord_fotografii'),
        'acord_poveste_publica' => edit_payload_value($payload, 'acord_poveste_publica'),
        'data_acord_gdpr' => edit_payload_null($payload, 'data_acord_gdpr')
    );

    edit_update_row($conn, 'asistati_social', $beneficiar_update, 'id', $beneficiar_id);
    edit_update_row($conn, 'fise_sociale', $fisa_update, 'id', $fisa_id);
    social_log_change($conn, $beneficiar_id, $fisa_id, 'formular extern', 'date beneficiar', '', 'Date aplicate in fisa', 'Aplicat de ' . $current_user . '.');
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

social_ensure_external_tables($conn);
$generated_external_url = '';

if (isset($_POST['external_action'])) {
    $external_action = edit_post('external_action');
    try {
        if ($external_action === 'generate_link') {
            $conn->begin_transaction();

            $stmt = $conn->prepare("
                UPDATE asistat_external_links
                SET revoked_at = NOW()
                WHERE beneficiar_id = ?
                  AND revoked_at IS NULL
                  AND expires_at >= NOW()
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
            $created_by = $_SESSION['nume_utilizator'] ?? 'utilizator';
            $stmt = $conn->prepare("
                INSERT INTO asistat_external_links (beneficiar_id, token_hash, expires_at, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $id, $token_hash, $expires_at, $created_by);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $generated_external_url = BASE_URL . 'formular-asistat.php?t=' . $token;
            $success_message = 'Linkul extern a fost generat. Copiati-l acum, tokenul nu este stocat in clar.';
        } elseif ($external_action === 'revoke_link') {
            $link_id = (int)($_POST['external_link_id'] ?? 0);
            $stmt = $conn->prepare("
                UPDATE asistat_external_links
                SET revoked_at = NOW()
                WHERE id = ? AND beneficiar_id = ? AND revoked_at IS NULL
            ");
            $stmt->bind_param("ii", $link_id, $id);
            $stmt->execute();
            $stmt->close();
            $success_message = 'Linkul extern a fost revocat.';
        } elseif ($external_action === 'apply_submission') {
            $submission_id = (int)($_POST['submission_id'] ?? 0);
            $stmt = $conn->prepare("
                SELECT *
                FROM asistat_external_submissions
                WHERE id = ? AND beneficiar_id = ? AND status = 'nou'
                LIMIT 1
            ");
            $stmt->bind_param("ii", $submission_id, $id);
            $stmt->execute();
            $submission = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$submission) {
                throw new Exception('Completarea externa nu mai este disponibila.');
            }

            $payload = json_decode($submission['payload_json'], true);
            if (!is_array($payload)) {
                throw new Exception('Datele primite nu pot fi citite.');
            }

            $conn->begin_transaction();
            edit_apply_external_payload($conn, $id, (int)$fisa['id'], $payload, $_SESSION['nume_utilizator'] ?? 'utilizator');

            $applied_by = $_SESSION['nume_utilizator'] ?? 'utilizator';
            $stmt = $conn->prepare("
                UPDATE asistat_external_submissions
                SET status = 'aplicat', applied_at = NOW(), applied_by = ?
                WHERE id = ? AND beneficiar_id = ?
            ");
            $stmt->bind_param("sii", $applied_by, $submission_id, $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success_message = 'Datele primite prin formularul extern au fost aplicate in fisa.';

            $stmt_get = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
            $stmt_get->bind_param("i", $id);
            $stmt_get->execute();
            $asistat = $stmt_get->get_result()->fetch_assoc();
            $stmt_get->close();
            $fisa = social_get_current_fisa($conn, $id);
        }
    } catch (Throwable $e) {
        if ($conn->errno === 0) {
            // transaction state is not directly exposed by mysqli; rollback is harmless if none is active
        }
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
        $error_message = 'Eroare link extern: ' . $e->getMessage();
    }
}

if (isset($_POST['submit'])) {
    $required = array(
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'cnp' => 'CNP',
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

            $uploaded_documents = 0;
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
                if ($message !== null) {
                    $uploaded_documents++;
                }
            }

            $bulk_tip_document = edit_post('bulk_tip_document');
            if ($bulk_tip_document === '') {
                $bulk_tip_document = 'alte documente';
            }
            foreach (edit_uploaded_files('documente_sociale_bulk') as $bulk_file) {
                [$ok, $message] = social_store_document(
                    $conn,
                    $bulk_file,
                    $id,
                    $old_fisa['id'],
                    null,
                    $bulk_tip_document,
                    edit_post('bulk_observatii_document')
                );
                if (!$ok) {
                    throw new Exception($message);
                }
                if ($message !== null) {
                    $uploaded_documents++;
                }
            }

            $conn->commit();
            $success_message = 'Fisa sociala a fost salvata.';
            if ($uploaded_documents > 0) {
                $success_message .= ' Documente incarcate: ' . $uploaded_documents . '.';
            }

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

$image_documents = array_values(array_filter($documents, 'edit_document_is_image'));

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

$external_active_link = null;
$stmt_ext = $conn->prepare("
    SELECT *
    FROM asistat_external_links
    WHERE beneficiar_id = ?
      AND revoked_at IS NULL
      AND expires_at >= NOW()
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt_ext->bind_param("i", $id);
$stmt_ext->execute();
$external_active_link = $stmt_ext->get_result()->fetch_assoc();
$stmt_ext->close();

$external_submissions = array();
$stmt_sub = $conn->prepare("
    SELECT *
    FROM asistat_external_submissions
    WHERE beneficiar_id = ?
    ORDER BY submitted_at DESC, id DESC
    LIMIT 10
");
$stmt_sub->bind_param("i", $id);
$stmt_sub->execute();
$result_sub = $stmt_sub->get_result();
while ($row = $result_sub->fetch_assoc()) {
    $external_submissions[] = $row;
}
$stmt_sub->close();

$external_pending_submissions = array_values(array_filter($external_submissions, function ($submission) {
    return ($submission['status'] ?? '') === 'nou';
}));
$external_applied_submissions = array_values(array_filter($external_submissions, function ($submission) {
    return ($submission['status'] ?? '') === 'aplicat';
}));
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

            <div class="card shadow mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Link extern completare date</h5>
                    <?php if ($external_active_link): ?><span class="badge text-bg-success">activ</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($generated_external_url): ?>
                        <label class="form-label fw-bold">Link generat</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?php echo h($generated_external_url); ?>" readonly onclick="this.select();">
                            <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">Copiaza</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($external_active_link): ?>
                        <div class="mb-3">
                            <div class="small text-muted">Exista un link activ pana la <?php echo h($external_active_link['expires_at']); ?>.</div>
                            <?php if (!$generated_external_url): ?>
                                <div class="small text-muted">Din motive de securitate tokenul nu este stocat in clar. Daca linkul a fost pierdut, generati unul nou.</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <form method="post">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="external_action" value="generate_link">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Genereaza link nou</button>
                            </form>
                            <form method="post">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="external_action" value="revoke_link">
                                <input type="hidden" name="external_link_id" value="<?php echo (int)$external_active_link['id']; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Revoca link</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <input type="hidden" name="external_action" value="generate_link">
                            <button type="submit" class="btn btn-primary btn-sm">Genereaza link completare date</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($external_pending_submissions) || !empty($external_applied_submissions)): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-light"><h5 class="mb-0">Date primite prin formular extern</h5></div>
                    <div class="card-body">
                        <?php if (empty($external_pending_submissions)): ?>
                            <div class="alert alert-light border mb-3">Nu exista completari noi de aplicat.</div>
                        <?php endif; ?>

                        <?php foreach ($external_pending_submissions as $submission): ?>
                            <?php $payload = json_decode($submission['payload_json'], true); ?>
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-bold">Completare #<?php echo (int)$submission['id']; ?></div>
                                        <div class="small text-muted">
                                            <?php echo h($submission['submitted_at']); ?>
                                            <?php if (!empty($submission['submitted_ip'])): ?> / IP <?php echo h($submission['submitted_ip']); ?><?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge text-bg-warning">nou</span>
                                        <?php if (is_array($payload)): ?>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#externalSubmission<?php echo (int)$submission['id']; ?>" aria-expanded="false" aria-controls="externalSubmission<?php echo (int)$submission['id']; ?>">Vezi datele</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!is_array($payload)): ?>
                                    <div class="text-danger">Datele nu pot fi afisate.</div>
                                <?php endif; ?>
                                <?php if (is_array($payload)): ?>
                                    <div class="collapse mt-3" id="externalSubmission<?php echo (int)$submission['id']; ?>">
                                        <?php edit_render_external_payload($payload); ?>
                                        <form method="post" class="mt-3">
                                            <input type="hidden" name="id" value="<?php echo $id; ?>">
                                            <input type="hidden" name="external_action" value="apply_submission">
                                            <input type="hidden" name="submission_id" value="<?php echo (int)$submission['id']; ?>">
                                            <button type="submit" class="btn btn-success btn-sm">Aplica aceste date in fisa</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!empty($external_applied_submissions)): ?>
                            <div class="mt-3">
                                <h6 class="text-muted mb-2">Completari aplicate recent</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th>ID</th><th>Trimis la</th><th>Aplicat la</th><th>Aplicat de</th></tr></thead>
                                        <tbody>
                                            <?php foreach ($external_applied_submissions as $submission): ?>
                                                <tr>
                                                    <td>#<?php echo (int)$submission['id']; ?></td>
                                                    <td><?php echo h($submission['submitted_at']); ?></td>
                                                    <td><?php echo h($submission['applied_at']); ?></td>
                                                    <td><?php echo h($submission['applied_by']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                        <div class="col-md-4"><label class="form-label">Telefon</label><input name="telefon" type="tel" class="form-control" value="<?php echo h($asistat['telefon'] ?? ''); ?>"></div>
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
                        <div class="col-12"><hr class="my-2"></div>
                        <div class="col-md-4"><label class="form-label">Tip acte bulk</label><select name="bulk_tip_document" class="form-select"><option value="">Alte documente</option><?php render_select_options(social_options('documente')); ?></select></div>
                        <div class="col-md-8"><label class="form-label">Incarca acte bulk</label><input name="documente_sociale_bulk[]" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple></div>
                        <div class="col-12"><label class="form-label">Observatii acte bulk</label><input name="bulk_observatii_document" type="text" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Observatii interne</label><textarea name="observatii_interne" class="form-control" rows="3"><?php echo h($fisa['observatii_interne'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Concluzie sociala scurta</label><textarea name="concluzie_sociala" class="form-control" rows="3"><?php echo h($fisa['concluzie_sociala'] ?? ''); ?></textarea></div>
                        <div class="col-12"><label class="form-label">Recomandare finala</label><textarea name="recomandare_finala" class="form-control" rows="3"><?php echo h($fisa['recomandare_finala'] ?? ''); ?></textarea></div>
                    </div>
                </div>

                <div class="text-center mb-5">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg px-5"><i class="bi bi-save me-2"></i> Salveaza fisa</button>
                </div>
            </form>

            <style>
                .document-gallery {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 12px;
                    padding: 16px;
                }
                .document-gallery__item {
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #fff;
                    text-decoration: none;
                    color: inherit;
                    transition: border-color .15s ease, box-shadow .15s ease;
                }
                .document-gallery__item:hover {
                    border-color: #2f6f5f;
                    box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, .08);
                }
                .document-gallery__thumb {
                    width: 100%;
                    aspect-ratio: 4 / 3;
                    object-fit: cover;
                    background: #f8fafc;
                    display: block;
                }
                .document-gallery__meta {
                    padding: 8px;
                    min-height: 62px;
                }
                .document-gallery__title {
                    display: block;
                    font-size: .82rem;
                    font-weight: 600;
                    line-height: 1.2;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .document-gallery__type {
                    display: block;
                    margin-top: 4px;
                    font-size: .72rem;
                    color: #64748b;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }
                .document-preview-img {
                    max-height: 78vh;
                    object-fit: contain;
                    background: #0f172a;
                }
                .document-preview-nav {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    z-index: 2;
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(255, 255, 255, .92);
                    border: 1px solid rgba(15, 23, 42, .15);
                    color: #0f172a;
                }
                .document-preview-nav:hover {
                    background: #fff;
                }
                .document-preview-nav--prev {
                    left: 12px;
                }
                .document-preview-nav--next {
                    right: 12px;
                }
            </style>

            <div class="card shadow mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Galerie foto acte</h5>
                    <?php if (!empty($image_documents)): ?><span class="badge text-bg-secondary"><?php echo count($image_documents); ?></span><?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($image_documents)): ?>
                        <div class="p-3 text-muted">Nu exista imagini atasate.</div>
                    <?php else: ?>
                        <div class="document-gallery">
                            <?php foreach ($image_documents as $index => $doc): ?>
                                <?php
                                    $doc_url = 'view-document-social.php?id=' . (int)$doc['id'];
                                    $doc_name = $doc['denumire_fisier'] ?? '';
                                    $doc_type = $doc['tip_document'] ?? '';
                                ?>
                                <a class="document-gallery__item"
                                   href="<?php echo h($doc_url); ?>"
                                   target="_blank"
                                   data-bs-toggle="modal"
                                   data-bs-target="#documentPreviewModal"
                                   data-doc-src="<?php echo h($doc_url); ?>"
                                   data-doc-title="<?php echo h($doc_name); ?>"
                                   data-doc-index="<?php echo (int)$index; ?>">
                                    <img class="document-gallery__thumb" src="<?php echo h($doc_url); ?>" alt="<?php echo h($doc_name); ?>" loading="lazy">
                                    <span class="document-gallery__meta">
                                        <span class="document-gallery__title"><?php echo h($doc_name); ?></span>
                                        <span class="document-gallery__type"><?php echo h($doc_type); ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
                                        <?php $atasamente_ajutor = donatie_get_attachments($conn, (int)($ajutor['ID'] ?? $ajutor['id']), $ajutor['link_act'] ?? ''); ?>
                                        <tr>
                                            <td><?php echo h($ajutor['ID'] ?? $ajutor['id']); ?></td>
                                            <td><?php echo h($ajutor['data']); ?></td>
                                            <td><?php echo h($ajutor['tip_donatie']); ?></td>
                                            <td><?php echo number_format((float)$ajutor['suma_lei'], 2, ',', '.'); ?> lei</td>
                                            <td><?php echo h($ajutor['scop_donatie']); ?></td>
                                            <td>
                                                <?php echo donatie_render_attachment_links($atasamente_ajutor); ?>
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

<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="documentPreviewTitle">Act</h5>
        <a class="btn btn-outline-primary btn-sm ms-auto me-2" id="documentPreviewOpen" href="#" target="_blank">Deschide</a>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Inchide"></button>
      </div>
      <div class="modal-body p-0 text-center bg-dark position-relative">
        <button type="button" class="document-preview-nav document-preview-nav--prev" id="documentPreviewPrev" aria-label="Imaginea anterioara">
            <i class="bi bi-chevron-left"></i>
        </button>
        <img id="documentPreviewImage" class="img-fluid document-preview-img" src="" alt="">
        <button type="button" class="document-preview-nav document-preview-nav--next" id="documentPreviewNext" aria-label="Imaginea urmatoare">
            <i class="bi bi-chevron-right"></i>
        </button>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var previewModal = document.getElementById('documentPreviewModal');
    if (!previewModal) {
        return;
    }

    var galleryItems = Array.prototype.slice.call(document.querySelectorAll('.document-gallery__item'));
    var currentIndex = 0;

    function showDocument(index) {
        if (!galleryItems.length) {
            return;
        }

        currentIndex = (index + galleryItems.length) % galleryItems.length;
        var item = galleryItems[currentIndex];
        var src = item.getAttribute('data-doc-src') || item.getAttribute('href') || '';
        var title = item.getAttribute('data-doc-title') || 'Act';
        var image = document.getElementById('documentPreviewImage');
        var titleNode = document.getElementById('documentPreviewTitle');
        var openLink = document.getElementById('documentPreviewOpen');

        image.src = src;
        image.alt = title;
        titleNode.textContent = title + ' (' + (currentIndex + 1) + '/' + galleryItems.length + ')';
        openLink.href = src;
    }

    previewModal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        var requestedIndex = parseInt(trigger.getAttribute('data-doc-index') || '0', 10);
        showDocument(Number.isNaN(requestedIndex) ? 0 : requestedIndex);
    });

    previewModal.addEventListener('hidden.bs.modal', function () {
        var image = document.getElementById('documentPreviewImage');
        image.src = '';
        image.alt = '';
    });

    document.getElementById('documentPreviewPrev').addEventListener('click', function () {
        showDocument(currentIndex - 1);
    });

    document.getElementById('documentPreviewNext').addEventListener('click', function () {
        showDocument(currentIndex + 1);
    });

    document.addEventListener('keydown', function (event) {
        if (!previewModal.classList.contains('show')) {
            return;
        }

        if (event.key === 'ArrowLeft') {
            showDocument(currentIndex - 1);
        } else if (event.key === 'ArrowRight') {
            showDocument(currentIndex + 1);
        }
    });
});
</script>

<?php include "includes/footer.php"; ?>
