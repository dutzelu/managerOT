<?php
// validează inputurile în formular
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }


// prea id-uri donatii pentru o singură persoana

function iduri_donatii ($id, $an, $luna) {
  $iduri_donatii = '';
  global $conn;
  global $iduri_donatii;
  $sql = "
  SELECT 
      donatii.id as 'id_donatie'
  FROM asistati_social
  LEFT JOIN donatii
  ON asistati_social.id = donatii.id_asistat
  WHERE asistati_social.id = ?
  AND YEAR(data) = ?
  AND MONTH(data) = ?
  ORDER BY donatii.data
  ASC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sss", $id, $an, $luna); 
  $stmt->execute(); 
  $rezultat = $stmt->get_result();
  $ultima_luna = null;
  while ($data = mysqli_fetch_assoc($rezultat)){   
    $iduri_donatii .= $data['id_donatie'].'-';
  }
  $iduri_donatii = substr($iduri_donatii, 0, -1);
}

// calculează total

function total_anual ($an) {
global $conn;
global $total_anual;
$sql = "SELECT 	SUM(`suma_lei`) Total
FROM donatii
WHERE YEAR(data) = ?;";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $an); 
$stmt->execute(); 
$rezultat = $stmt->get_result();

while ($data = mysqli_fetch_assoc($rezultat)){   
    $total_anual = $data['Total'];
    $total_anual = number_format((float)$total_anual, 0, '.', '');
}
}

function total_an_pers ($id, $an) {
  global $conn;
  global $total_extras;
  $sql = "SELECT 	SUM(`suma_lei`) Total
  FROM donatii
  WHERE id_asistat = ? AND YEAR(data) = ?;";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $id, $an); 
  $stmt->execute(); 
  $rezultat = $stmt->get_result();
  
  while ($data = mysqli_fetch_assoc($rezultat)){   
      $total_extras = $data['Total'];
  }
  }

function total_luna ($luna, $an) {
  global $conn;
  global $total_extras;
  $sql = "SELECT 	SUM('suma_lei') Total
  FROM donatii
  WHERE MONTH(data) = ? AND YEAR(data) = ?;";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ss", $luna, $an); 
  $stmt->execute(); 
  $rezultat = $stmt->get_result();
  
  while ($rez = mysqli_fetch_assoc($rezultat)){   
      $total_luna = $rez['Total'];
  }
  }

// descopune cnp pentru aflarea sexului, vârstei și a datei nașterii

 function descompunere_cnp($cod_numeric) {

    global $varsta;
    global $data_nasterii;
    global $sex;
    global $prima_cifra;
    $prima_cifra = $cod_numeric['0'];
    
    // 1/2 - cetateni romani nascuti intre 1 ian 1900 si 31 dec 1999
    // 3/4 - cetateni romani nascuti intre 1 ian 1800 si 31 dec 1899
    // 5/6 - cetateni romani nascuti intre 1 ian 2000 si 31 dec 2099
    
    $anul_nasterii = substr($cod_numeric, 1, 2);
    
    if ($prima_cifra == 1 | $prima_cifra == 2) {
      $anul_nasterii = "19" . $anul_nasterii;
    }
    elseif ($prima_cifra == 5 | $prima_cifra == 6) {
      $anul_nasterii = "20" . $anul_nasterii;
    }
    
    $luna_nasterii = substr ($cod_numeric, 3,2);
    $ziua_nasterii = substr ($cod_numeric, 5,2);
    $data_nasterii_unix = strtotime($data_nasterii);
    $data_nasterii = $ziua_nasterii . "-" . $luna_nasterii . "-" . $anul_nasterii;
  
    
    if ($prima_cifra == "1" || $prima_cifra == "5"){$sex = "Bărbat";} 
    elseif ($prima_cifra == "2" || $prima_cifra == "6") {$sex = "Femeie";}
    $varsta = date("Y") - $anul_nasterii . " ani";
   

    }

// generează următorul număr de contract pentru anul dat (format: AOT + serial + an, ex: AOT52026)

function genereaza_numar_contract($conn, $year) {
    $next_serial = 1;
    $query = "SELECT numar FROM contracte
              WHERE RIGHT(numar, 4) = ?
              ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $year);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // formatul stocat: "AOT" + serial + an (ex: AOT12025)
            $serial = substr($row['numar'], 3, -4);
            $next_serial = (int)$serial + 1;
        }
        $stmt->close();
    }
    return "AOT" . $next_serial . $year;
}

// scoate diacriticile

    function replaceSpecialChars($string){

      // caractere care trebuie inlocuite cu cele din $add (in aceeasi ordine)
      $rem = array('ă', 'Ă', 'ș', 'Ș', 'ț', 'Ț', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ð', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', '§', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', '€', 'Ð', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', '§', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Ÿ',
    // aceleasi caractere, dar ca entitati HTML
    '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;', '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&eth;', '&igrave;', '&iacute;', '&icirc;', '&iuml;', '&ntilde;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&oslash;', '&sect;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;', '&yuml;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;', '&AElig;', '&Ccedil;', '&Egrave;', '&Eacute;', '&Ecirc;', '&Euml;', '&euro;', '&ETH;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;', '&Ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&Oslash;', '&sect;', '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&Yuml;');
    
      // caractere care vor fi adaugate
      $add = array('a', 'A', 's', 'S', 't', 'T', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'ed', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 's', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'EUR', 'ED', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'S', 'U', 'U', 'U', 'U', 'Y', 'Y',
    // pentru inlocuit entitatile HTML
    'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'ed', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 's', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'EUR', 'ED', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'S', 'U', 'U', 'U', 'U', 'Y', 'Y');
    
        return str_replace($rem, $add, $string);
    }

if (!function_exists('social_options')) {
  function social_options($key) {
    $options = array(
      'status_caz' => array('caz nou', 'in analiza', 'aprobat', 'sprijin acordat', 'respins', 'inchis', 'arhivat'),
      'tip_locuinta' => array('proprietate', 'chirie', 'locuinta sociala', 'gazduire', 'fara locuinta', 'alta situatie'),
      'conditii_locuire' => array('bune', 'medii', 'precare', 'foarte precare'),
      'utilitati' => array('apa', 'curent', 'gaze', 'incalzire', 'canalizare', 'internet'),
      'surse_venit' => array('salariu', 'pensie', 'alocatii', 'ajutor social', 'indemnizatii', 'munca ocazionala', 'fara venit', 'alte venituri'),
      'vulnerabilitati' => array('boala cronica', 'dizabilitate', 'familie monoparentala', 'multi copii', 'somaj', 'varstnic singur', 'copil bolnav', 'risc abandon scolar', 'violenta domestica', 'alta situatie'),
      'tip_sprijin' => array('bani', 'alimente', 'medicamente', 'rechizite', 'haine', 'lemne', 'plata facturilor', 'materiale constructii', 'transport', 'alt sprijin'),
      'urgenta' => array('scazuta', 'medie', 'ridicata', 'critica'),
      'da_nu' => array('da', 'nu'),
      'da_nu_necunoscut' => array('da', 'nu', 'necunoscut'),
      'modalitate_evaluare' => array('telefon', 'online', 'vizita la domiciliu', 'recomandare preot', 'recomandare voluntar', 'alte surse'),
      'nivel_vulnerabilitate' => array('scazut', 'mediu', 'ridicat', 'critic'),
      'recomandare_interna' => array('se aproba', 'se respinge', 'necesita documente suplimentare', 'necesita verificare suplimentara'),
      'documente' => array('copie ci', 'cerere de sprijin', 'acord gdpr', 'documente medicale', 'adeverinte venit', 'certificate nastere copii', 'facturi/proforme/devize', 'ordin de plata', 'extras bancar', 'proces-verbal', 'alte documente')
    );
    return $options[$key] ?? array();
  }
}

if (!function_exists('social_join_post_values')) {
  function social_join_post_values($name) {
    $values = $_POST[$name] ?? array();
    if (!is_array($values)) {
      $values = array($values);
    }
    $clean = array();
    foreach ($values as $value) {
      $value = trim((string)$value);
      if ($value !== '') {
        $clean[] = $value;
      }
    }
    return implode(', ', $clean);
  }
}

if (!function_exists('social_default_fisa')) {
  function social_default_fisa() {
    $fields = array(
      'id', 'beneficiar_id', 'nr_total_membri', 'nr_copii_minori', 'nr_adulti', 'nr_varstnici',
      'nr_persoane_dizabilitati', 'persoane_intretinere', 'observatii_familie', 'tip_locuinta',
      'nr_camere', 'conditii_locuire', 'utilitati', 'risc_evacuare', 'observatii_locuinta',
      'venit_lunar_estimat', 'surse_venit', 'datorii_importante', 'descriere_datorii',
      'cheltuieli_lunare_majore', 'observatii_financiare', 'probleme_medicale',
      'descriere_probleme_medicale', 'persoane_cu_dizabilitati', 'grad_handicap',
      'documente_medicale_disponibile', 'alte_vulnerabilitati', 'observatii_sociale',
      'tip_sprijin_solicitat', 'descriere_nevoie', 'urgenta_caz', 'suma_estimata_necesara',
      'perioada_sprijin', 'alte_surse_ajutor', 'detalii_alte_surse', 'data_evaluarii',
      'modalitate_evaluare', 'persoana_recomandare', 'nivel_vulnerabilitate',
      'recomandare_interna', 'motivare_recomandare', 'status_caz', 'data_deciziei',
      'tip_ajutor_aprobat', 'suma_aprobata', 'observatii_decizie', 'gdpr_informat',
      'gdpr_semnat', 'acord_fotografii', 'acord_poveste_publica', 'data_acord_gdpr',
      'observatii_interne', 'concluzie_sociala', 'recomandare_finala'
    );
    $fisa = array();
    foreach ($fields as $field) {
      $fisa[$field] = '';
    }
    $fisa['status_caz'] = 'caz nou';
    $fisa['data_evaluarii'] = date('Y-m-d');
    return $fisa;
  }
}

if (!function_exists('social_get_current_fisa')) {
  function social_get_current_fisa($conn, $beneficiar_id) {
    $stmt = $conn->prepare("SELECT * FROM fise_sociale WHERE beneficiar_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $beneficiar_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $fisa = $result->fetch_assoc();
    $stmt->close();
    return $fisa ?: social_default_fisa();
  }
}

if (!function_exists('social_log_change')) {
  function social_log_change($conn, $beneficiar_id, $fisa_id, $tip_modificare, $camp, $old, $new, $observatii = '') {
    $old = (string)$old;
    $new = (string)$new;
    if ($old === $new) {
      return;
    }
    $stmt = $conn->prepare("
      INSERT INTO istoric_modificari
      (beneficiar_id, fisa_id, tip_modificare, camp_modificat, valoare_veche, valoare_noua, observatii)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssss", $beneficiar_id, $fisa_id, $tip_modificare, $camp, $old, $new, $observatii);
    $stmt->execute();
    $stmt->close();
  }
}

if (!function_exists('social_clean_filename')) {
  function social_clean_filename($name) {
    $name = replaceSpecialChars($name);
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name);
    $name = trim($name, '-');
    return $name !== '' ? $name : 'document';
  }
}

if (!function_exists('social_ensure_external_tables')) {
  function social_ensure_external_tables($conn) {
    $conn->query("
      CREATE TABLE IF NOT EXISTS asistat_external_links (
        id INT AUTO_INCREMENT PRIMARY KEY,
        beneficiar_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        revoked_at DATETIME NULL,
        created_by VARCHAR(190) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_external_token_hash (token_hash),
        INDEX idx_external_links_beneficiar (beneficiar_id),
        INDEX idx_external_links_status (expires_at, used_at, revoked_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $conn->query("
      CREATE TABLE IF NOT EXISTS asistat_external_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        link_id INT NOT NULL,
        beneficiar_id INT NOT NULL,
        payload_json LONGTEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'nou',
        submitted_ip VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        applied_at DATETIME NULL,
        applied_by VARCHAR(190) NULL,
        notes TEXT NULL,
        INDEX idx_external_submissions_beneficiar (beneficiar_id),
        INDEX idx_external_submissions_link (link_id),
        INDEX idx_external_submissions_status (status, submitted_at)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
  }
}

if (!function_exists('social_external_beneficiar_fields')) {
  function social_external_beneficiar_fields() {
    return array(
      'nume', 'prenume', 'cnp', 'serie_ci', 'numar_ci', 'data_nasterii',
      'telefon', 'email', 'adresa_completa', 'localitate', 'judet',
      'stare_civila', 'ocupatie', 'observatii_generale'
    );
  }
}

if (!function_exists('social_external_fisa_fields')) {
  function social_external_fisa_fields() {
    return array(
      'nr_total_membri', 'nr_copii_minori', 'nr_adulti', 'nr_varstnici',
      'nr_persoane_dizabilitati', 'persoane_intretinere', 'observatii_familie',
      'tip_locuinta', 'nr_camere', 'conditii_locuire', 'utilitati',
      'risc_evacuare', 'observatii_locuinta', 'venit_lunar_estimat',
      'surse_venit', 'datorii_importante', 'descriere_datorii',
      'cheltuieli_lunare_majore', 'observatii_financiare', 'probleme_medicale',
      'descriere_probleme_medicale', 'persoane_cu_dizabilitati',
      'grad_handicap', 'documente_medicale_disponibile', 'alte_vulnerabilitati',
      'observatii_sociale', 'tip_sprijin_solicitat', 'descriere_nevoie',
      'urgenta_caz', 'suma_estimata_necesara', 'perioada_sprijin',
      'alte_surse_ajutor', 'detalii_alte_surse', 'gdpr_informat',
      'gdpr_semnat', 'acord_fotografii', 'acord_poveste_publica',
      'data_acord_gdpr'
    );
  }
}

if (!function_exists('social_uploaded_files')) {
  function social_uploaded_files($field) {
    $files = $_FILES[$field] ?? null;
    if (!$files || !is_array($files['name'] ?? null)) {
      return array();
    }

    $normalized = array();
    foreach ($files['name'] as $index => $name) {
      $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
      if ($error === UPLOAD_ERR_NO_FILE) {
        continue;
      }

      $normalized[] = array(
        'name' => $name,
        'type' => $files['type'][$index] ?? '',
        'tmp_name' => $files['tmp_name'][$index] ?? '',
        'error' => $error,
        'size' => $files['size'][$index] ?? 0
      );
    }

    return $normalized;
  }
}

if (!function_exists('social_store_document')) {
  function social_store_document($conn, $file, $beneficiar_id, $fisa_id, $ajutor_id, $tip_document, $observatii = '') {
    if (!isset($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      return array(true, null);
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      return array(false, 'Eroare la incarcarea documentului.');
    }

    $max_size = 10 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max_size) {
      return array(false, 'Documentul trebuie sa fie sub 10 MB.');
    }

    $original_name = $file['name'] ?? 'document';
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $allowed = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
    if (!in_array($ext, $allowed, true)) {
      return array(false, 'Extensia documentului nu este permisa.');
    }

    $target_dir = 'documente-sociale/' . (int)$beneficiar_id;
    if (!is_dir($target_dir) && !mkdir($target_dir, 0777, true)) {
      return array(false, 'Nu s-a putut crea folderul pentru documente.');
    }

    $unique_part = substr(sha1(uniqid('', true)), 0, 8);
    $safe_name = date('YmdHis') . '-' . $unique_part . '-' . social_clean_filename(pathinfo($original_name, PATHINFO_FILENAME)) . '.' . $ext;
    $path = $target_dir . '/' . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
      return array(false, 'Nu s-a putut salva documentul pe server.');
    }

    $stmt = $conn->prepare("
      INSERT INTO documente_sociale
      (beneficiar_id, fisa_id, ajutor_id, tip_document, denumire_fisier, cale_fisier, observatii)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiissss", $beneficiar_id, $fisa_id, $ajutor_id, $tip_document, $original_name, $path, $observatii);
    $stmt->execute();
    $document_id = $stmt->insert_id;
    $stmt->close();

    social_log_change($conn, $beneficiar_id, $fisa_id, 'document', 'documente', '', $tip_document . ': ' . $original_name, 'Document incarcat.');

    return array(true, $document_id);
  }
}
?>
