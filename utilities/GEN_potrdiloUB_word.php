<?php

$id_org = $_GET['o'];
$sl_id = $_GET['sl'];
//mail("gasper.krstulovic@gmail.com", "pogodba", $id_org);



// pripravi podatke

// data input
include_once('../config/db.php');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Change character set to utf8
$conn->set_charset("utf8");
$solsko_leto = "";
// šolsko leto
$sql = "SELECT solsko_leto FROM FSD_admin_variables WHERE id = " . $sl_id;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $solsko_leto = $row['solsko_leto'];
  }
}


// dobi naslov 

$naziv = "";
$naslov = "";

$sql = "SELECT NAZIV, NASLOV, POSTA_ST, POSTA FROM FSD_organizacije WHERE OrganizacijaID = " . $id_org;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $naziv = $row['NAZIV'];
    $naslov = $row['NASLOV'] . ", " . $row['POSTA_ST'] .  " " . $row['POSTA'];
  }
}

$conn->close();

// GENERIRAJ POTRDILO

require 'vendor/autoload.php';

//require_once 'bootstrap.php';
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('potrdiloUB.docx');

// variables
$templateProcessor->setValue('NAZIV', $naziv);
$templateProcessor->setValue('NASLOV', $naslov);
$templateProcessor->setValue('SOLSKO_LETO', $solsko_leto);
$templateProcessor->setValue('DATUM', date('j. n. Y'));


$file = "Potrdilo FSD - " . substr($naziv, 0, 100) . ".docx";

header("Content-Description: File Transfer");
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');

//$templateProcessor = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
//$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($templateProcessor, 'Word2007');
//$templateProcessor->saveAs($file);
$templateProcessor->saveAs('php://output');

exit;
