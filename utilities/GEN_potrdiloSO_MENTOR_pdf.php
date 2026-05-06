<?php

$svetovalec_ime = $_GET['o'];
$naziv_id = $_GET['n'];
$stevilo_a = $_GET['s'];
$solsko_leto = $_GET['sl'];

if ($naziv_id == "") {
  $naziv_id = 0;
}
//mail("gasper.krstulovic@gmail.com", "pogodba", $svetovalec_id);


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


$sql = "SELECT NAZIV FROM FSD_organizacije WHERE OrganizacijaID = " . $naziv_id;
$result = $conn->query($sql);

$naziv = "";
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $naziv = $row['NAZIV'];
  }
}

// solsko leto 

$sql = "SELECT * FROM FSD_admin_variables WHERE id = " . $solsko_leto;
$result = $conn->query($sql);
$sl_text = "";
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $sl_text = $row['solsko_leto'];
  }
}

// dodaj generirano v LOG
$sql = "INSERT INTO `LOG_potrdila_o_mentorstvu`(`id_mentorja`, `solsko_leto`) VALUES ('99999','".$solsko_leto."')";
$conn->query($sql);
$last_insert_id = $conn->insert_id;

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
$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
$pdf->SetFont('DejaVu', '', 13);
$pdf->SetTextColor(0, 0, 0);


$pdf->Ln(30);
$pdf->SetFont('Times', 'BI', 38);
$pdf->SetTextColor(155, 155, 155); // grey color
$pdf->Cell(0, 5, 'POTRDILO', 0, 1, "C");

$pdf->SetTextColor(0, 0, 0); // black


$pdf->SetFont('DejaVu','', 8);

$pdf->Ln(20);
$pdf->SetFont('DejaVu','', 18);
$pdf->Cell(0, 10, $svetovalec_ime, 'B', 1, "C");
$pdf->SetFont('DejaVu','', 8);
$pdf->Cell(0, 5, 'mentor / mentorica v učni bazi', 0, 1, "C");

$pdf->Ln(5);
$pdf->SetFont('DejaVu','', 12);
$pdf->Cell(0, 5, mb_strtoupper($naziv), 'B', 1, "C");
$pdf->SetFont('DejaVu','', 8);
$pdf->Cell(0, 5, 'naziv učne baze', 0, 1, "C");


$pdf->Ln(20);


// Add a Unicode font (uses UTF-8)
$pdf->SetFont('DejaVu','',9);
$pdf->MultiCell(190, 5, 'je v študijskem letu '.$sl_text.' opravljal/a naloge mentorja/ice študentom socialnega dela. Število študentov pod njegovim/njenim mentorstvom: ' . $stevilo_a, 0, 'L'); // product multiline
$pdf->Ln(3);
$pdf->MultiCell(190, 5, 'Obvezna praksa študentov Fakultete za socialno delo obsega od 100 do 240 ur/leto. Praksa je sestavni del učnega procesa, mentor oz. mentorica v učni bazi pa tista pomembna oseba, ki bistveno prispeva k razvijanju študentove profesionalne identitete. Mentor oz. mentorica je pri vpeljevanju študentov v prakso opravil_a naslednje konkretne naloge:', 0, 'L'); // product multiline
$pdf->Ln(5);

$pdf->SetFillColor(225, 225, 225); // Yellow color
$pdf->SetFont('DejaVu','',8);
$pdf->MultiCell(190, 5, '• Prevzel_a je odgovornost za kvalitetno izvedbo prakse po programu;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Opravil_a je vse potrebne priprave za prihod študentov na učno bazo in jim zagotovil_a pogoje za učenje; ', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Udeležil_a se je seminarja in konzultacij za mentorje študentom na praksi, ki jih je organizirala FSD;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Pripravil_a je program dela študentov na učni bazi v skladu s programom prakse', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Namenil_a je poseben čas za redna srečanja s študenti, usmerjanje njihovega procesa učenja po metodi supervizije in za vsakodnevne krajše konzultacije;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Pomagal_a je mentorju oz. mentorici iz FSD pri spoznavanju konkretnih nalog, ki jih opravljajo študenti in so značilne za učno bazo;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Sodeloval_a je pri evalvaciji praktičnega dela študentov in uspešnosti njihovega dela;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Predlagal_a je opisno oceno uspešnosti študentov pri praktičnem delu. ', 0, 'L', 1); // product multiline


$pdf->Ln(37);
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(100, 5, 'Št. potrdila: 999' . $last_insert_id. "/" . substr($sl_text,2,2) . substr($sl_text,7,2) , 0, 0, "L");
$pdf->Cell(100, 5, 'D E K A N J A', 0, 1, "C");
$pdf->Cell(100, 5, 'V Ljubljani, ' . date("d. m. Y"), 0, 0, "L");
$pdf->Cell(100, 5, 'Izr. Prof. Dr. Mojca Urek', 0, 1, "C");

$pdf->Image('podpis.png', 90, 227, 100);
//LINE 1

//$invoice_name = "POTRDILO MENTORJU_" . date('Y')  . ".pdf";
$pdf->Output();

