<?php
include __DIR__ . "/includes/conexiune.php";
include __DIR__ . "/includes/functii.php";

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Acces interzis.');
}

$id = $_GET['id'] ?? null;
if (empty($id) || !is_numeric($id)) {
    http_response_code(400);
    exit('ID beneficiar invalid.');
}
$id = (int)$id;

$stmt = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$beneficiar = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$beneficiar) {
    http_response_code(404);
    exit('Beneficiarul nu exista.');
}

$fisa = social_get_current_fisa($conn, $id);

$docs = array();
$stmt_docs = $conn->prepare("SELECT * FROM documente_sociale WHERE beneficiar_id = ? ORDER BY data_incarcarii DESC");
$stmt_docs->bind_param("i", $id);
$stmt_docs->execute();
$result_docs = $stmt_docs->get_result();
while ($row = $result_docs->fetch_assoc()) {
    $docs[] = $row;
}
$stmt_docs->close();

$ajutoare = array();
$stmt_help = $conn->prepare("SELECT * FROM donatii WHERE id_asistat = ? ORDER BY data DESC, ID DESC");
$stmt_help->bind_param("i", $id);
$stmt_help->execute();
$result_help = $stmt_help->get_result();
while ($row = $result_help->fetch_assoc()) {
    $ajutoare[] = $row;
}
$stmt_help->close();

function pdf_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pdf_row($label, $value) {
    return '<tr><th>' . pdf_h($label) . '</th><td>' . nl2br(pdf_h($value)) . '</td></tr>';
}

function pdf_section($title, $rows) {
    $html = '<h2>' . pdf_h($title) . '</h2><table>';
    foreach ($rows as $row) {
        $html .= pdf_row($row[0], $row[1] ?? '');
    }
    return $html . '</table>';
}

$serie_ci = $beneficiar['serie_ci'] ?? '';
$numar_ci = $beneficiar['numar_ci'] ?? '';
if (($serie_ci === '' || $numar_ci === '') && !empty($beneficiar['serie_nr_ci'])) {
    $parts = preg_split('/\s+/', trim($beneficiar['serie_nr_ci']), 2);
    $serie_ci = $serie_ci ?: ($parts[0] ?? '');
    $numar_ci = $numar_ci ?: ($parts[1] ?? '');
}

$html = '
<style>
body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #222; }
h1 { font-size: 18pt; margin-bottom: 4px; }
h2 { font-size: 12pt; margin: 18px 0 6px; padding: 6px; background: #eef3fb; }
table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
th { width: 34%; text-align: left; background: #f7f7f7; }
th, td { border: 1px solid #d7d7d7; padding: 5px; vertical-align: top; }
.small { font-size: 8.5pt; color: #555; }
</style>
<h1>Fisa sociala</h1>
<p class="small">Generata la ' . date('d.m.Y H:i') . '</p>
';

$html .= pdf_section('1. Date beneficiar', array(
    array('ID beneficiar', $beneficiar['id'] ?? ''),
    array('Nume', $beneficiar['nume'] ?? ''),
    array('Prenume', $beneficiar['prenume'] ?? ''),
    array('CNP', $beneficiar['cnp'] ?? ''),
    array('Serie CI', $serie_ci),
    array('Numar CI', $numar_ci),
    array('Data nasterii', $beneficiar['data_nasterii'] ?? ''),
    array('Telefon', $beneficiar['telefon'] ?? ''),
    array('Email', $beneficiar['email'] ?? ''),
    array('Adresa completa', $beneficiar['adresa_completa'] ?? ''),
    array('Localitate', $beneficiar['localitate'] ?? ''),
    array('Judet', $beneficiar['judet'] ?? ''),
    array('Stare civila', $beneficiar['stare_civila'] ?? ''),
    array('Ocupatie', $beneficiar['ocupatie'] ?? ''),
    array('Observatii generale', $beneficiar['observatii_generale'] ?? '')
));

$html .= pdf_section('2. Componenta familiei', array(
    array('Numar total membri', $fisa['nr_total_membri'] ?? ''),
    array('Numar copii minori', $fisa['nr_copii_minori'] ?? ''),
    array('Numar adulti', $fisa['nr_adulti'] ?? ''),
    array('Numar varstnici', $fisa['nr_varstnici'] ?? ''),
    array('Persoane cu dizabilitati', $fisa['nr_persoane_dizabilitati'] ?? ''),
    array('Persoane in intretinere', $fisa['persoane_intretinere'] ?? ''),
    array('Observatii familie', $fisa['observatii_familie'] ?? '')
));

$html .= pdf_section('3. Situatie locativa', array(
    array('Tip locuinta', $fisa['tip_locuinta'] ?? ''),
    array('Numar camere', $fisa['nr_camere'] ?? ''),
    array('Conditii locuire', $fisa['conditii_locuire'] ?? ''),
    array('Utilitati', $fisa['utilitati'] ?? ''),
    array('Risc evacuare', $fisa['risc_evacuare'] ?? ''),
    array('Observatii locuinta', $fisa['observatii_locuinta'] ?? '')
));

$html .= pdf_section('4. Situatie financiara', array(
    array('Venit lunar estimat', $fisa['venit_lunar_estimat'] ?? ''),
    array('Surse venit', $fisa['surse_venit'] ?? ''),
    array('Datorii importante', $fisa['datorii_importante'] ?? ''),
    array('Descriere datorii', $fisa['descriere_datorii'] ?? ''),
    array('Cheltuieli lunare majore', $fisa['cheltuieli_lunare_majore'] ?? ''),
    array('Observatii financiare', $fisa['observatii_financiare'] ?? '')
));

$html .= pdf_section('5. Situatie medicala si vulnerabilitati', array(
    array('Probleme medicale', $fisa['probleme_medicale'] ?? ''),
    array('Descriere probleme medicale', $fisa['descriere_probleme_medicale'] ?? ''),
    array('Persoane cu dizabilitati', $fisa['persoane_cu_dizabilitati'] ?? ''),
    array('Grad handicap', $fisa['grad_handicap'] ?? ''),
    array('Documente medicale disponibile', $fisa['documente_medicale_disponibile'] ?? ''),
    array('Alte vulnerabilitati', $fisa['alte_vulnerabilitati'] ?? ''),
    array('Observatii sociale', $fisa['observatii_sociale'] ?? '')
));

$html .= pdf_section('6. Nevoia de sprijin', array(
    array('Tip sprijin solicitat', $fisa['tip_sprijin_solicitat'] ?? ''),
    array('Descriere nevoie', $fisa['descriere_nevoie'] ?? ''),
    array('Urgenta caz', $fisa['urgenta_caz'] ?? ''),
    array('Suma estimata necesara', $fisa['suma_estimata_necesara'] ?? ''),
    array('Perioada sprijin', $fisa['perioada_sprijin'] ?? ''),
    array('Alte surse ajutor', $fisa['alte_surse_ajutor'] ?? ''),
    array('Detalii alte surse', $fisa['detalii_alte_surse'] ?? '')
));

$html .= pdf_section('7-8. Evaluare si decizie interna', array(
    array('Data evaluarii', $fisa['data_evaluarii'] ?? ''),
    array('Modalitate evaluare', $fisa['modalitate_evaluare'] ?? ''),
    array('Persoana care a recomandat cazul', $fisa['persoana_recomandare'] ?? ''),
    array('Nivel vulnerabilitate', $fisa['nivel_vulnerabilitate'] ?? ''),
    array('Recomandare interna', $fisa['recomandare_interna'] ?? ''),
    array('Motivare recomandare', $fisa['motivare_recomandare'] ?? ''),
    array('Status caz', $fisa['status_caz'] ?? ''),
    array('Data deciziei', $fisa['data_deciziei'] ?? ''),
    array('Tip ajutor aprobat', $fisa['tip_ajutor_aprobat'] ?? ''),
    array('Suma aprobata', $fisa['suma_aprobata'] ?? ''),
    array('Observatii decizie', $fisa['observatii_decizie'] ?? '')
));

$html .= pdf_section('11-12. GDPR si concluzii', array(
    array('Beneficiar informat GDPR', $fisa['gdpr_informat'] ?? ''),
    array('Acord GDPR semnat', $fisa['gdpr_semnat'] ?? ''),
    array('Acord fotografii', $fisa['acord_fotografii'] ?? ''),
    array('Acord poveste publica', $fisa['acord_poveste_publica'] ?? ''),
    array('Data acord GDPR', $fisa['data_acord_gdpr'] ?? ''),
    array('Observatii interne', $fisa['observatii_interne'] ?? ''),
    array('Concluzie sociala', $fisa['concluzie_sociala'] ?? ''),
    array('Recomandare finala', $fisa['recomandare_finala'] ?? '')
));

$html .= '<h2>9. Documente atasate</h2>';
if (empty($docs)) {
    $html .= '<p>Nu exista documente atasate.</p>';
} else {
    $html .= '<table><tr><th>Tip document</th><th>Denumire fisier</th><th>Data incarcarii</th><th>Observatii</th></tr>';
    foreach ($docs as $doc) {
        $html .= '<tr><td>' . pdf_h($doc['tip_document']) . '</td><td>' . pdf_h($doc['denumire_fisier']) . '</td><td>' . pdf_h($doc['data_incarcarii']) . '</td><td>' . pdf_h($doc['observatii']) . '</td></tr>';
    }
    $html .= '</table>';
}

$html .= '<h2>10. Istoric ajutoare acordate</h2>';
if (empty($ajutoare)) {
    $html .= '<p>Nu exista ajutoare acordate.</p>';
} else {
    $html .= '<table><tr><th>ID</th><th>Data</th><th>Tip ajutor</th><th>Valoare</th><th>Mod acordare</th><th>Observatii</th></tr>';
    foreach ($ajutoare as $ajutor) {
        $html .= '<tr>';
        $html .= '<td>' . pdf_h($ajutor['ID'] ?? $ajutor['id'] ?? '') . '</td>';
        $html .= '<td>' . pdf_h($ajutor['data'] ?? '') . '</td>';
        $html .= '<td>' . pdf_h($ajutor['scop_donatie'] ?? '') . '</td>';
        $html .= '<td>' . pdf_h($ajutor['suma_lei'] ?? '') . '</td>';
        $html .= '<td>' . pdf_h($ajutor['mod_acordare'] ?? $ajutor['tip_donatie'] ?? '') . '</td>';
        $html .= '<td>' . pdf_h($ajutor['observatii_ajutor'] ?? '') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
}

require_once __DIR__ . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf(array('tempDir' => __DIR__ . '/vendor/mpdf/mpdf/tmp'));
$mpdf->WriteHTML($html);

$filename = 'Fisa-sociala-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', ($beneficiar['nume'] ?? '') . '-' . ($beneficiar['prenume'] ?? '')) . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
exit;
?>
