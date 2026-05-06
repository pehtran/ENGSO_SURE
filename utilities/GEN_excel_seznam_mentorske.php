<?php
session_start();
// dobi podatke
$letnik = $_GET['q'];

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

// mentor
$mentorID = $_SESSION['asociated_organization'];

// posebna pravila za test ali za tiste, ki imajo po več skupin v istem letniku
if ($mentorID == '889977') {
  $mentorID = '87';
}

if ($mentorID == '80139574') {
  $mentorID = '21';
}

if ($mentorID == '87') {
  $mentorID = '87 OR FSD_prijave_studentov.MentorID = 90';
}

if ($mentorID == '88') {
  $mentorID = '88 OR FSD_prijave_studentov.MentorID = 91';
}


if ($mentorID == '93') {
  $mentorID = '93 OR FSD_prijave_studentov.MentorID = 112';
}


if ($mentorID == '114') {
  $mentorID = '114 OR FSD_prijave_studentov.MentorID = 115';
}

if ($mentorID == '65') {
  $mentorID = '65 OR FSD_prijave_studentov.MentorID = 111';
}



$data = [];
$sql = "SELECT 
    FSD_studenti.VpisnaStevilka, 
    FSD_studenti.ime AS 'Ime študent_ka',
    FSD_studenti.priimek AS 'Priimek študent_ka',
    FSD_studenti.VLLetnST AS Letnik,
    FSD_studenti.email_UL AS 'Kontaktni email študent_ke', 
    FSD_organizacije.NAZIV AS 'Naziv UB', 
    s2.Ime AS 'Ime mentorja na UB',
    s2.Priimekinime AS 'Priimek mentorja na UB',
    s2.email AS 'Email mentorja na UB',
    s2.telefon AS 'Telefon mentorja na UB',

    -- concatenate all kontaktne osebe
    GROUP_CONCAT(DISTINCT CONCAT(s1.Ime, ' ', s1.Priimekinime) SEPARATOR ', ') AS 'Kontaktne osebe UB',
    GROUP_CONCAT(DISTINCT s1.email SEPARATOR ', ') AS 'Emaili kontaktnih oseb UB',
    GROUP_CONCAT(DISTINCT s1.telefon SEPARATOR ', ') AS 'Telefoni kontaktnih oseb UB'

FROM FSD_prijave_studentov 
LEFT JOIN FSD_studenti 
    ON FSD_prijave_studentov.StudentID = FSD_studenti.studentID
LEFT JOIN FSD_organizacije 
    ON FSD_prijave_studentov.OrganizacijaID_veljavna = FSD_organizacije.OrganizacijaID 
LEFT JOIN FSD_povezave_svetovalci 
    ON FSD_prijave_studentov.OrganizacijaID_veljavna = FSD_povezave_svetovalci.OrganizacijaID 
    AND FSD_povezave_svetovalci.kontaktna_oseba = 1 
    AND FSD_povezave_svetovalci.visible = 1
LEFT JOIN FSD_svetovalci s2 
    ON FSD_prijave_studentov.Mentor_UB_ID = s2.SvetovalecID
LEFT JOIN FSD_svetovalci s1 
    ON FSD_povezave_svetovalci.SvetovalecID = s1.SvetovalecID

WHERE 
    (FSD_prijave_studentov.MentorID = " . $mentorID . ") 
    AND FSD_studenti.VLLetnST = " . $letnik . " 
    AND FSD_prijave_studentov.Solsko_letoID = (
        SELECT MAX(id) FROM FSD_admin_variables
    )

GROUP BY 
    FSD_studenti.VpisnaStevilka, 
    FSD_studenti.ime, 
    FSD_studenti.priimek,
    FSD_studenti.VLLetnST,
    FSD_studenti.email_UL,
    FSD_organizacije.NAZIV,
    s2.Ime, 
    s2.Priimekinime, 
    s2.email, 
    s2.telefon

ORDER BY
    FSD_studenti.VLLetnST ASC,
    FSD_studenti.VLIzbSkupina ASC,
    FSD_organizacije.NAZIV ASC,
    FSD_studenti.priimek ASC";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}

$conn->close();


// naredi excel
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

//require_once __DIR__ . '/../Bootstrap.php';

$helper = new Sample();
if ($helper->isCli()) {
    $helper->log('This example should only be run from a Web Browser' . PHP_EOL);

    return;
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();

// Set document properties
$spreadsheet->getProperties()->setCreator('Maarten Balliauw')
    ->setLastModifiedBy('Maarten Balliauw')
    ->setTitle('Office 2007 XLSX Test Document')
    ->setSubject('Office 2007 XLSX Test Document')
    ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
    ->setKeywords('office 2007 openxml php')
    ->setCategory('Test result file');


// VALUES
$keys = array_keys($data[0]);
$clmn_start = 1;
$row_start = 1;

$spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
$spreadsheet->getDefaultStyle()->getFont()->setSize(8);
//$spreadsheet->getDefaultStyle()->getFont()->setBold(true);


$styleArray = [
  'font' => [
      'bold' => true,
  ]
];

// KEYS
foreach ($keys as $key_name) {
  $spreadsheet->setActiveSheetIndex(0)->setCellValue([$clmn_start, $row_start], $key_name);
  $spreadsheet->getActiveSheet()->getStyle([$clmn_start, $row_start])->applyFromArray($styleArray);
  
  $clmn_start = $clmn_start + 1;
}
$clmn_start = 1;
$row_start = $row_start + 1;



// ROWS
foreach ($data as $row) {
  foreach ($row as $key => $value) {
      // Set number format for large numbers
      if (is_numeric($value) && strlen($value) > 12) {
          $spreadsheet->getActiveSheet()->getStyle([$clmn_start, $row_start])->getNumberFormat()->setFormatCode('#');
      }
      // formatiraj datume
      if ($key == "Praksa_OD" || $key == "Praksa_DO") {
        // format date
        $date = new DateTime($row[$key]);
        // Format the date as "dd. mm. yyyy"
        $row[$key] = $date->format('d. m. Y');
        $spreadsheet->getActiveSheet()->getStyle([$clmn_start, $row_start])->getNumberFormat()->setFormatCode('dd. mm. yyyy');
      }


      // zapiši vpiši vrednost
      $spreadsheet->getActiveSheet()->setCellValue([$clmn_start, $row_start], $row[$key]);
      // formatiranje posameznih vrednosti

       // Set font to Calibri Light for values
       

       // Autofit column width based on content
       //$sheet->getColumnDimensionByColumn($clmn_start)->setAutoSize(true);
       //$sheet->getColumnDimensionByColumn($clmn_start)->setAutoSize(true);



      $clmn_start = $clmn_start + 1;
  }

  $clmn_start = 1;
  $row_start = $row_start + 1;
}

// autowidth
$spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
$spreadsheet->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);


// Rename worksheet
$spreadsheet->getActiveSheet()->setTitle('CPŠ izvoz');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$spreadsheet->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'."Mentorske_skupine_FSD".'.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;

?>