<?php
$titlu_pg = "Fisa sociala noua";
include "includes/header.php";

$asistat = $_GET['asistat'] ?? null;

function render_options($items, $selected = '') {
    foreach ($items as $item) {
        $is_selected = ((string)$item === (string)$selected) ? 'selected' : '';
        echo '<option value="' . htmlspecialchars($item) . '" ' . $is_selected . '>' . htmlspecialchars(ucfirst($item)) . '</option>';
    }
}

function render_checks($name, $items, $selected_csv = '') {
    $selected = array_map('trim', explode(',', (string)$selected_csv));
    foreach ($items as $item) {
        $id = $name . '-' . preg_replace('/[^a-z0-9]+/i', '-', $item);
        $checked = in_array($item, $selected, true) ? 'checked' : '';
        echo '<div class="form-check form-check-inline">';
        echo '<input class="form-check-input" type="checkbox" name="' . htmlspecialchars($name) . '[]" id="' . htmlspecialchars($id) . '" value="' . htmlspecialchars($item) . '" ' . $checked . '>';
        echo '<label class="form-check-label" for="' . htmlspecialchars($id) . '">' . htmlspecialchars($item) . '</label>';
        echo '</div>';
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-3 d-none d-md-block">
            <?php include "includes/sidebar.php"; ?>
        </div>

        <div class="col-12 col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-person-plus-fill me-2"></i> Fisa sociala noua</h2>
                <a href="asistati.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Inapoi la lista
                </a>
            </div>

            <?php if (!empty($asistat)): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="success-form">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Fisa pentru <strong><?php echo htmlspecialchars($asistat); ?></strong> a fost salvata.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="update-asistat-social-nou.php" method="post" enctype="multipart/form-data">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Date beneficiar</h5>
                    </div>
                    <div class="card-body row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nume *</label>
                            <input name="nume" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Prenume *</label>
                            <input name="prenume" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">CNP *</label>
                            <input name="cnp" type="text" class="form-control" maxlength="13" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Serie CI</label>
                            <input name="serie_ci" type="text" class="form-control" maxlength="10">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Numar CI</label>
                            <input name="numar_ci" type="text" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data nasterii</label>
                            <input name="data_nasterii" type="date" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefon</label>
                            <input name="telefon" type="tel" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input name="email" type="email" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Stare civila</label>
                            <select name="stare_civila" class="form-select">
                                <?php render_options(array('necasatorit', 'casatorit', 'divortat', 'vaduv')); ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adresa completa</label>
                            <input name="adresa_completa" type="text" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Localitate *</label>
                            <input name="localitate" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Judet *</label>
                            <input name="judet" type="text" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ocupatie</label>
                            <input name="ocupatie" type="text" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observatii generale</label>
                            <textarea name="observatii_generale" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">2. Componenta familiei</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-2"><label class="form-label">Total membri</label><input name="nr_total_membri" type="number" min="0" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Copii minori</label><input name="nr_copii_minori" type="number" min="0" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Adulti</label><input name="nr_adulti" type="number" min="0" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Varstnici</label><input name="nr_varstnici" type="number" min="0" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Persoane cu dizabilitati</label><input name="nr_persoane_dizabilitati" type="number" min="0" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Persoane aflate in intretinere</label><textarea name="persoane_intretinere" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii despre familie</label><textarea name="observatii_familie" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">3. Situatie locativa</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tip locuinta</label>
                            <select name="tip_locuinta" class="form-select"><option value="">--</option><?php render_options(social_options('tip_locuinta')); ?></select>
                        </div>
                        <div class="col-md-2"><label class="form-label">Numar camere</label><input name="nr_camere" type="number" min="0" class="form-control"></div>
                        <div class="col-md-3">
                            <label class="form-label">Conditii</label>
                            <select name="conditii_locuire" class="form-select"><option value="">--</option><?php render_options(social_options('conditii_locuire')); ?></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Risc evacuare</label>
                            <select name="risc_evacuare" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Utilitati</label><br>
                            <?php render_checks('utilitati', social_options('utilitati')); ?>
                        </div>
                        <div class="col-12"><label class="form-label">Observatii locuinta</label><textarea name="observatii_locuinta" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">4. Situatie financiara</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Venit lunar estimat</label><input name="venit_lunar_estimat" type="number" min="0" step="0.01" class="form-control"></div>
                        <div class="col-md-4">
                            <label class="form-label">Datorii importante</label>
                            <select name="datorii_importante" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Surse venit</label><br>
                            <?php render_checks('surse_venit', social_options('surse_venit')); ?>
                        </div>
                        <div class="col-12"><label class="form-label">Descriere datorii</label><textarea name="descriere_datorii" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label">Cheltuieli lunare majore</label><textarea name="cheltuieli_lunare_majore" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii financiare</label><textarea name="observatii_financiare" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">5. Situatie medicala si vulnerabilitati</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Probleme medicale</label><select name="probleme_medicale" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Persoane cu dizabilitati</label><select name="persoane_cu_dizabilitati" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Documente medicale disponibile</label><select name="documente_medicale_disponibile" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Grad handicap</label><input name="grad_handicap" type="text" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Alte vulnerabilitati</label><br><?php render_checks('alte_vulnerabilitati', social_options('vulnerabilitati')); ?></div>
                        <div class="col-12"><label class="form-label">Descriere probleme medicale</label><textarea name="descriere_probleme_medicale" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Observatii sociale</label><textarea name="observatii_sociale" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">6. Nevoia de sprijin</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-12"><label class="form-label fw-bold">Tip sprijin solicitat *</label><br><?php render_checks('tip_sprijin_solicitat', social_options('tip_sprijin')); ?></div>
                        <div class="col-12"><label class="form-label">Descrierea nevoii</label><textarea name="descriere_nevoie" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-3"><label class="form-label">Urgenta caz</label><select name="urgenta_caz" class="form-select"><option value="">--</option><?php render_options(social_options('urgenta')); ?></select></div>
                        <div class="col-md-3"><label class="form-label">Suma estimata</label><input name="suma_estimata_necesara" type="number" min="0" step="0.01" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Perioada sprijin</label><input name="perioada_sprijin" type="text" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Alte surse ajutor</label><select name="alte_surse_ajutor" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu_necunoscut')); ?></select></div>
                        <div class="col-12"><label class="form-label">Detalii alte surse</label><textarea name="detalii_alte_surse" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">7-8. Evaluare si decizie interna</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label fw-bold">Data evaluarii *</label><input name="data_evaluarii" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Modalitate evaluare</label><select name="modalitate_evaluare" class="form-select"><option value="">--</option><?php render_options(social_options('modalitate_evaluare')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Persoana recomandare</label><input name="persoana_recomandare" type="text" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Nivel vulnerabilitate</label><select name="nivel_vulnerabilitate" class="form-select"><option value="">--</option><?php render_options(social_options('nivel_vulnerabilitate')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Recomandare interna</label><select name="recomandare_interna" class="form-select"><option value="">--</option><?php render_options(social_options('recomandare_interna')); ?></select></div>
                        <div class="col-md-4"><label class="form-label fw-bold">Status caz *</label><select name="status_caz" class="form-select" required><?php render_options(social_options('status_caz'), 'caz nou'); ?></select></div>
                        <div class="col-12"><label class="form-label">Motivarea recomandarii</label><textarea name="motivare_recomandare" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-4"><label class="form-label">Data deciziei</label><input name="data_deciziei" type="date" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Suma aprobata</label><input name="suma_aprobata" type="number" min="0" step="0.01" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Tip ajutor aprobat</label><input name="tip_ajutor_aprobat" type="text" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Observatii decizie</label><textarea name="observatii_decizie" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white"><h5 class="mb-0">9-12. Documente, GDPR si concluzii</h5></div>
                    <div class="card-body row g-3">
                        <div class="col-md-4"><label class="form-label">Beneficiar informat GDPR</label><select name="gdpr_informat" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Acord GDPR semnat</label><select name="gdpr_semnat" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Data acord GDPR</label><input name="data_acord_gdpr" type="date" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Acord fotografii</label><select name="acord_fotografii" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-6"><label class="form-label">Acord poveste publica</label><select name="acord_poveste_publica" class="form-select"><option value="">--</option><?php render_options(social_options('da_nu')); ?></select></div>
                        <div class="col-md-4"><label class="form-label">Tip document initial</label><select name="tip_document" class="form-select"><option value="">--</option><?php render_options(social_options('documente')); ?></select></div>
                        <div class="col-md-8"><label class="form-label">Incarca document initial</label><input name="document_social" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                        <div class="col-12"><label class="form-label">Observatii document</label><input name="observatii_document" type="text" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Observatii interne</label><textarea name="observatii_interne" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Concluzie sociala scurta</label><textarea name="concluzie_sociala" class="form-control" rows="3"></textarea></div>
                        <div class="col-12"><label class="form-label">Recomandare finala</label><textarea name="recomandare_finala" class="form-control" rows="3"></textarea></div>
                    </div>
                </div>

                <div class="text-center mb-5">
                    <button type="submit" name="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-save me-2"></i> Salveaza fisa sociala
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
