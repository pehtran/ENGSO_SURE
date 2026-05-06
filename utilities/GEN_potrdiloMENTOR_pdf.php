<?php

$svetovalec_id = $_GET['o'];
$naziv_id = $_GET['n'];
$id_sl = $_GET['sl'];

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


// solsko leto
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
(SELECT FSD_prijave_studentov.*, FSD_studenti.priimek, FSD_studenti.ime, FSD_studenti.VpisnaStevilka, FSD_studenti.VLLetnST FROM FSD_prijave_studentov LEFT JOIN FSD_studenti ON FSD_prijave_studentov.StudentID = FSD_studenti.studentID WHERE FSD_prijave_studentov.Solsko_letoID = ".$id_sl.") AS FSD_prijave_studentov ON FSD_svetovalci.SvetovalecID = FSD_prijave_studentov.Mentor_UB_ID
WHERE 
FSD_svetovalci.SvetovalecID = ".$svetovalec_id." AND 
FSD_prijave_studentov.Solsko_letoID = ".$id_sl."
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

// številu mentorstev dodaj še število morebitnih somentorstev
$sql_somentor = "SELECT COUNT(id) as count
FROM FSD_somentorji
WHERE JSON_EXTRACT(Somentor_data, '$.SvetovalecID') = ?";

$stmt_somentor = $conn->prepare($sql_somentor);
$stmt_somentor->bind_param("s", $svetovalec_id);
$stmt_somentor->execute();
$stmt_somentor->bind_result($somentorjev);
$stmt_somentor->fetch();
$stmt_somentor->close();

// Ensure both values are integers before adding them
$decoded['Stevilo_mentorstev'] = intval($decoded['Stevilo_mentorstev']) + intval($somentorjev);


$sql = "SELECT NAZIV FROM FSD_organizacije WHERE OrganizacijaID = " . $naziv_id;
$result = $conn->query($sql);

$naziv = "";
if ($result->num_rows > 0) {
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $naziv = $row['NAZIV'];
  }
}

// solsko leto je zmeraj zadnje v bazi
$solsko_leto = 0;
$sql = "SELECT MAX(id) AS last_id FROM FSD_admin_variables";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $solsko_leto = $row['last_id'];
}



// dodaj generirano v LOG
$sql = "INSERT INTO `LOG_potrdila_o_mentorstvu`(`id_mentorja`, `solsko_leto`) VALUES ('".$svetovalec_id."','".$solsko_leto."')";
$conn->query($sql);

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
$decoded['Priimekinime'] = str_replace('#', '', $decoded['Priimekinime']);
$pdf->Cell(0, 10, $decoded['Ime'] . " " . $decoded['Priimekinime'], 'B', 1, "C");
$pdf->SetFont('DejaVu','', 8);
$pdf->Cell(0, 5, 'mentor / mentorica v učni bazi', 0, 1, "C");

$pdf->Ln(5);
$pdf->SetFont('DejaVu','', 12);
$pdf->Cell(0, 5, mb_strtoupper($naziv), 'B', 1, "C");
$pdf->SetFont('DejaVu','', 8);
$pdf->Cell(0, 5, 'naziv učne baze', 0, 1, "C");


$pdf->Ln(10);


// Add a Unicode font (uses UTF-8)
$pdf->SetFont('DejaVu','',9);
$pdf->MultiCell(190, 5, 'je v študijskem letu '.$solsko_leto_naziv.' opravljal_a naloge mentorja_ice študentom socialnega dela. Število mentorstev in študentov: ' . $sestavljena_Mentorstva, 0, 'L'); // product multiline
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
$pdf->MultiCell(190, 5, '• Predlagal_a je opisno oceno uspešnosti študentov pri praktičnem delu;', 0, 'L', 1); // product multiline
$pdf->MultiCell(190, 5, '• Obseg prakse: 1. letnik (100ur), 2. letnik (100ur), 3. letnik (240 ur), 4. letnik (160 ur), 1.l. 2. st. (80-100 ur), dif. za vpis na 2.st.
(60 ur), Erasmus (60 ur).', 0, 'L', 1); // product multiline


$pdf->Ln(30);
$pdf->SetFont('DejaVu', '', 8);
$pdf->Cell(100, 5, 'Št. potrdila: ' . $svetovalec_id . "/" . substr($solsko_leto_naziv, 2, 2) . substr($solsko_leto_naziv, 7, 2), 0, 0, "L");
$pdf->Cell(100, 5, 'D E K A N J A', 0, 1, "C");
$pdf->Cell(100, 5, 'V Ljubljani, ' . date("d. m. Y"), 0, 0, "L");
$pdf->Cell(100, 5, 'Izr. Prof. Dr. Mojca Urek', 0, 1, "C");

$pdf->Image('podpis.png', 90, 227, 100);
//LINE 1

//$invoice_name = "POTRDILO MENTORJU_" . date('Y')  . ".pdf";
$pdf->Output();

