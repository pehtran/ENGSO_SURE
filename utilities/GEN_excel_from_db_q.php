<?php

// dobi podatke
$id = $_GET['q'];

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

$sql = "SELECT sql_code, opis FROM FSD_views WHERE id=" . $id;
$result = $conn->query($sql);
$search_sql = '';
$opis = '';
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $search_sql = $row["sql_code"];
    $opis = $row["opis"];
  }
}


// dodatek datuma za SQL če gre za izpis zavarovanj
if ($id == 17) {
  $search_sql = $search_sql . "'" . $_GET['d'] . "' AND FSD_prijave_studentov.Praksa_OD <= '" . $_GET['de'] . "'";
  //echo $view_code;
}

//echo $search_sql; // sql OK

$data = [];
$result = $conn->query($search_sql);
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $data[] = $row;
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
header('Content-Disposition: attachment;filename="'.substr($opis, 0,100).'.xlsx"');
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

