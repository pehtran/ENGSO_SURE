<?php

$letnik = $_GET['letnik'];
$sl = $_GET['sl'];


include_once('../config/db.php');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Change character set to utf8
$conn->set_charset("utf8");

$sql = "SELECT * FROM FSD_organizacije";
$result = $conn->query($sql);

$org_data = [];
$org_data_posebni = [];

if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        // sortiranje kateri gredo notri
        // ali če je razpisano kakšno mesto
        $PROSTOVOLJCEV = json_decode($row['PROSTOVOLJCEV']);
        $PRAKTIKANTOV3 = json_decode($row['PRAKTIKANTOV3']);
        $PRAKTIKANTOV4 = json_decode($row['PRAKTIKANTOV4']);
        $PRAKTIKANTOV5 = json_decode($row['PRAKTIKANTOV5']);
        $PRAKTIKANTOV_ERASMUS = json_decode($row['PRAKTIKANTOV_ERASMUS']);

        // za prvi in drugi letnik . za ostale je treba še prirpavit 
        if ($letnik == 1 or $letnik == 2) {
            if ($PROSTOVOLJCEV->$sl > 0) {
                $org_data[] = $row;
            }
        } elseif ($row['Poseben_status_razpis'] == 1) {
            // ostanejo tisti ki nimajo razpisnih mest 
            $org_data_posebni[] = $row;
        };
    }
} else {
    echo "0 results";
}


//print_r($org_data);
$conn->close();

//print_r($org_data);

// GENERATE PDF
require('fpdf/fpdf.php');


class PDF extends FPDF
{
    protected $col = 0; // Current column
    protected $y0;      // Ordinate of column start

    function Header()
    {
        // Page header
        global $letnik;
        global $sl;
        // Logo
        $this->Image('../images/UL-Fakulteta-za-socialno-delo.gif', 90, 6, 30);
        // Line break
        $this->Ln(30);
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'RAZPIS ' . $sl . ' ' . $letnik . '. letnik', 0, 0, 'C');
        // Line break
        $this->Ln(20);

        // Save ordinate
        $this->y0 = $this->GetY();
    }

    function Footer()
    {
        // Page footer
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Stran ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    function SetCol($col)
    {
        // Set position at a given column
        // določi položaj vsakega izmed stolpcev
        $this->col = $col;
        $x = 10 + $col * 100;
        $this->SetLeftMargin($x);
        $this->SetX($x);
    }

    function AcceptPageBreak()
    {
        // Method accepting or not automatic page break
        // tukaj se določi število stolpcev
        if ($this->col < 1) {
            // Go to next column
            $this->SetCol($this->col + 1);
            // Set ordinate to top
            $this->SetY($this->y0);
            // Keep on page
            return false;
        } else {
            // Go back to first column
            $this->SetCol(0);
            // Page break
            return true;
        }
    }

    function ChapterTitle($num, $label)
    {
        // Title
        $this->SetFont('Arial', '', 12);
        $this->SetFillColor(200, 220, 255);
        $this->Cell(0, 6, "Chapter $num : $label", 0, 1, 'L', true);
        $this->Ln(4);
        // Save ordinate
        $this->y0 = $this->GetY();
    }

    function ChapterBody($file)
    {
        // Font
        $file = json_decode($file);
        $this->SetFont('Times', '', 12);

        // Read text file
        foreach ($file as $org) {
            //tukaj se določi vsebino

            $txt_title = strtoupper($org->NAZIV);
            $txt_title = urldecode($txt_title);

            $txt_body = $org->vsebina12;


            // Output text in a 9 cm width column
            $this->SetFont('Times', '', 12);
            $this->MultiCell(90, 5, $txt_title);


            $this->SetFont('Times', '', 10);
            $this->MultiCell(90, 4, $txt_body);


            // prostor ene vrstice
            $this->Ln();
        }





        // NA KONCU - zaključek poglavja
        $this->Ln();
        // Mention
        $this->SetFont('', 'I');
        $this->Cell(0, 5, '(end of excerpt)');
        // Go back to first column
        $this->SetCol(0);
    }

    function PrintChapter($num, $title, $file)
    {
        // Add chapter
        $this->AddPage();
        $this->ChapterTitle($num, $title);
        $this->ChapterBody($file);
    }
    function DumpFont($FontName)
    {
        $this->AddPage();
        // Title
        $this->SetFont('Times', '', 16);
        $this->Cell(0, 6, $FontName, 0, 1, 'C');
        // Print all characters in columns
        $this->SetCol(0);
        for ($i = 32; $i <= 255; $i++) {
            $this->SetFont('Times', '', 14);
            $this->Cell(12, 5.5, "$i : ");
            $this->SetFont($FontName);
            $this->Cell(0, 5.5, chr($i), 0, 1);
        }
        $this->SetCol(0);
    }
}

$pdf = new PDF();
$title = 'Razpis CPŠ';
//$pdf->DumpFont('Arial');
//$pdf->DumpFont('Times');

$pdf->SetTitle($title);
$pdf->AliasNbPages();
$pdf->SetAuthor('CPŠ');
$pdf->PrintChapter(1, 'REDNI', json_encode($org_data));
$pdf->PrintChapter(2, 'POSEBNI', json_encode($org_data_posebni));
$pdf->Output();
