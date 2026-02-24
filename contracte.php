<?php
$titlu_pg = "Lista Contractelor";
include "includes/header.php"; // Presupune includerea conexiunii la baza de date ($conn)

// --- 1. EXTRAGEREA ANILOR UNICI DIN BAZA DE DATE (Pentru Filtru) ---
$sql_ani = "SELECT DISTINCT YEAR(data_semnarii) AS an FROM contracte WHERE data_semnarii IS NOT NULL ORDER BY an DESC";
$rezultat_ani = $conn->query($sql_ani);
$ani_disponibili = [];
if ($rezultat_ani) {
    while ($row = $rezultat_ani->fetch_assoc()) {
        $ani_disponibili[] = $row['an'];
    }
}

// --- 2. DETERMINAREA ANULUI SELECTAT ---
$an_selectat = $_GET['an'] ?? '';
$an_curent = date("Y");

// Dacă nu este selectat niciun an, dar există ani în DB, selectează cel mai recent.
if (empty($an_selectat) && !empty($ani_disponibili)) {
    $an_selectat = $ani_disponibili[0];
}

// Dacă anul selectat nu este numeric, îl resetăm.
if (!is_numeric($an_selectat)) {
    $an_selectat = '';
}

// --- 3. PREGĂTIREA ȘI EXECUTAREA INTEROGĂRII PRINCIPALE (Securizat) ---
$sql = "SELECT * FROM contracte";
$where_clause = "";
$query_params = [];
$param_types = "";

if (!empty($an_selectat)) {
    $where_clause = " WHERE YEAR(data_semnarii) = ?";
    $query_params[] = $an_selectat;
    $param_types .= "s";
}

$sql .= $where_clause . " ORDER BY data_semnarii DESC, id DESC";

$stmt = $conn->prepare($sql);

if (!empty($query_params)) {
    // Apelăm bind_param dinamic
    $stmt->bind_param($param_types, ...$query_params);
}

$stmt->execute();
$result = $stmt->get_result();
$num_rows = $result->num_rows;
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
            
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-file-earmark-spreadsheet me-2"></i> Lista contractelor
            <?php if (!empty($an_selectat)): ?>
                <span class="badge bg-primary fs-6 ms-2">Anul: <?php echo htmlspecialchars($an_selectat); ?></span>
            <?php endif; ?>
        </h2>
        
        <a href="adauga-contract.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Adaugă Contract
        </a>
    </div>
    
    <div class="card card-body bg-light mb-4 shadow-sm">
        <form method="GET" action="contracte.php" class="row g-3 align-items-end">
            
            <div class="col-md-3">
                <label for="select-an" class="form-label fw-bold">Filtrează după An:</label>
                <select name="an" id="select-an" class="form-select form-select-sm">
                    <option value="">Toți Anii</option>
                    <?php if (empty($ani_disponibili)): ?>
                        <option value="" disabled>Nu există contracte în baza de date</option>
                    <?php else: ?>
                        <?php foreach ($ani_disponibili as $an_opt): ?>
                            <option value="<?php echo $an_opt; ?>" <?php echo ($an_opt == $an_selectat) ? 'selected' : ''; ?>>
                                <?php echo $an_opt; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel"></i> Aplică Filtru
                </button>
            </div>
            
        </form>
    </div>

    <div class="">
        <div class="card-header bg-white fw-bold mb-3">
            Total Contracte Găsite: <?php echo $num_rows; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">ID</th>
                                <th style="width: 10%;">Nr. Contract</th>
                                <th style="width: 25%;">Sponsor</th>
                                <th style="width: 25%;">Beneficiar</th>
                                <th style="width: 15%;">Sumă (Lei)</th>
                                <th style="width: 15%;">Data Semnării</th>
                                <th style="width: 5%;">Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            while ($row = $result->fetch_assoc()):
                                // Formatarea datei
                                $data_formatata = date('d.m.Y', strtotime($row['data_semnarii']));
                                // Formatarea sumei
                                $suma_formatata = number_format($row['suma'], 0, ',', '.');
                        ?>
                            <tr class="clickable-row" data-href="edit-contract.php?id=<?php echo htmlspecialchars($row['id']); ?>">
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['numar']); ?></td>
                                <td><?php echo htmlspecialchars($row['sponsor']); ?></td>
                                <td><?php echo htmlspecialchars($row['beneficiar']); ?></td>
                                <td class="fw-bold text-end"><?php echo $suma_formatata; ?></td>
                                <td><?php echo $data_formatata; ?></td>
                                <td>
                                    <a href="edit-contract.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-outline-primary p-1" title="Editează">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-primary m-3" role="alert">
                    <i class="bi bi-primary-circle me-1"></i> Nu s-au găsit contracte pentru anul <?php echo $an_selectat ? htmlspecialchars($an_selectat) : 'selectat'; ?>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var rows = document.querySelectorAll(".clickable-row");
    rows.forEach(function(row) {
        // Redirecționează pe tot rândul, cu excepția elementelor interactive
        row.addEventListener("click", function(event) {
            // Verifică dacă click-ul nu a fost pe un link sau buton
            if (event.target.tagName !== 'A' && event.target.closest('a') === null && event.target.tagName !== 'BUTTON' && event.target.closest('button') === null) {
                 window.location.href = this.dataset.href;
            }
        });
        // Schimbă cursorul la hover
        row.style.cursor = 'pointer';
    });
});
</script>

<?php 
include "includes/footer.php";
?>