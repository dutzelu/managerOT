<?php
$titlu_pg = "Toate donațiile";
include "includes/header.php"; // Include conexiunea si jQuery

setlocale(LC_ALL, 'ro_RO');

// Anul implicit este anul curent
$an_curent = date("Y");
$an = $_GET['an'] ?? $an_curent; 

// Tipul de donație este fixat pe '%' (toate), deoarece filtrul a fost eliminat
$tip = "%";

// --- 1. EXTRAGEREA ANILOR UNICI DIN BAZA DE DATE ---
$sql_ani = "SELECT DISTINCT YEAR(data) AS an FROM donatii WHERE data IS NOT NULL ORDER BY an DESC";
$rezultate_ani = mysqli_query($conn, $sql_ani);
$ani_disponibili = [];
while ($rand = mysqli_fetch_assoc($rezultate_ani)) {
    $ani_disponibili[] = $rand['an'];
}

// Asigură că anul selectat este valid
if (empty($ani_disponibili)) {
    $an = $an_curent;
} elseif (!in_array($an, $ani_disponibili) && is_numeric($an_curent)) {
    $an = $an_curent;
} elseif (!in_array($an, $ani_disponibili)) {
    $an = $ani_disponibili[0] ?? $an_curent;
}

// --- 2. PRE-CALCULARE TOTALURI PE LUNI (cu filtru de tip și an) ---
$stmt_total = $conn->prepare("
    SELECT 
        MONTH(data) AS luna_numar, 
        ROUND(SUM(suma_lei)) AS total_luna,
        YEAR(data) AS anul
    FROM donatii
    WHERE YEAR(data) = ? 
    AND tip_donatie LIKE ?
    GROUP BY luna_numar, anul
    ORDER BY luna_numar DESC
");

// $tip este acum fixat pe "%"
$stmt_total->bind_param("ss", $an, $tip); 
$stmt_total->execute(); 
$result_total = $stmt_total->get_result();

$total_lunar = [];
$total_anual_sum = 0;

while ($data_total = mysqli_fetch_assoc($result_total)) {
    $luna_key = (int)$data_total['luna_numar'];
    $total_lunar[$luna_key] = $data_total['total_luna'];
    $total_anual_sum += $data_total['total_luna'];
}

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

<div class="row mb-4 align-items-center">
    <div class="col-12">
        <h2 class="mb-3">Toate Donațiile Anul <?php echo htmlspecialchars($an); ?></h2>
            <p class="badge bg-primary align-middle fs-5">
                Total: <?php echo number_format($total_anual_sum, 0, '', '.'); ?> lei
        </p>
    </div>
</div>

<?php 
// --- Mesaj de notificare după ștergere ---
if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] == 'success') {
        $id_stearsa = htmlspecialchars($_GET['id'] ?? 'Necunoscut');
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo 'Donația cu ID-ul **' . $id_stearsa . '** a fost ștearsă cu succes.';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    } elseif ($_GET['deleted'] == 'error') {
        $msg = htmlspecialchars($_GET['msg'] ?? 'Eroare necunoscută.');
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo 'Eroare la ștergerea donației: ' . $msg . '.';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}
?>

<div class="card card-body bg-light mb-4 shadow-sm">
    <form method="GET" action="total-donatii.php" class="row g-3 align-items-end">
        
        <div class="col-md-3">
            <label for="select-an" class="form-label fw-bold">Selectează Anul:</label>
            <select name="an" id="select-an" class="form-select form-select-sm">
                <?php if (empty($ani_disponibili)): ?>
                    <option value="<?php echo $an_curent; ?>"><?php echo $an_curent; ?> (Fără donații)</option>
                <?php else: ?>
                    <?php foreach ($ani_disponibili as $an_opt): ?>
                        <option value="<?php echo $an_opt; ?>" <?php echo ($an_opt == $an) ? 'selected' : ''; ?>>
                            <?php echo $an_opt; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel"></i> Aplică
            </button>
        </div>
        
    </form>
</div>

<?php
// --- 3. QUERY PRINCIPAL PENTRU DETALIILE DONAȚIILOR ---

$stmt = $conn->prepare("
SELECT 
    asistati_social.nume as 'nume',
    asistati_social.prenume as 'prenume',
    donatii.ID as 'id_donatie',
    ROUND(donatii.suma_lei) as 'suma_lei',  
    donatii.tip_donatie,
    donatii.link_act,
    donatii.nr_act_doveditor,
    donatii.scop_donatie,
    donatii.data,
    donatii.proces_verbal,
    donatii.link_proces_verbal
FROM asistati_social
LEFT JOIN donatii ON asistati_social.id = donatii.id_asistat
WHERE YEAR(data) = ? 
AND tip_donatie LIKE ?
ORDER BY donatii.data DESC, donatii.ID DESC 
");

// $tip este acum fixat pe "%"
$stmt->bind_param("ss", $an, $tip); 
$stmt->execute(); 
$result = $stmt->get_result();

// Container pentru Acordeon (în Bootstrap 5)
echo '<div class="accordion" id="accordionExample">';

$ultima_luna_afisata = null;

$month_names_ro = [
    1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie', 
    5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August', 
    9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
];

if ($result->num_rows === 0) {
    echo '<div class="alert alert-info mt-3" role="alert">Nu s-au găsit donații pentru anul ' . htmlspecialchars($an) . '.</div>';
}

$luna_buffer = ''; 
$i = 0;

while ($date = mysqli_fetch_assoc($result)){    

    $nume_complet = $date['nume'] . ' ' . $date['prenume'];
    $data_db = $date['data'];
    $anul = date("Y", strtotime($data_db));
    $luna_numar = (int)date("m", strtotime($data_db));
    $luna_ro = $month_names_ro[$luna_numar] ?? date("F", strtotime($data_db)); 

    
    // --- SCHIMBARE DE LUNĂ: DACA SUNTEM LA O LUNĂ NOUĂ ---
    if ($ultima_luna_afisata != $luna_numar && $ultima_luna_afisata !== null){
        
        // 1. Închide Cardul (Acordeonul) lunii anterioare
        afiseaza_luna_anteriora($ultima_luna_afisata, $luna_buffer, $anul, $total_lunar, $month_names_ro, $i);
        
        // Golește bufferul
        $luna_buffer = '';
        $i++;
    }
    
    $ultima_luna_afisata = $luna_numar;
    
    // --- Populează Bufferul (Detaliile Donației) ---
    
    $suma_formatata_donatie = number_format($date['suma_lei'], 0, '', '.'); 
    $data_formatata = strftime("%d %b. %Y", strtotime($data_db)); 
    
    
    $luna_buffer .= '<li class="list-group-item" id="donatie-' . htmlspecialchars($date['id_donatie']) . '">';
        // ID, Data, Nume și Edit + DELETE (MODIFICAT AICI)
        $luna_buffer .= '<div class="d-flex justify-content-between align-items-center">';
            $luna_buffer .= '<div>';
                $luna_buffer .= '<span class="badge bg-secondary me-2">' . htmlspecialchars($date['id_donatie']) . '</span>';
                $luna_buffer .= '<strong>' . $data_formatata .'</strong> - ';
                $luna_buffer .= htmlspecialchars($nume_complet) . ' ';
                
                // Buton Edit
                $luna_buffer .= '<a href="edit-donatie.php?id=' . htmlspecialchars($date['id_donatie']) . '&persoana=' . urlencode($nume_complet) . '" class="btn btn-sm btn-outline-secondary py-0 px-1 ms-1" title="Editează">';
                $luna_buffer .= '<i class="bi bi-pencil"></i></a>';

                // NOU: Buton Ștergere cu Confirmare JavaScript
                $luna_buffer .= '<a href="delete-donatie.php?id=' . htmlspecialchars($date['id_donatie']) . '" ';
                $luna_buffer .= 'class="btn btn-sm btn-outline-danger py-0 px-1 ms-1" title="Șterge Donația" ';
                $luna_buffer .= 'onclick="return confirm(\'Ești sigur că vrei să ștergi donația cu ID-ul ' . htmlspecialchars($date['id_donatie']) . '?\')">';
                $luna_buffer .= '<i class="bi bi-trash"></i></a>';
            $luna_buffer .= '</div>';
            
            // Suma Donatiei
            $luna_buffer .= '<span class="fw-bold text-primary">' . $suma_formatata_donatie . " lei" . '</span>';
        $luna_buffer .= '</div>';
        
        // Detalii suplimentare
        $luna_buffer .= '<div class="mt-2 small">';
            $luna_buffer .= '<span>Tip: <span class="fw-bold">' . htmlspecialchars($date['tip_donatie']) . '</span></span> - ';
            $luna_buffer .= '<span>Scop: <span class="text-success">' . htmlspecialchars($date['scop_donatie']) . '</span></span>';
        $luna_buffer .= '</div>';
        
        // Documente (Act Doveditor și Proces Verbal)
        $luna_buffer .= '<div class="mt-2 d-flex flex-wrap">';
        
            // Link Act Doveditor
            if (!empty($date['link_act'])) {
                $luna_buffer .= '<span class="me-3">Act: <a href="' . htmlspecialchars($date['link_act']) . '" target="_blank" class="text-decoration-none text-info">';
                $luna_buffer .= '<i class="bi bi-file-earmark-text"></i> ' . htmlspecialchars($date['nr_act_doveditor']) . "</a></span>";
            } else if (!empty($date['nr_act_doveditor'])) {
                $luna_buffer .= '<span class="me-3">Act: ' . htmlspecialchars($date['nr_act_doveditor']) . '</span>';
            }
            
            // Proces Verbal
            if (!empty($date['link_proces_verbal'])){
                 $luna_buffer .= '<span>PV: <a href="' . htmlspecialchars($date['link_proces_verbal']) . '" target="_blank" class="text-decoration-none text-info">';
                 $luna_buffer .= '<i class="bi bi-file-earmark-ruled"></i> ' . htmlspecialchars($date['proces_verbal']) . "</a></span>";
            } else {
                 // Buton creează PV (Albastru Primary)
                 $luna_buffer .= '<a href="proces-verbal-unic.php?iddonatie=' . htmlspecialchars($date['id_donatie']) .  '" class="btn btn-outline-primary btn-sm py-0 px-2">';
                 $luna_buffer .= '<i class="bi bi-file-earmark-ruled"></i> Creează PV</a>';
            }
         
         $luna_buffer .= '</div>';
     
    $luna_buffer .= "</li>";
    
}

// Afisează ultima lună rămasă în buffer după ce bucla s-a terminat
if ($ultima_luna_afisata !== null) {
    afiseaza_luna_anteriora($ultima_luna_afisata, $luna_buffer, $anul, $total_lunar, $month_names_ro, $i);
}
 
echo "</div>"; // Închide div#accordionExample

echo "<script>";
echo "document.addEventListener('DOMContentLoaded', function () {";
echo "  if (!window.location.hash) { return; }";
echo "  var target = document.querySelector(window.location.hash);";
echo "  if (!target) { return; }";
echo "  var collapseEl = target.closest('.accordion-collapse');";
echo "  if (collapseEl) {";
echo "    var collapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });";
echo "    collapse.show();";
echo "  }";
echo "  target.scrollIntoView({ behavior: 'smooth', block: 'center' });";
echo "});";
echo "</script>";

include "includes/footer.php";


/**
 * Funcție pentru a genera HTML-ul pentru un grup lunar (Acordeon)
 * ATENȚIE: Folosește clase și atribute Bootstrap 5.
 */
function afiseaza_luna_anteriora($luna_numar, $luna_buffer, $anul, $total_lunar, $month_names_ro, $i) {
    
    // Numele lunii și ID-ul unic
    $luna_ro = $month_names_ro[$luna_numar] ?? 'Necunoscută';
    $id_unic_luna = 'collapse-' . $anul . '-' . $luna_numar . '-' . $i;
    $header_id = 'heading-' . $luna_numar . '-' . $i;

    
    // Totalul lunar rotunjit
    $luna_total_sum = $total_lunar[$luna_numar] ?? 0;
    $suma_formatata_luna = number_format($luna_total_sum, 0, '', '.');
    
    // Folosim structura Accordion Item (Bootstrap 5)
    echo '<div class="accordion-item">';
    
        // Antetul Cardului (Butonul care se poate deschide)
        echo '<h2 class="accordion-header" id="' . $header_id . '">';
        
            // data-bs-toggle și data-bs-target sunt esențiale pentru Bootstrap 5
            echo '<button class="accordion-button collapsed text-start d-flex" ';
            echo 'type="button" data-bs-toggle="collapse" data-bs-target="#' . $id_unic_luna . '" aria-expanded="false" aria-controls="' . $id_unic_luna . '">';
            
                // Container pentru Numele lunii (va fi aliniat stânga)
                echo '<span class="d-flex align-items-center me-auto">'; 
                    echo '<i class="bi bi-calendar-date-fill me-2"></i>' . htmlspecialchars($luna_ro) . ' ' . htmlspecialchars($anul);
                echo '</span>';
                
                // Totalul (Afișat permanent, folosește clasa ms-auto sau se aliniează automat la dreapta)
                echo '<span class="badge bg-primary fs-6">' . $suma_formatata_luna . ' lei</span>';
                
            echo '</button>';
        echo '</h2>'; // Închide accordion-header
        
        // Conținutul Cardului (Lista de donații - Ascuns inițial)
        echo '<div id="' . $id_unic_luna . '" class="accordion-collapse collapse" aria-labelledby="' . $header_id . '" data-bs-parent="#accordionExample">';
            echo '<div class="accordion-body p-0">'; 
                echo '<ul class="list-group list-group-flush">';
                echo $luna_buffer;
                echo '</ul>';
            echo '</div>'; // Închide accordion-body
        echo '</div>'; // Închide accordion-collapse
        
    echo '</div>'; // Închide accordion-item
}

?>