<?php

include 'header.php';

// începe obiectul care adună tot conțintul html într-un pdf
ob_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $iduri_donatii = $_GET['iduridonatii'];
    $data = $_GET['data'];
} else {$id = ""; $iduridonatii = '';}

$id_donatie = '';
$id_donatie = explode ('-', $iduri_donatii);

?>

<p style="text-align:right">Asociația Ortodoxia Tinerilor <br> Str.Frunzei nr.5, Bl.F3, 63 Galați, jud. Galați, România<br>asociatia@ortodoxiatinerilor.ro<br>www.ortodoxiatinerilor.ro | 0740.004.215</p>

<p>&nbsp;</p>

 
<?php 

$stmt = $conn->prepare("SELECT * FROM asistati_social WHERE id = ?");
$stmt->bind_param("s", $id); 
$stmt->execute(); 
$result = $stmt->get_result();

while ($date = mysqli_fetch_assoc($result)){ 

    $nume = $date['nume'];
    $prenume = $date ['prenume'];
    $cnp = $date['cnp'];
    descompunere_cnp($cnp);
    $serie_nr_ci = $date['serie_nr_ci'];
    $adresa_completa = $date['adresa_completa'];


    echo '<h1 style="text-align:center">Proces verbal nr. ' . $id . ' / ' . date("t.m.Y", strtotime( $data)) . '</h1>';
    descompunere_cnp($date['cnp']);
   
    echo '<p>';
   
    if ($prima_cifra == "1" || $prima_cifra == "5") {echo "Subsemnatul ";} 
    elseif ($prima_cifra == "2" || $prima_cifra == "6") {echo "Subsemnata ";} 
   
    echo '<strong>' . $date['nume'] . ' ' . $date['prenume'] . ' </strong>';
    echo ' cu CNP ' . '<strong>' . $date['cnp'] .'</strong>' . ' având cartea de identitate cu seria și numărul ' . '<strong>' . $date['serie_nr_ci'] . '</strong>';
   
    if ($prima_cifra == "1" || $prima_cifra == "5") {echo " domiciliat ";} 
    elseif ($prima_cifra == "2" || $prima_cifra == "6") {echo " domiciliată ";} 
   
    echo 'în '. '<strong>' . $date['adresa_completa'] . '</strong>' . ', declar că am primit de la Asociația Ortodoxia Tinerilor următoarele donații:';  
    echo '</p>';
}

echo '<ul>';

foreach ($id_donatie as $x){

$stmt = $conn->prepare("
SELECT 
    asistati_social.id,
    asistati_social.nume,
    asistati_social.prenume,
    asistati_social.cnp,
    asistati_social.serie_nr_ci,
    asistati_social.adresa_completa,
    donatii.suma_lei,
    donatii.tip_donatie,
    donatii.scop_donatie,
    donatii.act_doveditor,
    donatii.nr_act_doveditor,
    donatii.proces_verbal,
    donatii.link_proces_verbal,
    donatii.data
FROM donatii 
LEFT JOIN asistati_social
ON donatii.id_asistat = asistati_social.id
WHERE donatii.id = ?

");

$stmt->bind_param("s", $x); 
$stmt->execute(); 
$result = $stmt->get_result();

while ($date = mysqli_fetch_assoc($result)){ 
    
    $luna_cuvant = date("F", strtotime($date['data']));
    $luna = substr ($data, 5,2);
    $anul = substr ($data, 0,4);
    descompunere_cnp($date['cnp']);
    $data = $date['data'];

    echo '<li><strong>' . $date['suma_lei'] . '</strong>' . ' lei (' . '<strong>'; 
   
    if  ($date['tip_donatie'] == 'produse') {echo 'în ' . $date['tip_donatie'];}
    else {echo $date['tip_donatie'];}
   
    echo '</strong>) având ca scop plata pentru: <strong>' . $date['scop_donatie'] . ',</strong> ';

    if ($date['tip_donatie'] !== 'cash') {echo $date['act_doveditor'] . ' ' . $date['nr_act_doveditor'];}
   
    echo ' în data de ' . '<strong>' . date("d.m.Y", strtotime($data)) . '</strong>' . '.</li>';

  }    
}

?>

</ul>
<p>&nbsp;</p>

<p> <strong>Data: </strong><?php echo date("t.m.Y", strtotime( $data))?></p>
<p style="text-align:right"><strong>Semnătură: </strong><?php echo $nume . ' ' . $prenume ?>

<?php

  // directorul unde se vor încarca procesele verbale
  $target_dir = "procese-verbale/" . $anul . '/' . $luna . '/';
  $file_name = 'PV-' . $luna_cuvant . '-' .$anul . '-'. $nume . '-' . $prenume . '-nr-' . $id . '-din-' . date("t.m.Y", strtotime( $data)) . '.pdf';
  $link_proces_verbal = $target_dir . $file_name;

  // dacă directorul nu există, va fi creat
  if (!file_exists($target_dir)) {
  mkdir($target_dir, 0777, true);}


$html = ob_get_clean(); 

require_once __DIR__ . '/vendor/autoload.php';

// Crează o instanță Mpdf

$mpdf = new \Mpdf\Mpdf();


// Write PDF

$mpdf->WriteHTML($html);

// Creez fișierul pdf
ob_clean(); 

$mpdf->Output($target_dir . $file_name, \Mpdf\Output\Destination::FILE);

if (file_exists($target_dir . $file_name)) {

    // introduc linkul procesului verbal în fiecare donație
    foreach ($id_donatie as $x){

        $sql_file_path = "
        UPDATE donatii
        SET 
            proces_verbal = '$file_name',
            link_proces_verbal = '$link_proces_verbal'
        WHERE id = $x;";

        $rez=mysqli_query($conn, $sql_file_path);
    }

    header('Location:lista-donatii.php?id=' .  $id . "&persoana=" . $nume . '-'  . $prenume . '&an=' . $anul);
     
} else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}



?>
