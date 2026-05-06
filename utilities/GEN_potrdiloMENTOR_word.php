<?php

$id_org = $_GET['n'];
$id_ment= $_GET['o'];
$sl = $_GET['sl'];

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

// dobi še povezane osebe

// dobi naslov 

$naziv = "";
$naslov = "";

$mentor = "";
$solsko_leto = "";

$st_studentov = "";
$id_dok = "";
$sl_dok = "";
$datum = "";

$sql = "SELECT NAZIV FROM FSD_organizacije WHERE OrganizacijaID = " . $id_org;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $naziv = $row['NAZIV'];
  }
}

$sql = "SELECT Ime, Priimekinime FROM FSD_svetovalci WHERE SvetovalecID = " . $id_ment;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $mentor = $row['Ime'] . " " . $row['Priimekinime'];
  }
}

$sql = "SELECT solsko_leto FROM FSD_admin_variables WHERE id = " . $sl;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $solsko_leto = $row['solsko_leto'];
  }
}

$sql = "SELECT COUNT(*) AS student_count FROM FSD_prijave_studentov WHERE Mentor_UB_ID = " . $id_ment . " AND Solsko_letoID = " . $sl;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // fetch the result row
    $row = $result->fetch_assoc();
    $st_studentov = $row['student_count']; // use the alias
} else {
    $st_studentov = 0; // handle the case where no rows match
}


// custom studenti
$sql = "SELECT 
FSD_svetovalci.Ime, 
FSD_svetovalci.Priimekinime,
GROUP_CONCAT(DISTINCT FSD_organizacije.NAZIV) AS MergedNAZIV,
GROUP_CONCAT(DISTINCT FSD_svetovalci.SvetovalecID) AS Merged_ID,
COUNT(FSD_prijave_studentov.Mentor_UB_ID) AS Stevilo_mentorstev,
GROUP_CONCAT(FSD_prijave_studentov.VLLetnST) AS Studenti
FROM 
FSD_svetovalci
LEFT JOIN 
FSD_organizacije ON FSD_svetovalci.OrganizacijaID = FSD_organizacije.OrganizacijaID
LEFT JOIN 
(SELECT FSD_prijave_studentov.*, FSD_studenti.priimek, FSD_studenti.ime, FSD_studenti.VpisnaStevilka, FSD_studenti.VLLetnST FROM FSD_prijave_studentov LEFT JOIN FSD_studenti ON FSD_prijave_studentov.StudentID = FSD_studenti.studentID WHERE FSD_prijave_studentov.Solsko_letoID = ".$sl.") AS FSD_prijave_studentov ON FSD_svetovalci.SvetovalecID = FSD_prijave_studentov.Mentor_UB_ID
WHERE 
FSD_svetovalci.SvetovalecID = ".$id_ment." AND 
FSD_prijave_studentov.Solsko_letoID = ".$sl."
GROUP BY
FSD_svetovalci.Ime,
FSD_svetovalci.Priimekinime";

$result = $conn->query($sql);

$decoded = [];
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $decoded = $row;
  }
}

// sestavljena mentorstva - koliko v posameznem letniku
// FORMAT: 1 štud. 2. letnik, 1 štud. 3. letnik.
$sestavljena_Mentorstva = "";
$array_letnikov = explode(",", $decoded['Studenti']); // string to array forma: 1,2,2,4
// count repetted values in array
for ($i=1; $i < 10; $i++) { 
  // preglej koliko posameznih je notri in dodaj
  $count = 0;
  foreach ($array_letnikov as $key => $value) {
    if ($value == $i) {
      $count++;
    }
  }
  // imamo števio ponovitev
  if ($count > 0) {
    // dodaj v string
    if ($i < 5) {
      $sestavljena_Mentorstva .= $count . " štud. " . $i . ". letnik, ";
    } else if ($i == 5) {
      $sestavljena_Mentorstva .= $count . " štud. 1. letnik 2. stopnje, ";
    } else if ($i == 6) {
      $sestavljena_Mentorstva .= $count . " štud. DIF za vpis na 2. stopnjo, ";
    } else if ($i == 7) {
      $sestavljena_Mentorstva .= $count . " štud. DIF, ";
    } else if ($i == 8) {
      $sestavljena_Mentorstva .= $count . " štud. DIF, ";
    } else if ($i == 9) {
      $sestavljena_Mentorstva .= $count . " štud. Erasmus, ";
    }
    
  }
}

// odstrani zadnji znak ", "
if (strlen($sestavljena_Mentorstva) > 0) {
  $sestavljena_Mentorstva = substr($sestavljena_Mentorstva, 0, -2);
  $sestavljena_Mentorstva .= ".";
} else {
  $sestavljena_Mentorstva = "Mentor v šolskem letu ni imel študentov.";
}


$conn->close();

// GENERIRAJ POTRDILO

require 'vendor/autoload.php';

//require_once 'bootstrap.php';
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('potrdiloMENTOR.docx');

// variables
//$templateProcessor->setValue('NAZIV', $naziv);
$templateProcessor->setValue('NAZIV', $naziv);
$templateProcessor->setValue('MENTOR', $mentor);
$templateProcessor->setValue('SOLSKO_LETO', $solsko_leto);
$templateProcessor->setValue('ST_STUDENTOV', $st_studentov);
$templateProcessor->setValue('ST_STUDENTOV_STRING', $sestavljena_Mentorstva);
$templateProcessor->setValue('ID_DOK', $id_ment);
$templateProcessor->setValue('SL_DOK', substr($solsko_leto, 2, 2) . substr($solsko_leto, 7, 2));
$templateProcessor->setValue('DATUM', date("d. m. Y"));

//ST_STUDENTOV_STRING

$file = "Potrdilo FSD - " . substr($mentor, 0, 100) . ".docx";

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
