<?php 
$titlu_pg ="Campanie nouă";
include "includes/header.php"; // Presupune că include conexiunea ($conn), verificarea sesiunii și Bootstrap 5

// Preluare nume campanie pentru mesajul de succes de la update-adauga-campanie.php
$nume_campanie_succes = $_GET['campanie'] ?? null;
?>


<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "includes/sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0 text-primary">
            <i class="bi bi-megaphone-fill me-2"></i> Adaugă o nouă campanie
        </h2>
        
        <a href="campanii.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la Lista Campaniilor
        </a>
    </div>

    <?php if (!empty($nume_campanie_succes)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i> 
            Campania **"<?php echo htmlspecialchars($nume_campanie_succes); ?>"** a fost introdusă cu succes!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form action="update-adauga-campanie.php" method="post" enctype="multipart/form-data" class="row g-3" novalidate>
            
                <div class="col-12">
                    <label for="nume_campanie" class="form-label fw-bold">Numele Campaniei:</label>
                    <input name="nume" id="nume_campanie" type="text" class="form-control" placeholder="Ex: Campanie de iarnă, Sănătate pentru copii" required>
                </div>
                
                <div class="col-md-6">
                    <label for="data_start" class="form-label fw-bold">Data Start:</label>
                    <input name="data_start" id="data_start" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label for="data_final" class="form-label fw-bold">Data Final:</label>
                    <input name="data_final" id="data_final" type="date" class="form-control">
                    <div class="form-text">Lăsați gol dacă nu este cunoscută data exactă de final.</div>
                </div>


                <div class="col-12">
                    <label for="descriere" class="form-label fw-bold">Descriere Scurtă / Scop:</label>
                    <textarea name="descriere" id="descriere" class="form-control tinymce-editor" rows="3" placeholder="Descriere detaliată a campaniei, cine este vizat și scopul acesteia." required></textarea>
                </div>

                <div class="col-12">
                    <label for="detalii_desf" class="form-label fw-bold">Detalii Desfășurare:</label>
                    <textarea name="detalii_desf" id="detalii_desf" class="form-control tinymce-editor" rows="5" placeholder="Informații suplimentare despre pașii de desfășurare, obiective, etc."></textarea>
                </div>

                
                <div class="col-md-6">
                    <label for="link_ot" class="form-label fw-bold">Link Campanie OT (Site/Facebook):</label>
                    <input name="link_ot" id="link_ot" type="url" class="form-control" placeholder="Link-ul public al campaniei">
                    <div class="form-text">Opțional: Link către pagina campaniei de pe site-ul organizației.</div>
                </div>

                <div class="col-md-6">
                    <label for="logo_campanie" class="form-label fw-bold">Încarcă Logo Campanie:</label>
                    <input type="file" name="logo_campanie" id="logo_campanie" class="form-control" accept=".jpg,.jpeg,.png">
                    <div class="form-text">Opțional: Logo sau o imagine reprezentativă (JPG/PNG).</div>
                </div>

                <div class="col-12 mt-4 text-center">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-save-fill me-2"></i> Salvează Campania
                    </button>
                </div>

            </form>
            
        </div>
    </div>
</div>

<?php 
include "includes/footer.php";
?>