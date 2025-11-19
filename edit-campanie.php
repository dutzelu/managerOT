<?php
// edit-campanie.php - Editare Campanie
$titlu_pg = "Editare Campanie";
// Presupunând că header.php include conexiunea la baza de date ($conn)
include "header.php"; 

$campanie = null;
$errors = [];
$succes_msg = $_GET['succes'] ?? ''; // Mesaj de succes din redirect
$id = $_GET['id'] ?? null;

// --- 1. PROCESARE FORMULAR (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id_edit = (int)$_POST['id'];
    
    // Preluare și sanitizare/validare date din formular
    $nume         = trim($_POST['nume'] ?? '');
    $data_start   = $_POST['data_start'] ?? '';
    $data_final   = $_POST['data_final'] ?? '';
    $descriere    = $_POST['descriere'] ?? ''; // Conținutul TinyMCE
    $detalii_desf = $_POST['detalii_desf'] ?? ''; // Conținutul TinyMCE
    $link_ot      = trim($_POST['link_ot'] ?? '');

    // Validare
    if (empty($nume)) { $errors[] = "Numele campaniei este obligatoriu."; }
    if (empty($data_start)) { $errors[] = "Data de start este obligatorie."; }
    if (empty($data_final)) { $errors[] = "Data finală este obligatorie."; }
    if (!empty($data_start) && !empty($data_final) && $data_start > $data_final) { 
        $errors[] = "Data de start nu poate fi după data finală."; 
    }

    if (empty($errors)) {
        // Interogare UPDATE cu Prepared Statement (SECURITATE!)
        $sql_update = "UPDATE campanii SET nume=?, data_start=?, data_final=?, descriere=?, detalii_desf=?, link_ot=? WHERE id=?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update === false) {
             $errors[] = "Eroare la pregătirea interogării de actualizare: " . $conn->error;
        } else {
            // "ssssssi" - șase stringuri, un integer
            $stmt_update->bind_param("ssssssi", $nume, $data_start, $data_final, $descriere, $detalii_desf, $link_ot, $id_edit);
            
            if ($stmt_update->execute()) {
                // Redirecționare cu mesaj de succes
                header("Location: edit-campanie.php?id=" . $id_edit . "&succes=Campania a fost actualizată cu succes!");
                exit();
            } else {
                $errors[] = "Eroare la executarea actualizării: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
}


// --- 2. PRELUARE DATE PENTRU AFIȘARE (SELECT) ---

// Dacă s-a făcut POST, luăm ID-ul din POST (este mai sigur)
$id_to_fetch = (isset($_POST['id']) && is_numeric($_POST['id'])) ? (int)$_POST['id'] : ((isset($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : null);

if (empty($id_to_fetch)) {
    echo '<div class="container mt-4"><div class="alert alert-danger">ID-ul campaniei lipsește sau este invalid.</div></div>';
    include "footer.php";
    exit();
}

// Interogare SELECT cu Prepared Statement
$sql_select = "SELECT id, nume, data_start, data_final, descriere, detalii_desf, link_ot FROM `campanii` WHERE `id` = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id_to_fetch);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result->num_rows > 0) {
    $campanie = $result->fetch_assoc();
    $titlu_pg = "Editare Campania: " . htmlspecialchars($campanie['nume']);
} else {
    echo '<div class="container mt-4"><div class="alert alert-danger">Campania cu ID-ul ' . htmlspecialchars($id_to_fetch) . ' nu a fost găsită.</div></div>';
    include "footer.php";
    exit();
}
$stmt_select->close();

// Setăm variabilele locale din campanie pentru a fi folosite în formular (dacă nu e POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $nume         = $campanie['nume'];
    $data_start   = $campanie['data_start'];
    $data_final   = $campanie['data_final'];
    $descriere    = $campanie['descriere'];
    $detalii_desf = $campanie['detalii_desf'];
    $link_ot      = $campanie['link_ot'];
}

?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-pencil-square me-2"></i> Edit campanie (id <span class="text-danger"><?php echo htmlspecialchars($campanie['id']); ?>)</span></h1>
        <a href="view-campanie.php?id=<?php echo htmlspecialchars($campanie['id']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-eye-fill me-1"></i> Vezi Campanie
        </a>
    </div>

    <?php if (!empty($succes_msg)): ?>
        <div id="success-form" class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($succes_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <p><i class="bi bi-exclamation-triangle-fill me-2"></i> **Eroare:**</p>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="edit-campanie.php" method="post" class="card shadow p-4">
        
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($campanie['id']); ?>">

        <div class="mb-3">
            <label for="nume" class="form-label fw-bold"><i class="bi bi-tag-fill me-1"></i> Nume Campanie:</label>
            <input name="nume" type="text" class="form-control form-control-lg" id="nume" value="<?php echo htmlspecialchars($nume); ?>" required>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label for="data_start" class="form-label fw-bold"><i class="bi bi-calendar-check-fill me-1"></i> Data Start:</label>
                <input name="data_start" type="date" class="form-control" id="data_start" value="<?php echo htmlspecialchars($data_start); ?>" required>
            </div>
            
            <div class="col-md-6">
                <label for="data_final" class="form-label fw-bold"><i class="bi bi-calendar-x-fill me-1"></i> Data Final:</label>
                <input name="data_final" type="date" class="form-control" id="data_final" value="<?php echo htmlspecialchars($data_final); ?>" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="descriere" class="form-label fw-bold"><i class="bi bi-file-earmark-text-fill me-1"></i> Descriere (Scop donație):</label>
            <textarea name="descriere" class="form-control" id="descriere" rows="8"><?php echo htmlspecialchars_decode($descriere ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="detalii_desf" class="form-label fw-bold"><i class="bi bi-list-task me-1"></i> Detalii desfășurare și rezultate:</label>
            <textarea name="detalii_desf" class="form-control" id="detalii_desf" rows="12"><?php echo htmlspecialchars_decode($detalii_desf ?? '', ENT_QUOTES); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="link_ot" class="form-label fw-bold"><i class="bi bi-link-45deg me-1"></i> Link OT (Ordin de Taină):</label>
            <input type="url" name="link_ot" class="form-control" id="link_ot" value="<?php echo htmlspecialchars($link_ot); ?>" placeholder="Introduceți un URL valid">
        </div>

        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-2"></i> Salvează Modificările
            </button>
        </div>
        
    </form>
</div>

<?php 
// Presupunând că footer.php include și inițializarea TinyMCE
include "footer.php";
?>