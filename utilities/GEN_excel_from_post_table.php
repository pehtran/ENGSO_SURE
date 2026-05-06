<?php

// dobi podatke
$sl = $_GET['sl'];
$tip = $_GET['tip'];
$letnik = $_GET['letnik'];

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
// Change character set to utf8
$conn->set_charset("utf8");

// za TIP: : uredniki
if ($tip == 'uredniki') {
 $sql = "SELECT
    FSD_organizacije.NAZIV,
    users.personal_name AS 'IME UREDNIKA',
    users.personal_lastname AS 'PRIIMEK UREDNIKA',
    users.user_email AS 'EMAIL',
    COUNT(FSD_prijave_studentov.OrganizacijaID_veljavna) AS 'Število prijav v šolskem letu'
FROM
    FSD_organizacije
JOIN users ON FSD_organizacije.DAVCNA COLLATE utf8mb3_general_ci = users.asociated_organization
LEFT JOIN FSD_prijave_studentov ON 
    FSD_organizacije.OrganizacijaID = FSD_prijave_studentov.OrganizacijaID_veljavna
    AND FSD_prijave_studentov.Solsko_letoID = " . intval($sl) . "
GROUP BY
    FSD_organizacije.NAZIV,
    users.personal_name,
    users.personal_lastname,
    users.user_email
ORDER BY users.user_id;";
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
}


// za TIP : kontakti
if ($tip == 'kontakti') {
    $sql = "SELECT 
    FSD_organizacije.NAZIV,
    FSD_svetovalci.Ime,
    FSD_svetovalci.Priimekinime,
    FSD_svetovalci.email,
    COUNT(FSD_prijave_studentov.OrganizacijaID_veljavna) AS 'Število prijav v šolskem letu'
FROM 
    FSD_organizacije
LEFT JOIN FSD_povezave_svetovalci 
    ON FSD_povezave_svetovalci.OrganizacijaID = FSD_organizacije.OrganizacijaID
    AND FSD_povezave_svetovalci.visible = 1 
    AND FSD_povezave_svetovalci.kontaktna_oseba = 1 
    AND FSD_povezave_svetovalci.Solsko_letoID = " . intval($sl) . "
JOIN FSD_svetovalci 
    ON FSD_povezave_svetovalci.SvetovalecID = FSD_svetovalci.SvetovalecID
LEFT JOIN FSD_prijave_studentov 
    ON FSD_organizacije.OrganizacijaID = FSD_prijave_studentov.OrganizacijaID_veljavna 
    AND FSD_prijave_studentov.Solsko_letoID = " . intval($sl) . "
GROUP BY
    FSD_organizacije.NAZIV,
    FSD_svetovalci.Ime,
    FSD_svetovalci.Priimekinime,
    FSD_svetovalci.email
ORDER BY
	FSD_povezave_svetovalci.Povezave_svt_ID;";
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
}

// za TIP : mentorji
if ($tip == 'mentorji') {
    $sql = "SELECT
    FSD_organizacije.NAZIV,
    FSD_studenti.ime AS 'IME STUDENTA',
    FSD_studenti.priimek AS 'PRIIMEK STUDENTA',
    FSD_studenti.VLLetnST AS 'LETNIK',
    FSD_svetovalci.Ime AS 'IME MENTORJA',
    FSD_svetovalci.Priimekinime AS 'PRIIMEK MENTORJA',
    FSD_svetovalci.email AS 'EMAIL MENTORJA'
FROM
    FSD_prijave_studentov
LEFT JOIN FSD_organizacije ON FSD_prijave_studentov.OrganizacijaID_veljavna = FSD_organizacije.OrganizacijaID
LEFT JOIN FSD_studenti ON FSD_prijave_studentov.StudentID = FSD_studenti.studentID
LEFT JOIN FSD_svetovalci ON FSD_prijave_studentov.Mentor_UB_ID = FSD_svetovalci.SvetovalecID
WHERE
    FSD_studenti.VLLetnST = ".$letnik." AND FSD_studenti.Solsko_letoID_prijava = " . $sl . ";";
    $result = $conn->query($sql);

    $data = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
}

// data postane XLSX

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
header('Content-Disposition: attachment;filename="'.substr($tip, 0,100).'.xlsx"');
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

