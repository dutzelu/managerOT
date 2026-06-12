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
?>