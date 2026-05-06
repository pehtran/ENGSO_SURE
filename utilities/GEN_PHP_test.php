<?php

// dobi podatke
$id = $_GET['q'];

// data input
define("DB_HOST", "localhost");
define("DB_NAME", "eswrao40_FSD_praksa");
define("DB_USER", "eswrao40_FSD_praksa_mentor");
define("DB_PASS", "DA;WoN)?Fzh6");

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Change character set to utf8
//$conn -> set_charset("utf-8");

$sql = "SELECT sql_code FROM FSD_views WHERE id=" . $id;
$result = $conn->query($sql);
$search_sql = '';
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $search_sql = $row["sql_code"];
  }
}

$data = [];
$result = $conn->query($search_sql);
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $data[] = $row;
  }
}

$conn->close();

// naredi excel

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// Rename worksheet
$spreadsheet->getActiveSheet()->setTitle('CPŠ prenos');

//$sheet->setCellValue('A1', 'Hello World !');

// Set cell A5 with a string value
//$spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, 5, 'PhpSpreadsheet');


// CONSTRUCT AND FILL EXCELL
//$spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(2, 5, $id);
$clmn_start = 1;
$row_start = 1;
$keys = array_keys($data[0]);

// KEYS
foreach ($keys as $key_name) {
    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($clmn_start, $row_start, $key_name);
    $clmn_start = $clmn_start + 1;
}
$clmn_start = 1;
$row_start = $row_start + 1;

//VALUES
foreach ($data as $row) {
    
    foreach ($row as $key => $value) {
        $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($clmn_start, $row_start, $row[$key]);
        $clmn_start = $clmn_start + 1;
    }  

    $clmn_start = 1;
    $row_start = $row_start + 1;
  }


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$spreadsheet->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="01simple.xlsx"');
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