<?php
$titlu_pg = "Salvare fisa sociala";
ob_start();

require_once __DIR__ . "/includes/conexiune.php";
require_once __DIR__ . "/includes/functii.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: " . BASE_URL . "login/login.php");
    exit;
}

setlocale(LC_TIME, array('ro.utf-8', 'ro_RO.UTF-8', 'ro_RO.utf-8', 'ro', 'ro_RO', 'ro_RO.ISO8859-2'));

function social_post($key) {
    return trim((string)($_POST[$key] ?? ''));
}

function social_null_post($key) {
    $value = social_post($key);
    return $value === '' ? null : $value;
}

function social_bind_params($stmt, $types, &$values) {
    $refs = array($types);
    foreach ($values as $key => &$value) {
        $refs[] = &$value;
    }
    return call_user_func_array(array($stmt, 'bind_param'), $refs);
}

function social_insert_row($conn, $table, $data) {
    $columns = array_keys($data);
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $values = array_values($data);
    $types = str_repeat('s', count($values));
    social_bind_params($stmt, $types, $values);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

if (!isset($_POST['submit'])) {
    include "includes/header.php";
    echo '<div class="container mt-4"><div class="alert alert-warning">Acces invalid.</div></div>';
    include "includes/footer.php";
    exit;
}

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
foreach ($required as $key => $label) {
    if (social_post($key) === '') {
        $errors[] = "$label este obligatoriu.";
    }
}

$tip_sprijin = social_join_post_values('tip_sprijin_solicitat');
if ($tip_sprijin === '') {
    $errors[] = 'Tip sprijin solicitat este obligatoriu.';
}

if (!empty($errors)) {
    include "includes/header.php";
    echo '<div class="container mt-4"><div class="alert alert-danger"><ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul><a href="asistat-social-nou.php" class="btn btn-primary mt-2">Inapoi la formular</a></div></div>';
    include "includes/footer.php";
    exit;
}

try {
    $conn->begin_transaction();

    $serie_ci = social_post('serie_ci');
    $numar_ci = social_post('numar_ci');
    $serie_nr_ci = trim($serie_ci . ' ' . $numar_ci);
    $nr_copii_minori = social_post('nr_copii_minori');
    $observatii_sociale = social_post('observatii_sociale');
    $concluzie_sociala = social_post('concluzie_sociala');

    $beneficiar = array(
        'nume' => social_post('nume'),
        'prenume' => social_post('prenume'),
        'cnp' => social_post('cnp'),
        'serie_ci' => $serie_ci,
        'numar_ci' => $numar_ci,
        'serie_nr_ci' => $serie_nr_ci,
        'data_nasterii' => social_null_post('data_nasterii'),
        'telefon' => social_post('telefon'),
        'email' => social_post('email'),
        'adresa_completa' => social_post('adresa_completa'),
        'localitate' => social_post('localitate'),
        'judet' => social_post('judet'),
        'stare_civila' => social_post('stare_civila'),
        'ocupatie' => social_post('ocupatie'),
        'observatii_generale' => social_post('observatii_generale'),
        'nr_copii' => $nr_copii_minori === '' ? 0 : $nr_copii_minori,
        'descriere' => $observatii_sociale,
        'descriere_scurta' => $concluzie_sociala
    );
    $beneficiar_id = social_insert_row($conn, 'asistati_social', $beneficiar);

    $fisa = array(
        'beneficiar_id' => $beneficiar_id,
        'nr_total_membri' => social_null_post('nr_total_membri'),
        'nr_copii_minori' => $nr_copii_minori === '' ? null : $nr_copii_minori,
        'nr_adulti' => social_null_post('nr_adulti'),
        'nr_varstnici' => social_null_post('nr_varstnici'),
        'nr_persoane_dizabilitati' => social_null_post('nr_persoane_dizabilitati'),
        'persoane_intretinere' => social_post('persoane_intretinere'),
        'observatii_familie' => social_post('observatii_familie'),
        'tip_locuinta' => social_post('tip_locuinta'),
        'nr_camere' => social_null_post('nr_camere'),
        'conditii_locuire' => social_post('conditii_locuire'),
        'utilitati' => social_join_post_values('utilitati'),
        'risc_evacuare' => social_post('risc_evacuare'),
        'observatii_locuinta' => social_post('observatii_locuinta'),
        'venit_lunar_estimat' => social_null_post('venit_lunar_estimat'),
        'surse_venit' => social_join_post_values('surse_venit'),
        'datorii_importante' => social_post('datorii_importante'),
        'descriere_datorii' => social_post('descriere_datorii'),
        'cheltuieli_lunare_majore' => social_post('cheltuieli_lunare_majore'),
        'observatii_financiare' => social_post('observatii_financiare'),
        'probleme_medicale' => social_post('probleme_medicale'),
        'descriere_probleme_medicale' => social_post('descriere_probleme_medicale'),
        'persoane_cu_dizabilitati' => social_post('persoane_cu_dizabilitati'),
        'grad_handicap' => social_post('grad_handicap'),
        'documente_medicale_disponibile' => social_post('documente_medicale_disponibile'),
        'alte_vulnerabilitati' => social_join_post_values('alte_vulnerabilitati'),
        'observatii_sociale' => $observatii_sociale,
        'tip_sprijin_solicitat' => $tip_sprijin,
        'descriere_nevoie' => social_post('descriere_nevoie'),
        'urgenta_caz' => social_post('urgenta_caz'),
        'suma_estimata_necesara' => social_null_post('suma_estimata_necesara'),
        'perioada_sprijin' => social_post('perioada_sprijin'),
        'alte_surse_ajutor' => social_post('alte_surse_ajutor'),
        'detalii_alte_surse' => social_post('detalii_alte_surse'),
        'data_evaluarii' => social_null_post('data_evaluarii'),
        'modalitate_evaluare' => social_post('modalitate_evaluare'),
        'persoana_recomandare' => social_post('persoana_recomandare'),
        'nivel_vulnerabilitate' => social_post('nivel_vulnerabilitate'),
        'recomandare_interna' => social_post('recomandare_interna'),
        'motivare_recomandare' => social_post('motivare_recomandare'),
        'status_caz' => social_post('status_caz'),
        'data_deciziei' => social_null_post('data_deciziei'),
        'tip_ajutor_aprobat' => social_post('tip_ajutor_aprobat'),
        'suma_aprobata' => social_null_post('suma_aprobata'),
        'observatii_decizie' => social_post('observatii_decizie'),
        'gdpr_informat' => social_post('gdpr_informat'),
        'gdpr_semnat' => social_post('gdpr_semnat'),
        'acord_fotografii' => social_post('acord_fotografii'),
        'acord_poveste_publica' => social_post('acord_poveste_publica'),
        'data_acord_gdpr' => social_null_post('data_acord_gdpr'),
        'observatii_interne' => social_post('observatii_interne'),
        'concluzie_sociala' => $concluzie_sociala,
        'recomandare_finala' => social_post('recomandare_finala')
    );
    $fisa_id = social_insert_row($conn, 'fise_sociale', $fisa);

    social_log_change($conn, $beneficiar_id, $fisa_id, 'creare', 'fisa sociala', '', 'Fisa sociala initiala', 'Beneficiar creat.');

    $tip_document = social_post('tip_document');
    if ($tip_document === '' && isset($_FILES['document_social']) && ($_FILES['document_social']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $tip_document = 'alte documente';
    }
    if ($tip_document !== '') {
        [$ok, $message] = social_store_document(
            $conn,
            $_FILES['document_social'] ?? null,
            $beneficiar_id,
            $fisa_id,
            null,
            $tip_document,
            social_post('observatii_document')
        );
        if (!$ok) {
            throw new Exception($message);
        }
    }

    $conn->commit();
    $nume_complet = $beneficiar['nume'] . ' ' . $beneficiar['prenume'];
    if (ob_get_length() !== false) {
        ob_clean();
    }
    header("Location: asistat-social-nou.php?asistat=" . urlencode($nume_complet));
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    include "includes/header.php";
    echo '<div class="container mt-4">';
    echo '<div class="alert alert-danger">';
    echo '<h4>Eroare la salvarea fisei</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<a href="asistat-social-nou.php" class="btn btn-primary">Inapoi la formular</a>';
    echo '</div></div>';
}

include "includes/footer.php";
?>
