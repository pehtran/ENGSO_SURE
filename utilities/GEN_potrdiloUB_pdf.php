<?php

$id_org = $_GET['o'];
$id_sl = $_GET['sl'];
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

// dobi naslov 

$naziv = "";
$naslov = "";

$solsko_leto_naziv = "";
// querry
$qqq = "SELECT solsko_leto FROM `FSD_admin_variables` WHERE id=" . $id_sl;
$result = $conn->query($qqq);

if ($result->num_rows > 0) {
  // output data of each row
  while ($row = $result->fetch_assoc()) {
    $solsko_leto_naziv = $row['solsko_leto'];
  }
}




$sql = "SELECT NAZIV, NASLOV, POSTA_ST, POSTA FROM FSD_organizacije WHERE OrganizacijaID = " . $id_org;
$result = $conn->query($sql);

if ($result->num_rows > 0) {
  // output data of each row
  while ($row = $result->fetch_assoc()) {
    $naziv = $row['NAZIV'];
    $naslov = $row['NASLOV'] . ", " . $row['POSTA_ST'] .  " " . $row['POSTA'];
  }
}

$conn->close();

// GENERIRAJ POTRDILO

require 'vendor/autoload.php';

// PDF GENERATOR
require('../utilities/fpdf/tfpdf/tfpdf.php');

// PDF
// header and footer
class PDF extends tFPDF
{
  // Page header
  function Header()
  {
    // Logo
    $this->Image('../images/UL_FSD_logoVER-CMYK_barv.jpg', 60, 6, 100);


    $this->Ln(30);
  }

  // Page footer
  function Footer()
  {
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    // Arial italic 8
    $this->SetFont('Arial', 'I', 8);
    // Page number
    //$this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
  }
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);

$pdf->SetFont('DejaVu', '', 13);
$pdf->SetTextColor(0, 0, 0);


$pdf->Ln(30);
$pdf->SetFont('Times', 'BI', 38);
$pdf->SetTextColor(155, 155, 155); // grey color
$pdf->Cell(0, 5, 'POTRDILO', 0, 1, "C");

$pdf->SetTextColor(0, 0, 0); // black


$pdf->SetFont('DejaVu', '', 8);

$pdf->Ln(20);
$pdf->SetFont('DejaVu', '', 14);
$pdf->MultiCell(0, 10, mb_strtoupper($naziv), 0, 'C');
$pdf->Cell(0, 0, "", 'B', 1, "C");
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(0, 5, 'ustanova / organizacija / zavod / društvo', 0, 1, "C");

$pdf->Ln(5);
$pdf->SetFont('DejaVu', '', 11);
$pdf->Cell(0, 5, mb_strtoupper($naslov), 'B', 1, "C");
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(0, 5, 'naslov / kraj', 0, 1, "C");


$pdf->Ln(20);


// Add a Unicode font (uses UTF-8)

//$pdf->AddFont('DejaVu','','DejaVuSans-BoldOblique.ttf',true);
$pdf->SetFont('DejaVu', '', 14);
$pdf->MultiCell(190, 8, 'je v šolskem letu '.$solsko_leto_naziv.' učna baza za področje socialnega dela in sodeluje pri izvajanju praktičnega dela izobraževanja študentov Fakultete za socialno delo.', 0, 'C'); // product multiline
$pdf->Ln(25);
$pdf->SetFont('DejaVu', '', 10);
$pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
$pdf->MultiCell(190, 5, 'Obvezna praksa študentov Fakultete za socialno delo, I. oz. II. letnika obsega 140ur, III. letnika, 240 ur in IV. letnika, 160 ur ter I. letnika II. stopnje, 80ur, učna baza pa je v tem času študentom zagotovila strokovno usposobljenega_no mentorja_ico in pogoje za kvalitetno izvedbo prakse na področju socialnega dela, kjer deluje. ', 0, 'L'); // product multiline
$pdf->Ln(5);

$pdf->SetFillColor(225, 225, 225); // Yellow color
$pdf->SetFont('DejaVu', '', 8);


$pdf->Ln(37);
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(90, 5, '', 0, 0, "L");
$pdf->Cell(100, 5, 'D E K A N J A', 0, 1, "C");
$pdf->Cell(90, 5, 'V Ljubljani, ' . date("d. m. Y"), 0, 0, "L");
$pdf->Cell(100, 5, 'Izr. Prof. Dr. Mojca Urek', 0, 1, "C");

$pdf->Image('podpis.png', 80, 218, 100);
//LINE 1

//$invoice_name = "POTRDILO MENTORJU_" . date('Y')  . ".pdf";
$pdf->Output();
