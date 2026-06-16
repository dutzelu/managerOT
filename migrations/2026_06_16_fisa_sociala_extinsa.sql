-- Migrare pentru fisa sociala extinsa.
-- Rulati dupa backup complet al bazei de date si al folderelor de documente.

START TRANSACTION;

ALTER TABLE asistati_social
    ADD COLUMN IF NOT EXISTS serie_ci VARCHAR(10) NULL AFTER cnp,
    ADD COLUMN IF NOT EXISTS numar_ci VARCHAR(20) NULL AFTER serie_ci,
    ADD COLUMN IF NOT EXISTS data_nasterii DATE NULL AFTER numar_ci,
    ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL AFTER telefon,
    ADD COLUMN IF NOT EXISTS ocupatie VARCHAR(190) NULL AFTER stare_civila,
    ADD COLUMN IF NOT EXISTS observatii_generale TEXT NULL AFTER ocupatie,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE asistati_social
SET
    serie_ci = COALESCE(NULLIF(serie_ci, ''), TRIM(SUBSTRING_INDEX(serie_nr_ci, ' ', 1))),
    numar_ci = COALESCE(NULLIF(numar_ci, ''), TRIM(SUBSTRING(serie_nr_ci, LENGTH(SUBSTRING_INDEX(serie_nr_ci, ' ', 1)) + 1)))
WHERE serie_nr_ci IS NOT NULL
  AND serie_nr_ci <> ''
  AND (serie_ci IS NULL OR serie_ci = '' OR numar_ci IS NULL OR numar_ci = '');

CREATE TABLE IF NOT EXISTS fise_sociale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiar_id INT NOT NULL,
    nr_total_membri INT NULL,
    nr_copii_minori INT NULL,
    nr_adulti INT NULL,
    nr_varstnici INT NULL,
    nr_persoane_dizabilitati INT NULL,
    persoane_intretinere TEXT NULL,
    observatii_familie TEXT NULL,
    tip_locuinta VARCHAR(60) NULL,
    nr_camere INT NULL,
    conditii_locuire VARCHAR(60) NULL,
    utilitati TEXT NULL,
    risc_evacuare ENUM('da','nu') NULL,
    observatii_locuinta TEXT NULL,
    venit_lunar_estimat DECIMAL(12,2) NULL,
    surse_venit TEXT NULL,
    datorii_importante ENUM('da','nu') NULL,
    descriere_datorii TEXT NULL,
    cheltuieli_lunare_majore TEXT NULL,
    observatii_financiare TEXT NULL,
    probleme_medicale ENUM('da','nu') NULL,
    descriere_probleme_medicale TEXT NULL,
    persoane_cu_dizabilitati ENUM('da','nu') NULL,
    grad_handicap VARCHAR(120) NULL,
    documente_medicale_disponibile ENUM('da','nu') NULL,
    alte_vulnerabilitati TEXT NULL,
    observatii_sociale TEXT NULL,
    tip_sprijin_solicitat TEXT NULL,
    descriere_nevoie TEXT NULL,
    urgenta_caz VARCHAR(30) NULL,
    suma_estimata_necesara DECIMAL(12,2) NULL,
    perioada_sprijin VARCHAR(190) NULL,
    alte_surse_ajutor ENUM('da','nu','necunoscut') NULL,
    detalii_alte_surse TEXT NULL,
    data_evaluarii DATE NULL,
    modalitate_evaluare VARCHAR(80) NULL,
    persoana_recomandare VARCHAR(190) NULL,
    nivel_vulnerabilitate VARCHAR(30) NULL,
    recomandare_interna VARCHAR(80) NULL,
    motivare_recomandare TEXT NULL,
    status_caz VARCHAR(30) NOT NULL DEFAULT 'caz nou',
    data_deciziei DATE NULL,
    tip_ajutor_aprobat TEXT NULL,
    suma_aprobata DECIMAL(12,2) NULL,
    observatii_decizie TEXT NULL,
    gdpr_informat ENUM('da','nu') NULL,
    gdpr_semnat ENUM('da','nu') NULL,
    acord_fotografii ENUM('da','nu') NULL,
    acord_poveste_publica ENUM('da','nu') NULL,
    data_acord_gdpr DATE NULL,
    observatii_interne TEXT NULL,
    concluzie_sociala TEXT NULL,
    recomandare_finala TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fise_beneficiar (beneficiar_id),
    INDEX idx_fise_status (status_caz),
    INDEX idx_fise_data_evaluarii (data_evaluarii)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documente_sociale (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiar_id INT NOT NULL,
    fisa_id INT NULL,
    ajutor_id INT NULL,
    tip_document VARCHAR(80) NOT NULL,
    denumire_fisier VARCHAR(255) NOT NULL,
    cale_fisier VARCHAR(500) NOT NULL,
    data_incarcarii DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observatii TEXT NULL,
    INDEX idx_documente_beneficiar (beneficiar_id),
    INDEX idx_documente_fisa (fisa_id),
    INDEX idx_documente_ajutor (ajutor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS istoric_modificari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiar_id INT NOT NULL,
    fisa_id INT NULL,
    tip_modificare VARCHAR(80) NOT NULL,
    camp_modificat VARCHAR(120) NULL,
    valoare_veche TEXT NULL,
    valoare_noua TEXT NULL,
    data_modificarii DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observatii TEXT NULL,
    INDEX idx_istoric_beneficiar (beneficiar_id),
    INDEX idx_istoric_fisa (fisa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE donatii
    ADD COLUMN IF NOT EXISTS fisa_id INT NULL AFTER id_asistat,
    ADD COLUMN IF NOT EXISTS mod_acordare VARCHAR(80) NULL AFTER tip_donatie,
    ADD COLUMN IF NOT EXISTS cont_beneficiar VARCHAR(120) NULL AFTER nr_act_doveditor,
    ADD COLUMN IF NOT EXISTS numar_ordin_plata VARCHAR(120) NULL AFTER cont_beneficiar,
    ADD COLUMN IF NOT EXISTS sursa_fondurilor VARCHAR(190) NULL AFTER numar_ordin_plata,
    ADD COLUMN IF NOT EXISTS observatii_ajutor TEXT NULL AFTER sursa_fondurilor,
    ADD INDEX IF NOT EXISTS idx_donatii_fisa (fisa_id);

INSERT INTO fise_sociale (
    beneficiar_id,
    nr_copii_minori,
    observatii_sociale,
    concluzie_sociala,
    tip_sprijin_solicitat,
    data_evaluarii,
    status_caz
)
SELECT
    a.id,
    a.nr_copii,
    a.descriere,
    a.descriere_scurta,
    NULL,
    COALESCE((SELECT MIN(d.data) FROM donatii d WHERE d.id_asistat = a.id), CURRENT_DATE),
    CASE
        WHEN EXISTS (SELECT 1 FROM donatii d WHERE d.id_asistat = a.id) THEN 'sprijin acordat'
        ELSE 'caz nou'
    END
FROM asistati_social a
WHERE NOT EXISTS (
    SELECT 1 FROM fise_sociale f WHERE f.beneficiar_id = a.id
);

UPDATE donatii d
JOIN (
    SELECT beneficiar_id, MAX(id) AS fisa_id
    FROM fise_sociale
    GROUP BY beneficiar_id
) f ON f.beneficiar_id = d.id_asistat
SET d.fisa_id = f.fisa_id
WHERE d.fisa_id IS NULL;

ALTER TABLE asistati_social
    DROP COLUMN IF EXISTS link_ci,
    DROP COLUMN IF EXISTS contract_sponsorizare,
    DROP COLUMN IF EXISTS link_contract;

COMMIT;
