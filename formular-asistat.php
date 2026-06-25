<?php
include __DIR__ . "/includes/conexiune.php";
include __DIR__ . "/includes/functii.php";

social_ensure_external_tables($conn);

function public_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function public_post($key) {
    return trim((string)($_POST[$key] ?? ''));
}

function public_form_value($key, $beneficiar, $fisa) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return public_post($key);
    }
    if (array_key_exists($key, $beneficiar)) {
        return (string)($beneficiar[$key] ?? '');
    }
    return (string)($fisa[$key] ?? '');
}

function public_selected($value, $selected) {
    return ((string)$value === (string)$selected) ? 'selected' : '';
}

function public_checked($value, $csv) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $items = $_POST[$csv] ?? array();
        if (!is_array($items)) {
            $items = array($items);
        }
        return in_array($value, $items, true) ? 'checked' : '';
    }
    return in_array($value, array_map('trim', explode(',', (string)$csv)), true) ? 'checked' : '';
}

function public_render_options($items, $selected = '') {
    foreach ($items as $item) {
        echo '<option value="' . public_h($item) . '" ' . public_selected($item, $selected) . '>' . public_h(ucfirst($item)) . '</option>';
    }
}

function public_render_checks($name, $items, $selected_csv = '') {
    $selected = array_map('trim', explode(',', (string)$selected_csv));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected = $_POST[$name] ?? array();
        if (!is_array($selected)) {
            $selected = array($selected);
        }
    }
    foreach ($items as $item) {
        $id = $name . '-' . preg_replace('/[^a-z0-9]+/i', '-', $item);
        $checked = in_array($item, $selected, true) ? 'checked' : '';
        echo '<div class="form-check form-check-inline">';
        echo '<input class="form-check-input" type="checkbox" name="' . public_h($name) . '[]" id="' . public_h($id) . '" value="' . public_h($item) . '" ' . $checked . '>';
        echo '<label class="form-check-label" for="' . public_h($id) . '">' . public_h($item) . '</label>';
        echo '</div>';
    }
}

function public_store_payload() {
    $payload = array();
    foreach (social_external_beneficiar_fields() as $field) {
        $payload[$field] = public_post($field);
    }
    foreach (social_external_fisa_fields() as $field) {
        if (in_array($field, array('utilitati', 'surse_venit', 'alte_vulnerabilitati', 'tip_sprijin_solicitat'), true)) {
            $payload[$field] = social_join_post_values($field);
        } else {
            $payload[$field] = public_post($field);
        }
    }
    return $payload;
}

$token = $_GET['t'] ?? $_POST['token'] ?? '';
$token = trim((string)$token);
$token_hash = hash('sha256', $token);
$errors = array();
$success = false;
$link = null;
$beneficiar = array();
$fisa = array();

if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $errors[] = 'Link invalid sau incomplet.';
} else {
    $stmt = $conn->prepare("
        SELECT
            l.id AS external_link_id,
            l.beneficiar_id AS external_beneficiar_id,
            l.expires_at AS external_expires_at,
            l.revoked_at AS external_revoked_at,
            a.*
        FROM asistat_external_links l
        JOIN asistati_social a ON a.id = l.beneficiar_id
        WHERE l.token_hash = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $errors[] = 'Link invalid sau expirat.';
    } elseif (!empty($row['external_revoked_at'])) {
        $errors[] = 'Acest link nu mai este activ.';
    } elseif (strtotime($row['external_expires_at']) < time()) {
        $errors[] = 'Acest link a expirat.';
    } else {
        $link = array(
            'id' => $row['external_link_id'],
            'beneficiar_id' => $row['external_beneficiar_id'],
            'expires_at' => $row['external_expires_at']
        );
        $beneficiar = $row;
        $fisa = social_get_current_fisa($conn, (int)$row['beneficiar_id']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $link && empty($errors)) {
    $required = array(
        'nume' => 'Nume',
        'prenume' => 'Prenume',
        'localitate' => 'Localitate',
        'judet' => 'Judet'
    );
    foreach ($required as $field => $label) {
        if (public_post($field) === '') {
            $errors[] = $label . ' este obligatoriu.';
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();
            $payload = public_store_payload();
            $payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $agent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

            $stmt = $conn->prepare("
                INSERT INTO asistat_external_submissions
                (link_id, beneficiar_id, payload_json, submitted_ip, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iisss", $link['id'], $link['beneficiar_id'], $payload_json, $ip, $agent);
            $stmt->execute();
            $submission_id = $stmt->insert_id;
            $stmt->close();

            $document_type = public_post('tip_documente');
            if ($document_type === '') {
                $document_type = 'alte documente';
            }
            $document_notes = trim('Incarcat prin formular extern. ' . public_post('observatii_documente'));
            $fisa_id = !empty($fisa['id']) ? (int)$fisa['id'] : null;
            foreach (social_uploaded_files('documente_sociale') as $file) {
                [$ok, $message] = social_store_document(
                    $conn,
                    $file,
                    (int)$link['beneficiar_id'],
                    $fisa_id,
                    null,
                    $document_type,
                    $document_notes
                );
                if (!$ok) {
                    throw new Exception($message);
                }
            }

            $stmt = $conn->prepare("UPDATE asistat_external_links SET used_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $link['id']);
            $stmt->execute();
            $stmt->close();

            social_log_change($conn, (int)$link['beneficiar_id'], $fisa_id, 'formular extern', 'date beneficiar', '', 'Completare externa #' . $submission_id, 'Date trimise de beneficiar.');

            $conn->commit();
            $success = true;
        } catch (Throwable $e) {
            $conn->rollback();
            $errors[] = 'Datele nu au putut fi salvate: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completare date beneficiar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        body { background: #f6f8fb; color: #1f2937; }
        .public-shell { max-width: 980px; margin: 0 auto; padding: 28px 14px 48px; }
        .public-header { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px; margin-bottom: 18px; }
        .card { border-radius: 8px; }
        .form-check-inline { margin-bottom: .35rem; }
    </style>
</head>
<body>
<main class="public-shell">
    <div class="public-header">
        <h1 class="h4 mb-1">Completare date beneficiar</h1>
        <div class="text-muted">Formular securizat pentru actualizarea datelor si incarcarea actelor.</div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">Datele au fost trimise cu succes. Multumim.</div>
    <?php elseif (!empty($errors) && !$link): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?><div><?php echo public_h($error); ?></div><?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?><div><?php echo public_h($error); ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="token" value="<?php echo public_h($token); ?>">

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Date beneficiar</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label">Nume *</label><input name="nume" class="form-control" value="<?php echo public_h(public_form_value('nume', $beneficiar, $fisa)); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Prenume *</label><input name="prenume" class="form-control" value="<?php echo public_h(public_form_value('prenume', $beneficiar, $fisa)); ?>" required></div>
                    <div class="col-md-4"><label class="form-label">CNP</label><input name="cnp" class="form-control" maxlength="13" value="<?php echo public_h(public_form_value('cnp', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-2"><label class="form-label">Serie CI</label><input name="serie_ci" class="form-control" value="<?php echo public_h(public_form_value('serie_ci', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Numar CI</label><input name="numar_ci" class="form-control" value="<?php echo public_h(public_form_value('numar_ci', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Data nasterii</label><input name="data_nasterii" type="date" class="form-control" value="<?php echo public_h(public_form_value('data_nasterii', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Telefon</label><input name="telefon" type="tel" class="form-control" value="<?php echo public_h(public_form_value('telefon', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?php echo public_h(public_form_value('email', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Stare civila</label><select name="stare_civila" class="form-select"><option value="">--</option><?php public_render_options(array('necasatorit', 'casatorit', 'divortat', 'vaduv'), public_form_value('stare_civila', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-12"><label class="form-label">Adresa completa</label><input name="adresa_completa" class="form-control" value="<?php echo public_h(public_form_value('adresa_completa', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-6"><label class="form-label">Localitate *</label><input name="localitate" class="form-control" value="<?php echo public_h(public_form_value('localitate', $beneficiar, $fisa)); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Judet *</label><input name="judet" class="form-control" value="<?php echo public_h(public_form_value('judet', $beneficiar, $fisa)); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Ocupatie</label><input name="ocupatie" class="form-control" value="<?php echo public_h(public_form_value('ocupatie', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-12"><label class="form-label">Observatii generale</label><textarea name="observatii_generale" class="form-control" rows="3"><?php echo public_h(public_form_value('observatii_generale', $beneficiar, $fisa)); ?></textarea></div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Familie si locuinta</div>
                <div class="card-body row g-3">
                    <div class="col-md-2"><label class="form-label">Total membri</label><input name="nr_total_membri" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_total_membri', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-2"><label class="form-label">Copii minori</label><input name="nr_copii_minori" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_copii_minori', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-2"><label class="form-label">Adulti</label><input name="nr_adulti" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_adulti', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Varstnici</label><input name="nr_varstnici" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_varstnici', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Persoane dizabilitati</label><input name="nr_persoane_dizabilitati" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_persoane_dizabilitati', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-12"><label class="form-label">Persoane aflate in intretinere</label><textarea name="persoane_intretinere" class="form-control" rows="2"><?php echo public_h(public_form_value('persoane_intretinere', $beneficiar, $fisa)); ?></textarea></div>
                    <div class="col-md-4"><label class="form-label">Tip locuinta</label><select name="tip_locuinta" class="form-select"><option value="">--</option><?php public_render_options(social_options('tip_locuinta'), public_form_value('tip_locuinta', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-2"><label class="form-label">Camere</label><input name="nr_camere" type="number" min="0" class="form-control" value="<?php echo public_h(public_form_value('nr_camere', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Conditii</label><select name="conditii_locuire" class="form-select"><option value="">--</option><?php public_render_options(social_options('conditii_locuire'), public_form_value('conditii_locuire', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-3"><label class="form-label">Risc evacuare</label><select name="risc_evacuare" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('risc_evacuare', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-12"><label class="form-label">Utilitati</label><br><?php public_render_checks('utilitati', social_options('utilitati'), public_form_value('utilitati', $beneficiar, $fisa)); ?></div>
                    <div class="col-12"><label class="form-label">Observatii familie/locuinta</label><textarea name="observatii_familie" class="form-control mb-2" rows="2"><?php echo public_h(public_form_value('observatii_familie', $beneficiar, $fisa)); ?></textarea><textarea name="observatii_locuinta" class="form-control" rows="2"><?php echo public_h(public_form_value('observatii_locuinta', $beneficiar, $fisa)); ?></textarea></div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Venituri, sanatate si nevoie de sprijin</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">Venit lunar estimat</label><input name="venit_lunar_estimat" type="number" min="0" step="0.01" class="form-control" value="<?php echo public_h(public_form_value('venit_lunar_estimat', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-4"><label class="form-label">Datorii importante</label><select name="datorii_importante" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('datorii_importante', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-12"><label class="form-label">Surse venit</label><br><?php public_render_checks('surse_venit', social_options('surse_venit'), public_form_value('surse_venit', $beneficiar, $fisa)); ?></div>
                    <div class="col-12"><label class="form-label">Descriere datorii / cheltuieli</label><textarea name="descriere_datorii" class="form-control mb-2" rows="2"><?php echo public_h(public_form_value('descriere_datorii', $beneficiar, $fisa)); ?></textarea><textarea name="cheltuieli_lunare_majore" class="form-control" rows="2"><?php echo public_h(public_form_value('cheltuieli_lunare_majore', $beneficiar, $fisa)); ?></textarea></div>
                    <div class="col-md-4"><label class="form-label">Probleme medicale</label><select name="probleme_medicale" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('probleme_medicale', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Persoane cu dizabilitati</label><select name="persoane_cu_dizabilitati" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('persoane_cu_dizabilitati', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Documente medicale</label><select name="documente_medicale_disponibile" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('documente_medicale_disponibile', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Grad handicap</label><input name="grad_handicap" class="form-control" value="<?php echo public_h(public_form_value('grad_handicap', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-12"><label class="form-label">Alte vulnerabilitati</label><br><?php public_render_checks('alte_vulnerabilitati', social_options('vulnerabilitati'), public_form_value('alte_vulnerabilitati', $beneficiar, $fisa)); ?></div>
                    <div class="col-12"><label class="form-label">Descriere probleme medicale / sociale</label><textarea name="descriere_probleme_medicale" class="form-control mb-2" rows="3"><?php echo public_h(public_form_value('descriere_probleme_medicale', $beneficiar, $fisa)); ?></textarea><textarea name="observatii_sociale" class="form-control" rows="3"><?php echo public_h(public_form_value('observatii_sociale', $beneficiar, $fisa)); ?></textarea></div>
                    <div class="col-12"><label class="form-label">Tip sprijin solicitat</label><br><?php public_render_checks('tip_sprijin_solicitat', social_options('tip_sprijin'), public_form_value('tip_sprijin_solicitat', $beneficiar, $fisa)); ?></div>
                    <div class="col-12"><label class="form-label">Descrierea nevoii</label><textarea name="descriere_nevoie" class="form-control" rows="3"><?php echo public_h(public_form_value('descriere_nevoie', $beneficiar, $fisa)); ?></textarea></div>
                    <div class="col-md-3"><label class="form-label">Urgenta</label><select name="urgenta_caz" class="form-select"><option value="">--</option><?php public_render_options(social_options('urgenta'), public_form_value('urgenta_caz', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-3"><label class="form-label">Suma estimata</label><input name="suma_estimata_necesara" type="number" min="0" step="0.01" class="form-control" value="<?php echo public_h(public_form_value('suma_estimata_necesara', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Perioada sprijin</label><input name="perioada_sprijin" class="form-control" value="<?php echo public_h(public_form_value('perioada_sprijin', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-3"><label class="form-label">Alte surse ajutor</label><select name="alte_surse_ajutor" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu_necunoscut'), public_form_value('alte_surse_ajutor', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-12"><label class="form-label">Detalii alte surse / observatii financiare</label><textarea name="detalii_alte_surse" class="form-control mb-2" rows="2"><?php echo public_h(public_form_value('detalii_alte_surse', $beneficiar, $fisa)); ?></textarea><textarea name="observatii_financiare" class="form-control" rows="2"><?php echo public_h(public_form_value('observatii_financiare', $beneficiar, $fisa)); ?></textarea></div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">Documente si acorduri</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">GDPR informat</label><select name="gdpr_informat" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('gdpr_informat', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Acord GDPR semnat</label><select name="gdpr_semnat" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('gdpr_semnat', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Data acord GDPR</label><input name="data_acord_gdpr" type="date" class="form-control" value="<?php echo public_h(public_form_value('data_acord_gdpr', $beneficiar, $fisa)); ?>"></div>
                    <div class="col-md-6"><label class="form-label">Acord fotografii</label><select name="acord_fotografii" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('acord_fotografii', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-6"><label class="form-label">Acord poveste publica</label><select name="acord_poveste_publica" class="form-select"><option value="">--</option><?php public_render_options(social_options('da_nu'), public_form_value('acord_poveste_publica', $beneficiar, $fisa)); ?></select></div>
                    <div class="col-md-4"><label class="form-label">Tip documente</label><select name="tip_documente" class="form-select"><option value="">Alte documente</option><?php public_render_options(social_options('documente')); ?></select></div>
                    <div class="col-md-8"><label class="form-label">Incarca acte</label><input name="documente_sociale[]" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple></div>
                    <div class="col-12"><label class="form-label">Observatii documente</label><input name="observatii_documente" class="form-control"></div>
                </div>
            </div>

            <div class="d-grid mb-5">
                <button type="submit" class="btn btn-success btn-lg">Trimite datele</button>
            </div>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
