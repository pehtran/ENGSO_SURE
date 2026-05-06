<?php

//print_r($org_data);
$id_vloge = $_POST['id_vloge'];

//dobi besedilo vloge

//SELECT * FROM `FSD_vloge_za_spremembe_podatkov`

include_once('../config/db.php');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Change character set to utf8
$conn->set_charset("utf8");

$sql = "SELECT * FROM FSD_vloge_za_spremembe_podatkov WHERE VlogeID = " . $id_vloge;

$result = $conn->query($sql);

$vloga_data = [];

if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        // sortiranje kateri gredo notri
        // ali če je razpisano kakšno mesto
        $vloga_data[] = $row;
    }
}

$vloga = str_replace("\n", '', $vloga_data[0]['Vloga']);
$vloga = json_decode($vloga);

// pridobi šolsko leto
$sql = "SELECT solsko_leto FROM FSD_admin_variables WHERE id = " . $vloga_data[0]['solsko_leto'];

$result = $conn->query($sql);
$solsko_leto = "";
if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        // sortiranje kateri gredo notri
        // ali če je razpisano kakšno mesto
        $solsko_leto = $row['solsko_leto'];
    }
}

// pridobi Tistega ki je oddal vlogo
$sql = "SELECT personal_name, personal_lastname FROM users WHERE user_id = " . $vloga_data[0]['vlogo_oddal_ID'];

$result = $conn->query($sql);
$vlogo_oddal = "";
if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        // sortiranje kateri gredo notri
        // ali če je razpisano kakšno mesto
        $vlogo_oddal = $row['personal_name'] . " " . $row['personal_lastname'];
    }
}

// TEST KER V JSONU ŠE NI NOVEGA ŠOLSKEGA LETA. TO SE MORA NAREDIT KO SE INICIRA NOVO LETO!! 
//$solsko_leto = "2022/2023";

//print_r($org_data);
$conn->close();

// GENERATE PDF
//require('fpdf/fpdf.php');


// Optionally define the filesystem path to your system fonts
// otherwise tFPDF will use [path to tFPDF]/font/unifont/ directory
// define("_SYSTEM_TTFONTS", "C:/Windows/Fonts/");

require('tfpdf.php');


//class PDF extends FPDF
class PDF extends tFPDF
{
    // Page header
    function Header()
    {
        // Logo
        $this->Image('../images/UL-Fakulteta-za-socialno-delo.gif', 10, 6, 30);
        // Arial bold 15
        //$this->AddFont('OpenSans-Regular', '', 'OpenSans-Regular.ttf', true);
        $this->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
        $this->SetFont('DejaVu', '', 16);
        //$this->SetFont('Arial','I',8);
        //$this->SetFont('OpenSans-Regular', '', 8);
        // Move to the right
        //$this->Cell(80);
        // Title
        $this->Cell(0, 10, 'CENTER ZA PRAKTIČNI ŠTUDIJ', 0, 0, 'R');
        // Line break
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
        $this->Cell(0, 10, 'Stran ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Add a Unicode font (uses UTF-8)
//$pdf->AddFont('OpenSans','','OpenSans-Regular.ttf',true);
//$pdf->SetFont('OpenSans','',14);

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
// Add a Unicode font (uses UTF-8)
$pdf->AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
$pdf->SetFont('DejaVu', '', 8);

$pdf->Cell(160, 5, "Št. vloge: ", 0, 0, "R");
$pdf->Cell(0, 5,  $vloga_data[0]['VlogeID'], 0, 1, "R");

$pdf->Cell(160, 5, "Datum oddaje vloge: ", 0, 0, "R");
$pdf->Cell(0, 5,  $vloga_data[0]['Datum_vloge'], 0, 1, "R");

$pdf->Cell(160, 5, "Vlogo oddal: ", 0, 0, "R");
$pdf->Cell(0, 5,  $vlogo_oddal, 0, 1, "R");

$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(0, 10, "POTRDILO O ODDAJI VLOGE: ", 0, 1, '', true);
$pdf->Cell(50, 7, "Šolsko leto: ", 0, 0);
$pdf->Cell(0, 7, $solsko_leto, 0, 1);

$pdf->Cell(50, 7, "Naziv: ", 0, 0);
$pdf->Cell(0, 7,  $vloga->NAZIV, 0, 1);

$pdf->Cell(50, 7, "Naslov: ", 0, 0);
$pdf->Cell(0, 7, $vloga->NASLOV . ", " . $vloga->POSTA, 0, 1);

$pdf->Cell(50, 7, "Odgovorna oseba: ", 0, 0);
$pdf->Cell(0, 7, $vloga->odgovorna_oseba . ", " . $vloga->email, 0, 1);


$pdf->Cell(0, 10, "MENTORJI:", 0, 1, '', true);
$pdf->Cell(50, 10, "Kontaktna oseba: ", 0, 1);

// loop za mentorje
$loop_mentorji = json_decode($vloga_data[0]['Mentorji']);
$prepreci_podvajanje_imen = [];
for ($i = 0; $i < count($loop_mentorji); $i++) {
    //$oseba = json_decode($loop_mentorji[$i]);
    $kontakt = "";
    if ($loop_mentorji[$i]->Kontaktna_oseba == 1) {
        $kontakt = "- KONTAKTNA OSEBA";

        // MARKO JE REKEL DA SE NA POTRDILU IZPISUJE IZKLJUČNI IN SAMO KONTAKTNA OSEBA. 11. 9. 2025
    }
    if ($loop_mentorji[$i]->Kontaktna_oseba == 1 && !in_array($loop_mentorji[$i]->Ime . $loop_mentorji[$i]->Priimekinime, $prepreci_podvajanje_imen)) {
        $pdf->Cell(50, 5, "", 0, 0);
        $pdf->Cell(0, 5, $loop_mentorji[$i]->Ime . " " . $loop_mentorji[$i]->Priimekinime . ", " . $loop_mentorji[$i]->naziv . ", "  . $loop_mentorji[$i]->email . " " . $kontakt, 0, 1);
        // če zapiše, zapiši še ime
        array_push($prepreci_podvajanje_imen, $loop_mentorji[$i]->Ime . $loop_mentorji[$i]->Priimekinime );
    }
}
$pdf->ln();



$pdf->Cell(0, 10, "RAZPISANA PROSTA MESTA:", 0, 1, '', true);
$pdf->Cell(50, 7, "Prosta mesta 1. in 2. letnik: ", 0, 0);
$stevilo = json_encode($vloga->PROSTOVOLJCEV);
$parsedData = json_decode($stevilo, true);
$value = $parsedData;
$pdf->Cell(0, 7, $value, 0, 1);

$pdf->Cell(50, 7, "Prosta mesta 3.letnik: ", 0, 0);
$stevilo = json_encode($vloga->PRAKTIKANTOV3);
$parsedData = json_decode($stevilo, true);
$value = $parsedData;
$pdf->Cell(0, 7, $value, 0, 1);

// področja priprava podatkov 
$podrocja = json_decode($vloga_data[0]['razpisana_podrocja']);
$pdf->SetTextColor(150, 150, 150);

if (count($podrocja->letnik_3) > 0) {
    // področja 3. letnik
    $pdf->Cell(10, 4, "-", 0, 0);
    $pdf->Cell(50, 4, "Področja: ", 0, 1);

    foreach ($podrocja->letnik_3 as $value) {
        $pdf->Cell(20, 7, "", 0, 0);
        $pdf->Cell(0, 7, $value[0] . ": " . $value[1] . " prosta mesta", 0, 1);
    }
}
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(50, 7, "Prosta mesta 4.letnik: ", 0, 0);
$stevilo = json_encode($vloga->PRAKTIKANTOV4);
$parsedData = json_decode($stevilo, true);
$value = $parsedData;
$pdf->Cell(0, 7, $value, 0, 1);

$pdf->SetTextColor(150, 150, 150);
// področja 4. letnik
if (count($podrocja->letnik_4) > 0) {
    $pdf->Cell(10, 4, "-", 0, 0);
    $pdf->Cell(50, 4, "Področja: ", 0, 1);

    foreach ($podrocja->letnik_4 as $value) {
        $pdf->Cell(20, 7, "", 0, 0);
        $pdf->Cell(0, 7, $value[0] . ": " . $value[1] . " prosta mesta", 0, 1);
    }
}
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(50, 7, "Prosta mesta podiplomski: ", 0, 0);
$stevilo = json_encode($vloga->PRAKTIKANTOV5);
$parsedData = json_decode($stevilo, true);
$value = $parsedData;
$pdf->Cell(0, 7, $value, 0, 1);

$pdf->Cell(50, 7, "Prosta mesta ERASMUS: ", 0, 0);
$stevilo = json_encode($vloga->PRAKTIKANTOV_ERASMUS);
$parsedData = json_decode($stevilo, true);
$value = $parsedData;
$pdf->Cell(0, 7, $value, 0, 1);

$pdf->Cell(0, 10, "OPIS PRAKSE:", 0, 1, '', true);
$pdf->ln();
$pdf->Cell(50, 5, "Opis prakse (1. in 2. letnik): ", 0, 0);
$pdf->MultiCell(0, 5, $vloga_data[0]['vsebina12'], 0, 1);
$pdf->ln(5);
$pdf->Cell(50, 5, "Opis prakse (3. in 4. letnik): ", 0, 0);
$pdf->MultiCell(0, 5, $vloga_data[0]['vsebina345'], 0, 1);
$pdf->ln(5);
$pdf->Cell(50, 5, "Opis prakse (ERASMUS): ", 0, 0);
$pdf->MultiCell(0, 5, $vloga_data[0]['vsebinaERASMUS'], 0, 1);
$pdf->ln(5);
$pdf->Cell(50, 5, "Opombe: ", 0, 0);
$pdf->MultiCell(0, 5, $vloga_data[0]['Opombe'], 0, 1);

$pdf->Output();
