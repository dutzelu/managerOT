<?php
// Generează contractul de sponsorizare (PDF) pe baza datelor firmei,
// îl salvează pe server, îl înregistrează în tabelul `contracte`
// și redirecționează înapoi la sponsorizari.php pentru descărcare.

include "includes/conexiune.php";
include "includes/functii.php";
include "includes/date-asociatie.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sponsorizari.php");
    exit;
}

// honeypot anti-spam: câmp invizibil pentru oameni, completat doar de boți
if (!empty($_POST['website'])) {
    header("Location: sponsorizari.php");
    exit;
}

function redirect_eroare($mesaj) {
    // păstrăm tipul ales, ca formularul redeschis să rămână pe varianta corectă
    $tip = $_POST['tip_sponsorizare'] ?? '';
    $extra = in_array($tip, ['directionare177', 'sponsorizare20'], true) ? "&tip=" . $tip : "";
    header("Location: sponsorizari.php?eroare=" . urlencode($mesaj) . $extra . "#genereaza-contract");
    exit;
}

// --- 1. Preluare și validare ---
$denumire_firma = test_input($_POST['denumire_firma'] ?? '');
$cui            = test_input($_POST['cui'] ?? '');
$nr_reg_com     = test_input($_POST['nr_reg_com'] ?? '');
$sediu          = test_input($_POST['sediu'] ?? '');
$banca          = test_input($_POST['banca'] ?? '');
$iban           = test_input($_POST['iban'] ?? '');
$reprezentant   = test_input($_POST['reprezentant'] ?? '');
$functie        = test_input($_POST['functie'] ?? '');
$email          = test_input($_POST['email'] ?? '');
$telefon        = test_input($_POST['telefon'] ?? '');
$suma           = test_input($_POST['suma'] ?? '');
$data_semnarii  = test_input($_POST['data_semnarii'] ?? '');
$tip_sponsorizare = test_input($_POST['tip_sponsorizare'] ?? '');

if (!in_array($tip_sponsorizare, ['directionare177', 'sponsorizare20'], true)) {
    redirect_eroare("Te rugăm să alegi tipul sponsorizării (Direcționare 177 sau Sponsorizare directă 20%).");
}

if ($denumire_firma === '' || $cui === '' || $nr_reg_com === '' || $sediu === ''
    || $reprezentant === '' || $functie === '' || $email === '') {
    redirect_eroare("Te rugăm să completezi toate câmpurile obligatorii (marcate cu *).");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_eroare("Adresa de email introdusă nu este validă.");
}
if (!is_numeric($suma) || (float)$suma <= 0) {
    redirect_eroare("Suma sponsorizată trebuie să fie un număr mai mare decât zero.");
}
$timestamp_semnare = strtotime($data_semnarii);
if ($timestamp_semnare === false) {
    redirect_eroare("Data semnării nu este validă.");
}

$year     = date("Y", $timestamp_semnare);
$data_rom = date("d.m.Y", $timestamp_semnare);
$suma_formatata = number_format((float)$suma, 2, ',', '.');
// pentru Direcționare 177 se redirecționează impozitul aferent anului fiscal precedent
$an_fiscal_precedent = (int)$year - 1;

// --- 2. Număr de contract (aceeași serie cu contractele din admin) ---
$numar_contract = genereaza_numar_contract($conn, $year);

// --- 3. Conținutul contractului (HTML, folosit și pentru PDF, și stocat în DB) ---
ob_start();
?>
<style>
    body { font-family: sans-serif; font-size: 11pt; color: #111; }
    h1 { font-size: 15pt; text-align: center; margin-bottom: 2px; }
    h2 { font-size: 11.5pt; margin: 18px 0 6px 0; }
    p { margin: 6px 0; text-align: justify; line-height: 1.5; }
    .subtitlu { text-align: center; font-size: 11pt; margin-top: 0; }
    .antet { text-align: right; font-size: 9pt; color: #333; }
    table.semnaturi { width: 100%; margin-top: 40px; }
    table.semnaturi td { width: 50%; vertical-align: top; font-size: 11pt; }
</style>

<p class="antet">
    <?php echo ASOC_DENUMIRE; ?><br>
    <?php echo ASOC_SEDIU; ?><br>
    <?php echo ASOC_EMAIL; ?><br>
    <?php echo ASOC_SITE; ?> | <?php echo ASOC_TELEFON; ?>
</p>

<h1>CONTRACT DE SPONSORIZARE</h1>
<p class="subtitlu">nr. <strong><?php echo $numar_contract; ?></strong> din data de <strong><?php echo $data_rom; ?></strong></p>

<h2>I. PĂRȚILE CONTRACTANTE</h2>
<p>
    <strong>1.1. <?php echo $denumire_firma; ?></strong>, cu sediul în <?php echo $sediu; ?>,
    înregistrată la Oficiul Registrului Comerțului sub nr. <?php echo $nr_reg_com; ?>,
    cod unic de înregistrare <?php echo $cui; ?>,
    <?php if ($iban !== ''): ?>cont bancar (IBAN) <?php echo $iban; ?><?php echo ($banca !== '') ? ', deschis la ' . $banca : ''; ?>,<?php endif; ?>
    e-mail <?php echo $email; ?><?php echo ($telefon !== '') ? ', telefon ' . $telefon : ''; ?>,
    reprezentată legal prin <strong><?php echo $reprezentant; ?></strong>, în calitate de <?php echo $functie; ?>,
    denumită în continuare <strong>„Sponsor"</strong>,
</p>
<p style="text-align:center">și</p>
<p>
    <strong>1.2. <?php echo mb_strtoupper(ASOC_DENUMIRE, 'UTF-8'); ?></strong>, cu sediul în <?php echo ASOC_SEDIU; ?>,
    cod de identificare fiscală <?php echo ASOC_CUI; ?>,
    înscrisă în Registrul asociațiilor și fundațiilor sub nr. <?php echo ASOC_NR_REGISTRU; ?>,
    cont bancar (IBAN) <?php echo ASOC_IBAN; ?>, deschis la <?php echo ASOC_BANCA; ?>,
    reprezentată legal prin <strong><?php echo ASOC_REPREZENTANT; ?></strong>, în calitate de <?php echo ASOC_FUNCTIE; ?>,
    denumită în continuare <strong>„Beneficiar"</strong>,
</p>
<p>
    au convenit încheierea prezentului contract de sponsorizare, în temeiul Legii nr. 32/1994 privind sponsorizarea,
    cu modificările și completările ulterioare.
</p>

<h2>II. OBIECTUL CONTRACTULUI</h2>
<?php if ($tip_sponsorizare === 'directionare177'): ?>
<p>
    <strong>2.1.</strong> Sponsorul acordă Beneficiarului, cu titlu de sponsorizare, suma de
    <strong><?php echo $suma_formatata; ?> lei</strong>, reprezentând impozit pe profit aferent anului fiscal
    <?php echo $an_fiscal_precedent; ?>, rămas nealocat, redirecționat în temeiul art. 42 din Legea nr. 227/2015
    privind Codul fiscal.
</p>
<p>
    <strong>2.2.</strong> Suma prevăzută la art. 2.1 va fi virată în contul Beneficiarului
    <?php echo ASOC_IBAN; ?>, deschis la <?php echo ASOC_BANCA; ?>, de către organul fiscal competent (ANAF),
    în termen de 45 de zile de la depunerea de către Sponsor a Declarației 177 privind redirecționarea
    impozitului pe profit.
</p>
<?php else: ?>
<p>
    <strong>2.1.</strong> Sponsorul acordă Beneficiarului, cu titlu de sponsorizare, suma de
    <strong><?php echo $suma_formatata; ?> lei</strong>, care va fi virată de către Sponsor în contul Beneficiarului
    <?php echo ASOC_IBAN; ?>, deschis la <?php echo ASOC_BANCA; ?>.
</p>
<?php endif; ?>
<p>
    <strong><?php echo ($tip_sponsorizare === 'directionare177') ? '2.3.' : '2.2.'; ?></strong>
    Suma care face obiectul sponsorizării va fi utilizată de Beneficiar pentru susținerea
    activităților sale social-filantropice, educaționale și culturale, în conformitate cu scopul și obiectivele
    sale statutare.
</p>

<h2>III. DURATA CONTRACTULUI</h2>
<p>
    <strong>3.1.</strong> Prezentul contract intră în vigoare la data semnării sale de către ambele părți
    și își produce efectele până la îndeplinirea obligațiilor asumate de fiecare parte.
</p>

<h2>IV. OBLIGAȚIILE PĂRȚILOR</h2>
<?php if ($tip_sponsorizare === 'directionare177'): ?>
<p>
    <strong>4.1.</strong> Sponsorul se obligă să depună la organul fiscal competent Declarația 177 privind
    redirecționarea impozitului pe profit, în termenul prevăzut de lege (25 iunie <?php echo $year; ?>),
    indicând Beneficiarul ca entitate către care se redirecționează suma prevăzută la art. 2.1.
</p>
<?php else: ?>
<p>
    <strong>4.1.</strong> Sponsorul se obligă să vireze suma prevăzută la art. 2.1 în contul Beneficiarului
    până cel târziu la data de 31 decembrie <?php echo $year; ?>.
</p>
<?php endif; ?>
<p>
    <strong>4.2.</strong> Beneficiarul se obligă să utilizeze suma primită exclusiv potrivit destinației
    prevăzute la art. 2.2 și să aducă la cunoștința publicului sponsorizarea, dacă este cazul,
    într-un mod care să nu lezeze, direct sau indirect, activitatea sponsorizată, bunele moravuri sau
    ordinea și liniștea publică, potrivit art. 5 din Legea nr. 32/1994.
</p>

<h2>V. FACILITĂȚI FISCALE</h2>
<?php if ($tip_sponsorizare === 'directionare177'): ?>
<p>
    <strong>5.1.</strong> Redirecționarea sumei prevăzute la art. 2.1 se efectuează în temeiul art. 42 din
    Legea nr. 227/2015 privind Codul fiscal, în limita sumei rămase nealocate din plafonul de sponsorizare
    aferent anului fiscal <?php echo $an_fiscal_precedent; ?> (20% din impozitul pe profit datorat, dar nu mai mult
    de 0,75% din cifra de afaceri), prevăzut la art. 25 alin. (4) lit. i) din Codul fiscal. Plata se efectuează
    de către organul fiscal competent, fără niciun cost suplimentar pentru Sponsor.
</p>
<?php else: ?>
<p>
    <strong>5.1.</strong> În conformitate cu art. 25 alin. (4) lit. i) din Legea nr. 227/2015 privind Codul fiscal,
    Sponsorul scade suma aferentă sponsorizării din impozitul pe profit datorat, în limita a 0,75% din cifra de afaceri,
    dar nu mai mult de 20% din impozitul pe profit datorat.
</p>
<?php endif; ?>

<h2>VI. LITIGII</h2>
<p>
    <strong>6.1.</strong> Eventualele neînțelegeri apărute în legătură cu executarea prezentului contract
    se vor rezolva pe cale amiabilă, iar dacă acest lucru nu este posibil, de către instanțele judecătorești competente.
</p>

<h2>VII. DISPOZIȚII FINALE</h2>
<p>
    <strong>7.1.</strong> Modificarea prezentului contract se poate face numai prin act adițional semnat de ambele părți.
</p>
<p>
    <strong>7.2.</strong> Prezentul contract a fost încheiat astăzi, <?php echo $data_rom; ?>,
    în două exemplare originale, câte unul pentru fiecare parte.
</p>

<table class="semnaturi">
    <tr>
        <td>
            <strong>SPONSOR,</strong><br><br>
            <?php echo $denumire_firma; ?><br>
            <?php echo $reprezentant; ?> — <?php echo $functie; ?><br><br><br>
            Semnătura și ștampila: ______________________
        </td>
        <td>
            <strong>BENEFICIAR,</strong><br><br>
            <?php echo ASOC_DENUMIRE; ?><br>
            <?php echo ASOC_REPREZENTANT; ?> — <?php echo ASOC_FUNCTIE; ?><br><br><br>
            Semnătura și ștampila: ______________________
        </td>
    </tr>
</table>
<?php
$html = ob_get_clean();

// --- 4. Generare PDF cu mPDF (pattern din proces-verbal-unic.php) ---
require_once __DIR__ . '/vendor/autoload.php';

$target_dir = "contracte-sponsorizare/" . $year . "/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// nume de fișier sigur, fără diacritice sau caractere speciale
$firma_slug = replaceSpecialChars($denumire_firma);
$firma_slug = preg_replace('/[^A-Za-z0-9]+/', '-', $firma_slug);
$firma_slug = trim($firma_slug, '-');
$file_name = 'Contract-' . $numar_contract . '-' . $firma_slug . '.pdf';
$link_contract = $target_dir . $file_name;

$mpdf = new \Mpdf\Mpdf(['margin_top' => 15, 'margin_bottom' => 15]);
$mpdf->WriteHTML($html);
$mpdf->Output($link_contract, \Mpdf\Output\Destination::FILE);

if (!file_exists($link_contract)) {
    redirect_eroare("A apărut o problemă la generarea PDF-ului. Te rugăm să încerci din nou sau să ne contactezi.");
}

// --- 5. Înregistrare în tabelul `contracte` (apare în lista din admin) ---
$sql = "INSERT INTO contracte (numar, continut, sponsor, beneficiar, suma, data_semnarii, link_contract)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$beneficiar = ASOC_DENUMIRE;
$stmt->bind_param("sssssss",
    $numar_contract,
    $html,
    $denumire_firma,
    $beneficiar,
    $suma,
    $data_semnarii,
    $link_contract
);

if (!$stmt->execute()) {
    redirect_eroare("Contractul a fost generat, dar nu a putut fi înregistrat. Te rugăm să ne contactezi.");
}
$stmt->close();

// --- 6. Redirect PRG: pagina afișează mesajul de succes și pornește descărcarea ---
header("Location: sponsorizari.php?succes=" . urlencode($numar_contract)
     . "&pdf=" . urlencode($link_contract) . "#genereaza-contract");
exit;
?>
