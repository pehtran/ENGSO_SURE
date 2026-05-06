<?php

$id_org = $_GET['o'];
$studenti = $_GET['s'];
$studenti = explode(",", $studenti);
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

// dobi še povezane osebe

$sol_leto = "";
$sql = "SELECT solsko_leto FROM FSD_admin_variables";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $sol_leto = $row['solsko_leto'];
  }
}



// dobi tabelo z urami
$tabelica = [];
$sql = "SELECT ure_prakse FROM FSD_admin_variables";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $tabelica = $row['ure_prakse'];
    //$obveza = $tabelica;
  }
}

$tabelica = json_decode($tabelica, true);


$sql = "SELECT NAZIV FROM FSD_organizacije WHERE OrganizacijaID = " . $id_org;
$result = $conn->query($sql);

$naziv;
if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        // podatki o organizaciji
        $naziv = $row['NAZIV'];
    }
}

$studenti_imena = [];
$mentorji_FSD = [];
$mentorji_FSD_imena = [];
$mentorji_UB = [];
$mentorji_UB_imena = [];
$praksa_OD = "";
$praksa_DO = "";
$obseg = [];

// imena študentov
foreach ($studenti as $student) {
    $sql_studenti = "SELECT * FROM FSD_studenti WHERE studentID = " . $student;
    $result = $conn->query($sql_studenti);
    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            // izračunaj koliko ur obveze ima
            $obveza = "";

            $id = $row['studentID'];

            // preveri še ure prakse
            // letnik
            $obveza = $tabelica['Letnik_' . $row['VLLetnST']];
        
            // prepiši če je letnik 5 in če je izbina skupina nekaj drugega
            $obveza = $row['VLLetnST'] == 5 && $row['VLIzbSkupina'] == 'Dolgotrajna oskrba starih ljudi' ?  $tabelica['Dolgotrajna oskrba starih ljudi'] : $obveza;
            $obveza = $row['VLLetnST'] == 5 && $row['VLIzbSkupina'] == 'Skupnostna oskrba' ?  $tabelica['Skupnostna oskrba'] : $obveza;
            $obveza = $row['VLLetnST'] == 5 && $row['VLIzbSkupina'] == 'Psihosocialna podpora in pomoč' ?  $tabelica['Psihosocialna podpora in pomoč'] : $obveza;
            $obveza = $row['VLLetnST'] == 5 && $row['VLIzbSkupina'] == 'Socialna pravičnost in radikalne perspektive v socialnem delu' ?  $tabelica['Socialna pravičnost in radikalne perspektive v socialnem delu'] : $obveza;
            $obveza = $row['VLLetnST'] == 5 && $row['VLIzbSkupina'] == 'Socialno delo v vzgoji in izobraževanju' ?  $tabelica['Socialno delo v vzgoji in izobraževanju'] : $obveza;
            
            // magisterij
            $obveza = $row['VrstaStudijaStudenta'] == 'Magistrski študijski program druge stopnje Socialno delo z družino' ?  $tabelica['Magistrski študijski program druge stopnje Socialno delo z družino'] : $obveza;
            $obveza = $row['VrstaStudijaStudenta'] == 'Magistrski študijski program druge stopnje Duševno zdravje v skupnosti' ?  $tabelica['Magistrski študijski program druge stopnje Duševno zdravje v skupnosti'] : $obveza;
            
            // če so izredni
            $obveza = $row['VLNacST'] == 'izredni' ? $tabelica['izredni'] : $obveza;

            // še če so šesti letnik
            $obveza = $row['VLLetnST'] == 6 ?  $tabelica['Letnik_6'] : $obveza;

            // podatki o organizaciji
            array_push($studenti_imena, $row['ime'] . " " . $row['priimek'] . " (" . $obveza . " ur)");
            array_push($obseg, $row['VLLetnST']);
        }
    }

    $sql_mentorji = "SELECT * FROM FSD_prijave_studentov WHERE StudentID = " . $student;
    $result = $conn->query($sql_mentorji);
    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            // podatki o organizaciji // če je mentor definiran
            if ($row['MentorID'] > 0) {
                array_push($mentorji_FSD, $row['MentorID']);
            }
            if ($row['Mentor_UB_ID'] > 0) {
                // dobi podatek o mentorju
                array_push($mentorji_UB, $row['Mentor_UB_ID']);
            }

            $praksa_OD = $row['Praksa_OD'];
            $praksa_DO = $row['Praksa_DO'];
        }
    }
}

// dobi ime mentorja
foreach (array_unique($mentorji_FSD) as $ment_FSD) {
    $sql_ment_FSD = "SELECT * FROM FSD_mentorji WHERE MentorID = " . $ment_FSD;
    $result = $conn->query($sql_ment_FSD);
    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            // podatki o organizaciji
            $fixed_priimek = explode(' - ', $row['M_PRIIMEK']);
            $fixed_priimek = $fixed_priimek[0];
            array_push($mentorji_FSD_imena, $row['M_IME'] . " " . $fixed_priimek);
        }
    }
}

// dobi ime menotrja UB
foreach (array_unique($mentorji_UB) as $ment_UB) {
    $sql_ment_UB = "SELECT * FROM FSD_svetovalci WHERE SvetovalecID = " . $ment_UB;
    $result = $conn->query($sql_ment_UB);
    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            // podatki o organizaciji
            array_push($mentorji_UB_imena, $row['Ime'] . " " . $row['Priimekinime']);
        }
    }
}

// tole je da se ne zapre polje za urejanje
if (count($mentorji_FSD_imena) == 0) {
    $mentorji_FSD_imena = ["  "];
}

if (count($mentorji_UB_imena) == 0) {
    $mentorji_UB_imena = ["  "];
}

// popravi datume od do
$vsi_meseci = ["ni meseca", "januar", "februar", "marec", "april", "maj", "junij", "julij", "avgust", "september", "oktober", "november", "december"];
$praksa_OD = date("m", strtotime($praksa_OD));
$praksa_DO = date("m", strtotime($praksa_DO));

$praksa_OD = ltrim($praksa_OD, '0');
$praksa_DO = ltrim($praksa_DO, '0');

$praksa_OD = $vsi_meseci[$praksa_OD];
$praksa_DO = $vsi_meseci[$praksa_DO];

// obseg


$conn->close();

// GENERIRAJ POGODBO

require 'vendor/autoload.php';

//require_once 'bootstrap.php';
$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('POGODBA345.docx');

// variables
$templateProcessor->setValue('NAZIV', $naziv);
$templateProcessor->setValue('solsko_leto', $sol_leto);
$templateProcessor->setValue('studenti', implode(", ", $studenti_imena));
$templateProcessor->setValue('mentor_fsd', implode(", ", $mentorji_FSD_imena));
$templateProcessor->setValue('mentor_UB', implode(", ", $mentorji_UB_imena));
$templateProcessor->setValue('termin_OD', $praksa_OD);
$templateProcessor->setValue('termin_DO', $praksa_DO);
$templateProcessor->setValue('obseg', "");
$templateProcessor->setValue('datum', date("d. m. Y"));


$file = "P - " . substr($naziv, 0, 100) . ".docx";

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
