<?php
// campanii.php - Lista Campaniilor
$titlu_pg = "Lista Campaniilor";
// Presupunând că header.php include conexiunea la baza de date ($conn) și gestionează sesiunea
include "includes/header.php";

// --- Logica PHP pentru Anii de Filtrare ---

// 1. Obținerea anilor unici din baza de date
$sql_ani = "SELECT DISTINCT YEAR(data_start) AS an FROM campanii ORDER BY an DESC";
$rezultate_ani = $conn->query($sql_ani);
$ani_disponibili = [];
while ($rand = $rezultate_ani->fetch_assoc()) {
    $ani_disponibili[] = $rand['an'];
}

// 2. Preluare și validare An (Folosim cel mai recent an ca default sau "" dacă nu există campanii)
$filter_year = '';
if (!empty($ani_disponibili)) {
    // Dacă nu e setat GET['an'], folosim cel mai recent an disponibil
    $filter_year = isset($_GET['an']) && is_numeric($_GET['an']) ? (int)$_GET['an'] : $ani_disponibili[0];
}

// 3. Interogare SQL cu Prepared Statement
$sql = "SELECT id, nume, data_start, data_final, descriere, link_ot FROM campanii";

if (!empty($filter_year)) {
    $sql .= " WHERE YEAR(data_start) = ? ";
}

$sql .= " ORDER BY data_start DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Eroare la pregătirea interogării: " . $conn->error);
}

if (!empty($filter_year)) {
    $stmt->bind_param("i", $filter_year);
}

$stmt->execute();
$rezultate = $stmt->get_result();

$campanii = [];
while ($data = $rezultate->fetch_assoc()) {
    $campanii[] = $data;
}

$stmt->close();
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

        <?php 
            $id_sters = $_GET['id'] ?? null;
            if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'succes_sters'): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-trash-fill me-2"></i> 
                    Campania cu ID-ul **<?php echo htmlspecialchars($id_sters); ?>** a fost ștearsă cu succes!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
        <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-megaphone-fill me-2"></i> Lista Campaniilor <?php echo !empty($filter_year) ? " - Anul $filter_year" : ""; ?>
        </h2>
        
        <a href="adauga-campanie.php" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Adaugă Campanie
        </a>
    </div>

    <?php if (!empty($ani_disponibili)): ?>
    <div class="mb-4 d-flex align-items-center">
        <label for="filter_an" class="form-label mb-0 me-3 fw-bold">Filtrează pe an:</label>
        <select class="form-select w-auto" id="filter_an" onchange="window.location.href='campanii.php?an=' + this.value;">
            <?php foreach ($ani_disponibili as $an_opt): ?>
                <option value="<?php echo $an_opt; ?>" <?php echo ($an_opt == $filter_year) ? 'selected' : ''; ?>>
                    <?php echo $an_opt; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    
    <?php if (empty($campanii)): ?>
        <div class="alert alert-info shadow-sm" role="alert">
            Nu s-au găsit campanii pentru anul selectat (<?php echo $filter_year; ?>).
        </div>
    <?php else: ?>
        
        <div class="list-group">
            <?php foreach ($campanii as $campanie): 
                // Formatare date pentru afișare
                $data_start_formatata = date("d M Y", strtotime($campanie['data_start']));
                $data_final_formatata = date("d M Y", strtotime($campanie['data_final']));
                
                // **REMEDIERE TAGURI HTML**
                // Eliminare taguri HTML din descriere pentru snippet
                $snippet = strip_tags($campanie['descriere'] ?? '');
                $descriere_scurta = (strlen($snippet) > 150) ? substr($snippet, 0, 150) . '...' : htmlspecialchars($snippet);
            ?>
                <a href="view-campanie.php?id=<?php echo $campanie['id']; ?>" class="list-group-item list-group-item-action mb-3 shadow-sm border border-secondary-subtle">
                    
                    <h5 class="mb-1 text-primary">
                        <?php echo htmlspecialchars($campanie['nume']); ?>
                    </h5>
                    
                    <p class="mb-1 text-muted">
                        <i class="bi bi-calendar-event me-1"></i>
                        <?php echo $data_start_formatata; ?> - <?php echo $data_final_formatata; ?>
                    </p>
                    
                    <p class="mb-1 text-truncate text-dark">
                        <?php echo $descriere_scurta; ?>
                    </p>
                    
                    <small class="text-secondary">
                        <i class="bi bi-info-circle me-1"></i> Click pentru detalii complete
                    </small>
                </a>
            <?php endforeach; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<?php 
include "includes/footer.php";
?>