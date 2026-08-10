<?php
require_once __DIR__ . "/includes/conexiune.php";
require_once __DIR__ . "/includes/functii.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: lista-donatii.php');
    exit;
}

$id = (int)$_GET['id'];
$persoana = $_GET['persoana'] ?? '';
$errors = array();

if (isset($_POST["submit"])) {
    $suma_lei = test_input($_POST['suma_lei']);
    $tip_donatie = test_input($_POST['tip_donatie']);
    $mod_acordare = test_input($_POST['mod_acordare'] ?? '');
    $act_doveditor = test_input($_POST['act_doveditor']);
    $nr_act_doveditor = test_input($_POST['nr_act_doveditor']);
    $cont_beneficiar = test_input($_POST['cont_beneficiar'] ?? '');
    $numar_ordin_plata = test_input($_POST['numar_ordin_plata'] ?? '');
    $sursa_fondurilor = test_input($_POST['sursa_fondurilor'] ?? '');
    $link_act = test_input($_POST['link_act']);
    $scop_donatie = test_input($_POST['scop_donatie']);
    $observatii_ajutor = test_input($_POST['observatii_ajutor'] ?? '');
    $data_donatiei = test_input($_POST['data']);
    $proces_verbal = test_input($_POST['proces_verbal']);
    $link_proces_verbal = test_input($_POST['link_proces_verbal']);

    $uploaded_files = donatie_normalize_uploaded_files('act');
    $errors = array_merge($errors, donatie_validate_uploaded_files($uploaded_files));

    if (empty($errors)) {
        $upload_result = donatie_upload_attachments($conn, $id, $data_donatiei, 'act');
        $errors = array_merge($errors, $upload_result['errors']);

        if (!empty($link_act)) {
            donatie_add_external_attachment($conn, $id, $link_act);
        }

        if (!empty($upload_result['paths'])) {
            $link_act = $upload_result['paths'][0];
        }
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("
            UPDATE `donatii`
            SET
              `suma_lei` = ?,
              `tip_donatie` = ?,
              `mod_acordare` = ?,
              `act_doveditor` = ?,
              `nr_act_doveditor` = ?,
              `cont_beneficiar` = ?,
              `numar_ordin_plata` = ?,
              `sursa_fondurilor` = ?,
              `link_act` = ?,
              `proces_verbal` = ?,
              `link_proces_verbal` = ?,
              `scop_donatie` = ?,
              `observatii_ajutor` = ?,
              `data` = ?
            WHERE `ID` = ?
        ");

        $stmt->bind_param(
            "ssssssssssssssi",
            $suma_lei,
            $tip_donatie,
            $mod_acordare,
            $act_doveditor,
            $nr_act_doveditor,
            $cont_beneficiar,
            $numar_ordin_plata,
            $sursa_fondurilor,
            $link_act,
            $proces_verbal,
            $link_proces_verbal,
            $scop_donatie,
            $observatii_ajutor,
            $data_donatiei,
            $id
        );
        $stmt->execute();
        $stmt->close();

        header('Location: edit-donatie.php?id=' . $id . '&succes=1&persoana=' . urlencode($persoana));
        exit;
    }

    header('Location: edit-donatie.php?id=' . $id . '&upload_error=' . urlencode(implode(' ', $errors)) . '&persoana=' . urlencode($persoana));
    exit;
}

header('Location: edit-donatie.php?id=' . $id . '&persoana=' . urlencode($persoana));
exit;
?>
