<?php
// view-campanie.php - Vizualizare Detalii Campanie
// Include conexiunea la baza de date ($conn)
include "includes/header.php";

$id = $_GET['id'] ?? null;
$campanie = null;
$titlu_pg = "Detalii Campanie";

// --- Logica PHP ---

// 1. Validare ID
if (empty($id) || !is_numeric($id)) {
    echo '<div class="container mt-4"><div class="alert alert-danger">ID-ul campaniei lipsește sau este invalid.</div></div>';
    include "includes/footer.php";
    exit();
}

// 2. Interogare SQL cu Prepared Statement
$sql = "SELECT id, nume, data_start, data_final, descriere, detalii_desf, link_ot FROM campanii WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('<div class="container mt-4"><div class="alert alert-danger">Eroare la pregătirea interogării: ' . $conn->error . '</div></div>');
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $campanie = $result->fetch_assoc();
    $titlu_pg = "Campania: " . htmlspecialchars($campanie['nume']);
}

$stmt->close();
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <?php 
            if ($campanie) {
                echo '<i class="bi bi-eye-fill me-2"></i>' . htmlspecialchars($campanie['nume']);
            } else {
                echo '<i class="bi bi-x-circle-fill me-2"></i> Campanie Negăsită';
            }
            ?>
        </h1>
        
        <div class="btn-group" role="group">
             <a href="campanii.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Înapoi
            </a>
            <?php if ($campanie): ?>
                 <a href="edit-campanie.php?id=<?php echo htmlspecialchars($id); ?>" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Editează
                </a>
                
                <button type="button" 
                         class="btn btn-danger" 
                         onclick="confirmDelete(<?php echo htmlspecialchars($id); ?>, '<?php echo htmlspecialchars($campanie['nume']); ?>')">
                    <i class="bi bi-trash me-1"></i> Șterge
                </button>
                
            <?php endif; ?>
        </div>
    </div>

    <?php if ($campanie): ?>
        
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Detalii Campanie (ID: <?php echo htmlspecialchars($campanie['id']); ?>)</h5>
            </div>
            <div class="card-body">
                
                <div class="mb-4 p-3 border rounded bg-light">
                    
                    <h5 class="text-primary"><i class="bi bi-calendar-event me-2"></i> Perioada</h5>
                    <p class="lead">
                        <?php 
                            $data_start_formatata = strftime('%d %B %Y', strtotime($campanie['data_start']));
                            $data_final_formatata = strftime('%d %B %Y', strtotime($campanie['data_final']));
                            echo "{$data_start_formatata} - {$data_final_formatata}";
                        ?>
                    </p>
                    
                    <h5 class="text-primary mt-3"><i class="bi bi-link-45deg me-2"></i> Link către OT</h5>
                    <p class="mb-0">
                        <?php if (!empty($campanie['link_ot'])): ?>
                            <a href="<?php echo htmlspecialchars($campanie['link_ot']); ?>" target="_blank" class="text-decoration-none text-break">
                                <?php echo htmlspecialchars($campanie['link_ot']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </p>
                    
                </div>

                <div class="mt-4">
                    <h4 class="border-bottom pb-2 mb-3 text-danger"><i class="bi bi-text-paragraph me-2"></i> Descriere (Scop)</h4>
                    
                    <div class="p-3 border rounded bg-white">
                        <?php echo htmlspecialchars_decode($campanie['descriere'] ?? '<span class="text-muted">Nu există descriere.</span>', ENT_QUOTES); ?>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h4 class="border-bottom pb-2 mb-3 text-danger"><i class="bi bi-list-task me-2"></i> Detalii desfășurare și rezultate</h4>
                    
                    <div class="p-3 border rounded bg-white">
                         <?php echo htmlspecialchars_decode($campanie['detalii_desf'] ?? '<span class="text-muted">Nu există detalii de desfășurare.</span>', ENT_QUOTES); ?>
                    </div>
                </div>

            </div>
        </div>
        
    <?php else: ?>
        <div class="alert alert-danger shadow" role="alert">
            Nu a fost găsită nicio campanie cu ID-ul **<?php echo htmlspecialchars($id); ?>**.
        </div>
    <?php endif; ?>
    
</div>

 <script>

/* Funcție de Confirmare Ștergere Campanie */
function confirmDelete(id, nume) {
    if (confirm('Ești sigur(ă) că vrei să ștergi campania "' + nume + '" (ID: ' + id + ')? Această acțiune este ireversibilă!')) {
        // Redirecționare către scriptul de ștergere cu ID-ul campaniei
        window.location.href = 'sterge-campanie.php?id=' + id;
    }
}
</script>

<?php 
include "includes/footer.php";
?>