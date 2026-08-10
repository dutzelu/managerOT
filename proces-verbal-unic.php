<?php
$titlu_pg = "Proces verbal unic";

ob_start();
include "includes/header.php";
ob_end_clean();

ob_start();

if(isset($_GET['iddonatie'])) {
    $id_donatie = $_GET['iddonatie'];
} else {$id_donatie = '';}


$stmt = $conn->prepare("
SELECT 
    asistati_social.id as 'id_asistat',
    asistati_social.nume,
    asistati_social.prenume,
    asistati_social.cnp,
    asistati_social.serie_nr_ci,
    asistati_social.adresa_completa,
    donatii.suma_lei,
    donatii.tip_donatie,
    donatii.scop_donatie,
    donatii.data
FROM donatii 
LEFT JOIN asistati_social
ON donatii.id_asistat = asistati_social.id
WHERE donatii.id = ?

");

$stmt->bind_param("s", $id_donatie); 
$stmt->execute(); 
$result = $stmt->get_result();

while ($date = mysqli_fetch_assoc($result)){ 
    $id_asistat = $date['id_asistat'];
    $nume = $date['nume'];
    $prenume = $date ['prenume'];
    $cnp = $date['cnp'];
    descompunere_cnp($cnp);
    $serie_nr_ci = $date['serie_nr_ci'];
    $adresa_completa = $date['adresa_completa'];
    $suma_lei =  $date['suma_lei'];
    $tip_donatie = $date['tip_donatie'];
    $scop_donatie = $date['scop_donatie'];
    $data = $date['data'];
    $data_rom = date("d.m.Y", strtotime($data));
    $anul = substr ($data, 0,4);
    $luna = substr ($data, 5,2);

?>


<p style="text-align:right">Asociația Ortodoxia Tinerilor <br>
Str.Frunzei nr.5, Bl.F3, 63 Galați, jud. Galați, România<br>
asociatia@ortodoxiatinerilor.ro<br>
www.ortodoxiatinerilor.ro | 0740.004.215
</p>

<p>&nbsp;</p>
<h1 style="text-align:center">Proces verbal nr. <?php echo $id_donatie . ' / ' . $data_rom?> </h1>

<div style="text-align:center">

<?php
    echo '<p>';	 

    if ($prima_cifra == "1" || $prima_cifra == "5"){echo "Subsemnatul ";} 
    elseif ($prima_cifra == "2" || $prima_cifra == "6") {echo "Subsemnata ";} 

    echo '<strong>' . $nume . ' ' . $prenume . '</strong>' .  ' cu CNP <strong>' . $cnp .'</strong>' . ' , având bulentin de identitate cu seria și numărul <strong>' . $serie_nr_ci . '</strong>';
   
    if ($prima_cifra == "1" || $prima_cifra == "5") {echo " domiciliat ";} 
    elseif ($prima_cifra == "2" || $prima_cifra == "6") {echo " domiciliată ";} 
   
   echo ' în <strong>' . $adresa_completa . '</strong>'?>, declar că am primit de la Asociația Ortodoxia Tinerilor  <?php echo '<strong>' . $suma_lei . '</strong>'?> lei (<?php echo '<strong>' . $tip_donatie . '</strong>'?>) având ca scop plata pentru: <?php echo '<strong>' . $scop_donatie . '</strong>' ?>.</p>

</div>

<p>&nbsp;</p><p>&nbsp;</p>

<p> <strong>Data: </strong><?php echo $data_rom;?></p>
<p style="text-align:right"><strong>Semnătură: </strong><?php echo $nume . ' ' . $prenume ?>


<?php

}

  // directorul unde se vor încarca procesele verbale
  $target_dir = "procese-verbale/" . $anul . '/' . $luna . '/';
  $file_name = 'PV-nr-' . $id_donatie . '-din-' . $data_rom . '-' . $nume . '-' . $prenume . '-' . $suma_lei . '-lei.pdf';
  $link_proces_verbal = $target_dir . $file_name;
  // dacă directorul nu există, va fi creat

  if (!file_exists($target_dir)) {
  mkdir($target_dir, 0777, true);}


$html = ob_get_clean(); 

require_once __DIR__ . '/vendor/autoload.php';

// Crează o instanță Mpdf ----------------
$mpdf = new \Mpdf\Mpdf();


// Scriere fișierului PDF ----------------
$mpdf->WriteHTML($html);


// Salvarea fișierului pdf----------------
ob_clean(); 
$mpdf->Output($target_dir . $file_name, \Mpdf\Output\Destination::FILE);


// inserare link proces verbal în baza de date ----------------

if (file_exists($target_dir . $file_name)) {

    $sql_file_path = "
    UPDATE donatii
    SET 
        proces_verbal = '$file_name',
        link_proces_verbal = '$link_proces_verbal'
    WHERE id = $id_donatie;";

    $rez=mysqli_query($conn, $sql_file_path);

    $back_link = 'total-donatii.php?an=' . urlencode($anul) . '#donatie-' . urlencode($id_donatie);

    header('Location: ' . $back_link);
    exit;

} else {echo "Problem updating record.MySQL Error: " . mysqli_error($query);}




?>