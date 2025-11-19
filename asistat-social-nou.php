<?php 
$titlu_pg ="Formular Asistat Social Nou";
include "header.php"; // Include conexiunea la baza de date ($conn)

// Preluarea numelui asistatului pentru mesajul de succes
$asistat = $_GET['asistat'] ?? null; 
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">          
            <?php include "sidebar.php";?>
        </div>

        <div class="col-12 col-md-9">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-person-plus-fill me-2"></i> Introducere Asistat Social Nou
        </h2>
        
        <a href="asistati.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Înapoi la Listă
        </a>
    </div>

    <?php if (!empty($asistat)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
            <i class="bi bi-check-circle-fill me-2"></i> 
            Asistatul social **"<?php echo htmlspecialchars($asistat); ?>"** a fost introdus cu succes!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Datele Persoanei Asistate</h5>
        </div>
        <div class="card-body">
            
            <form action="update-asistat-social-nou.php" method="post" enctype="multipart/form-data">
                
                <div class="row g-4"> <div class="col-12">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-person-badge me-2"></i> Date de Identificare</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="nume" class="form-label fw-bold">Nume:</label>
                        <input name="nume" id="nume" type="text" class="form-control" placeholder="Nume" required>
                    </div>
                    <div class="col-md-6">
                        <label for="prenume" class="form-label fw-bold">Prenume:</label>
                        <input name="prenume" id="prenume" type="text" class="form-control" placeholder="Prenume" required>
                    </div>

                    <div class="col-md-6">
                        <label for="cnp" class="form-label fw-bold">Cod Numeric Personal (CNP):</label>
                        <input name="cnp" id="cnp" type="text" class="form-control" placeholder="CNP" maxlength="13">
                    </div>
                    <div class="col-md-6">
                        <label for="serie_nr_ci" class="form-label fw-bold">Serie și Număr CI:</label>
                        <input name="serie_nr_ci" id="serie_nr_ci" type="text" class="form-control" placeholder="ex: XT 123456">
                    </div>

                    <div class="col-md-6">
                        <label for="sex" class="form-label fw-bold">Sex:</label>
                        <select name="sex" id="sex" class="form-select">
                            <option value="masculin">Masculin</option>
                            <option value="feminin">Feminin</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="data_nasterii" class="form-label fw-bold">Data Nașterii:</label>
                        <input name="data_nasterii" id="data_nasterii" type="date" class="form-control">
                    </div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-geo-alt-fill me-2"></i> Adresă și Contact</h4>
                    </div>

                    <div class="col-12">
                        <label for="adresa_completa" class="form-label fw-bold">Adresă completă:</label>
                        <input name="adresa_completa" id="adresa_completa" type="text" class="form-control" placeholder="Strada, Numărul, Bloc, Ap.">
                    </div>

                    <div class="col-md-6">
                        <label for="localitate" class="form-label fw-bold">Localitate:</label>
                        <input name="localitate" id="localitate" type="text" class="form-control" placeholder="Localitate">
                    </div>
                    <div class="col-md-6">
                        <label for="judet" class="form-label fw-bold">Județ:</label>
                        <input name="judet" id="judet" type="text" class="form-control" placeholder="Județ">
                    </div>

                    <div class="col-md-6">
                        <label for="telefon" class="form-label fw-bold">Telefon:</label>
                        <input name="telefon" id="telefon" type="tel" class="form-control" placeholder="Număr de telefon">
                    </div>
                    <div class="col-md-6"></div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-house-door-fill me-2"></i> Situație Socială</h4>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="stare_civila" class="form-label fw-bold">Stare civilă:</label>
                        <select name="stare_civila" id="stare_civila" class="form-select">
                            <option value="necasatorit">Necăsătorit/ă</option>
                            <option value="casatorit">Căsătorit/ă</option>
                            <option value="divortat">Divorțat/ă</option>
                            <option value="vaduv">Văduv/ă</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="nr_copii" class="form-label fw-bold">Număr copii în întreținere:</label>
                        <input name="nr_copii" id="nr_copii" type="number" min="0" class="form-control" placeholder="0" value="0">
                    </div>

                    <div class="col-12">
                        <label for="descriere" class="form-label fw-bold">Descriere situație personală:</label>
                        <textarea name="descriere" id="descriere" class="form-control" rows="10" placeholder="Introduceți detaliile situației sociale, medicale, etc."></textarea>
                    </div>


                    <div class="col-12 mt-4">
                        <h4 class="border-bottom pb-2 mb-3 text-primary"><i class="bi bi-file-earmark-text me-2"></i> Documente și Sponsorizare</h4>
                    </div>

                    <div class="col-md-6">
                        <label for="contract_sponsorizare" class="form-label fw-bold">Are contract de sponsorizare?</label>
                        <select name="contract_sponsorizare" id="contract_sponsorizare" class="form-select">
                            <option value="nu">Nu</option>
                            <option value="da">Da</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="link_contract" class="form-label fw-bold">Link Contract Sponsorizare (URL/Drive):</label>
                        <input name="link_contract" id="link_contract" type="url" class="form-control" placeholder="http://">
                    </div>
                    
                    <div class="col-12">
                        <label for="copiebuletin" class="form-label fw-bold">Încarcă copia după buletin (CI):</label>
                        <input type="file" name="copiebuletin" id="copiebuletin" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text">
                            Se va salva o copie pe server și se va genera un link.
                        </div>
                    </div>


                    <div class="col-12 mt-5 text-center">
                        <button type="submit" name="submit" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-person-check-fill me-2"></i> Salvează Asistatul Social
                        </button>
                    </div>

                </div>
            </form>
            
        </div>
    </div>
</div>

<?php 
include "footer.php";
?>