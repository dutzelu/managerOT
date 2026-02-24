<?php
// lista-donatii.php - Lista donațiilor către un asistat social
$titlu_pg = "Lista donațiilor";
// Include conexiunea la baza de date ($conn) și funcțiile (inclusiv setlocale)
include "includes/header.php"; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

        <?php

// --- 1. Preluare și validare parametri ---
$id = $_GET['id'] ?? null;
$persoana = $_GET['persoana'] ?? 'Asistat Necunoscut';
$an = $_GET['an'] ?? date("Y"); // Anul selectat (implicit: anul curent)

if (empty($id) || !is_numeric($id)) {
    echo '<div class="container mt-4"><div class="alert alert-info"><i class="bi bi-x-circle-fill me-2"></i> ID-ul asistatului social lipsește sau este invalid.</div></div>';
    include "includes/footer.php";
    exit;
}

// --- 2. Afișare Titlu și Filtre Anuale Dinamice (cu SELECT) ---
echo '<div class="container mt-4 mb-5">';
echo '<div class="d-flex justify-content-between align-items-center mb-4">';
echo '    <h2 class="mb-0">Donații > <span class="text-danger">' . htmlspecialchars($persoana) . '</span></h2>';
echo '    <a href="asistati.php" class="btn btn-outline-secondary btn-sm">';
echo '        <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă Asistați';
echo '    </a>';
echo '</div>';

// Interogare pentru a extrage toți anii unici din tabela donatii
$sql_ani = "SELECT DISTINCT YEAR(data) AS an FROM donatii ORDER BY an DESC";
$result_ani = $conn->query($sql_ani);

// Dacă există donații, afișăm filtrele pe ani (Select Dropdown)
if ($result_ani && $result_ani->num_rows > 0) {
    echo '<div class="mb-4 d-flex align-items-center">';
    echo '    <label for="select-an" class="form-label me-3 mb-0 fw-bold">Filtrează pe an:</label>';
    echo '    <select id="select-an" class="form-select w-auto">';
    
    // Opțiunile dropdown
    while ($row_an = $result_ani->fetch_assoc()) {
        $filter_year = $row_an['an'];
        $selected = ($filter_year == $an) ? 'selected' : '';
        echo '        <option value="' . $filter_year . '" ' . $selected . '>' . $filter_year . '</option>';
    }
    
    echo '    </select>';
    echo '</div>';
    
    // Script JavaScript pentru a gestiona schimbarea anului și redirecționarea
    // Folosim BASE_URL din conexiune.php pentru a ne asigura de calea corectă
    echo '<script>';
    echo 'document.getElementById("select-an").addEventListener("change", function() {';
    echo '    var selectedYear = this.value;';
    echo '    var currentId = ' . json_encode($id) . ';';
    echo '    var currentPersoana = ' . json_encode($persoana) . ';';
    echo '    window.location.href = window.location.pathname + "?id=" + currentId + "&persoana=" + encodeURIComponent(currentPersoana) + "&an=" + selectedYear;';
    echo '});';
    echo '</script>';
}


// --- 3. Interogare Bază de Date (Prepared Statement) ---

// Inițializare variabile pentru totaluri și grupare
$total_anual = 0;
$total_luna = 0;
$ultima_luna_numar = null;
$ultima_luna_nume = null;
$iduri_donatii_luna = []; 

// Coloana de legătură corectă este 'id_asistat'
$sql = "
    SELECT * FROM donatii 
    WHERE id_asistat = ? AND YEAR(data) = ? 
    ORDER BY data DESC
";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo '<div class="alert alert-danger">Eroare la pregătirea interogării SQL: ' . $conn->error . '</div>';
} else {
    // Bind-uim parametrii: i (integer) pentru ID, s (string) pentru An
    $stmt->bind_param("is", $id, $an); 
    
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<ul class="list-group shadow-sm">';

    // --- 4. Iterarea și Afișarea Rezultatelor ---
    if ($result->num_rows > 0) {
        
        while ($data = $result->fetch_assoc()) {
            
            $data_donatie = $data['data'];
            $anul_donatie = date("Y", strtotime($data_donatie));
            // strftime '%B' dă numele complet al lunii în română
            $luna_nume = strftime('%B', strtotime($data_donatie)); 
            $luna_numar = date("m", strtotime($data_donatie));
            $suma_lei = (float)$data['suma_lei'];
            // Presupunem că ID-ul donației este 'ID'
            $id_donatie = $data['ID']; 
            $tip_donatie = $data['tip_donatie'];
            $scop_donatie = $data['scop_donatie'];
            $act_doveditor = $data['act_doveditor'];
            $nr_act_doveditor = $data['nr_act_doveditor'];
            $link_act = $data['link_act'];
            $proces_verbal = $data['proces_verbal'];
            $link_proces_verbal = $data['link_proces_verbal'];
            
            // --- Logica de totalizare și grupare pe Lună ---
            
            // Dacă luna se schimbă, afișăm totalul lunii precedente
            if ($ultima_luna_numar !== $luna_numar && $ultima_luna_numar !== null) {
                
                // Formăm lista de ID-uri pentru link-ul de PV
                $iduri_str = implode(',', $iduri_donatii_luna);
                
                // Afișează totalul lunii precedente (ultima_luna_nume)
                echo '<li class="list-group-item list-group-item-secondary mb-3 d-flex justify-content-between align-items-center">';
                echo '    <h5 class="mb-0 text-uppercase">' . $ultima_luna_nume . ' ' . $anul_donatie . '</h5>';
                echo '    <div>';
                echo '        <span class="badge bg-dark me-3">Total Lună: ' . number_format($total_luna, 2, ',', '.') . ' lei</span>';
                
                // Resetare pentru luna nouă
                $total_luna = 0;
                $iduri_donatii_luna = [];
            }
            
            // Acumulăm datele pentru donația curentă
            $total_luna += $suma_lei;
            $total_anual += $suma_lei;
            $ultima_luna_numar = $luna_numar; 
            $ultima_luna_nume = $luna_nume; 
            $iduri_donatii_luna[] = $id_donatie;

            // --- Afișarea detaliilor donației ---
            echo '<li class="list-group-item">';
            echo '    <div class="d-flex justify-content-between align-items-center">';
            echo '        <div>';
            echo '            <span class="badge bg-secondary me-2">' . $id_donatie . '</span>';
            echo '            <strong>' . strftime('%e %B %Y', strtotime($data_donatie)) . '</strong> ';
            echo '            - <span class="text-success fw-bold">' . number_format($suma_lei, 2, ',', '.') . ' lei</span>';
            echo '            - <span class="text-muted">(' . $tip_donatie . ')</span>';
            echo '            - <span class="text-primary">' . $scop_donatie . '</span>';
            echo '        </div>';
            echo '        <div>';
            // Link de editare
            echo '            <a href="edit-donatie.php?id=' . $id_donatie . '&persoana=' . urlencode($persoana) . '" class="btn btn-sm btn-outline-primary me-2" title="Editează Donație">';
            echo '                <i class="bi bi-pencil-square"></i>';
            echo '            </a>';
            echo '        </div>';
            echo '    </div>';
            
            // Afișare acte doveditoare
            if ($act_doveditor !== 'proces-verbal') {
                echo '<p class="ms-4 mb-0 mt-1 text-sm">';
                echo '    <small>Act Doveditor: ';
                if (empty($link_act)) {
                    echo htmlspecialchars($act_doveditor) . ' - ' . htmlspecialchars($nr_act_doveditor);
                } else {
                    echo htmlspecialchars($act_doveditor) . ' - <a href="' . htmlspecialchars($link_act) . '" target="_blank">' . htmlspecialchars($nr_act_doveditor) . '</a>';
                }
                echo '</small></p>';
            }
            
            // Afișare Proces Verbal existent
            if (!empty($proces_verbal) && !empty($link_proces_verbal)) {
                 echo '<p class="ms-4 mb-0 text-sm"><small class="text-primary">';
                 echo '    <i class="bi bi-file-earmark-check-fill me-1"></i> PV Existent: <a href="' . htmlspecialchars($link_proces_verbal) . '" target="_blank">' . htmlspecialchars($proces_verbal) . '</a>';
                 echo '</small></p>';
            }
            
            echo '</li>';
        }
        
        // --- 5. Afișăm totalul pentru ultima lună din buclă (dacă există date) ---
        if ($ultima_luna_numar !== null) {
            
            $iduri_str = implode(',', $iduri_donatii_luna);
            
            echo '<li class="list-group-item list-group-item-secondary mb-3 d-flex justify-content-between align-items-center">';
            echo '    <h5 class="mb-0 text-uppercase">' . $ultima_luna_nume . ' ' . $anul_donatie . '</h5>';
            echo '    <div>';
            echo '        <span class="badge bg-dark me-3">Total Lună: ' . number_format($total_luna, 2, ',', '.') . ' lei</span>';
            
            echo '        <a href="creeaza-pv.php?id_asistat=' . $id . '&persoana=' . urlencode($persoana) . '&luna_numar=' . $ultima_luna_numar . '&an=' . $anul_donatie . '&iduri_donatii=' . $iduri_str . '" class="btn btn-sm btn-primary text-white">';
            echo '            <i class="bi bi-file-earmark-text me-1"></i> Creează PV pentru ' . $ultima_luna_nume;
            echo '        </a>';
            echo '    </div>';
            echo '</li>';
            
        }

        echo '</ul>';
        
        // --- 6. Afișăm Totalul Anual General ---
        echo '<div class="alert alert-dark mt-4 shadow-sm text-center">';
        echo '    <h4 class="mb-0">TOTAL DONAȚII ÎN ANUL ' . htmlspecialchars($an) . ': <span class="text-success">' . number_format($total_anual, 2, ',', '.') . ' lei</span></h4>';
        echo '</div>';


    } else {
        echo '<div class="alert alert-primary mt-4"><i class="bi bi-primary-circle-fill me-2"></i> Nu au fost găsite donații pentru <span class="text-danger">' . htmlspecialchars($persoana) . '</span> în anul ' . htmlspecialchars($an) . '.</div>';
    }

    $stmt->close();
}

echo '</div>'; // Închide container-ul principal

include "includes/footer.php";
?>