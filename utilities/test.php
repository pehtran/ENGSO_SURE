<?php
require 'vendor/autoload.php';
require('../utilities/fpdf/tfpdf/tfpdf.php');

// 1. Get the POST data
$payload = isset($_POST['payload']) ? $_POST['payload'] : null;

// 2. Decode the JSON into a PHP associative array
$data = json_decode($payload, true);

class PDF extends tFPDF
{
    function Header() {
        $this->Image('../images/SURE_LOGO.png', 10, 10, 30); // Adjust path and size as needed
        $this->Ln(10);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();

// Load a Unicode font if you need special characters (Slovenian čšž, etc.)
// $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
// $pdf->SetFont('DejaVu','',14);

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Certificate of Results', 0, 1, 'C');
$pdf->Ln(10);

if ($data) {
    // Accessing exportDate
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, 'Export Date: ' . $data['exportDate'], 0, 1);
    $pdf->Ln(5);

    // Accessing overallResults (Summary Data)
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Summary:', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    foreach ($data['overallResults'] as $key => $value) {
        $pdf->Cell(50, 10, ucfirst($key) . ":", 0, 0);
        $pdf->Cell(0, 10, $value, 0, 1);
    }

    // Accessing rawDetails (Questions)
    if (!empty($data['rawDetails'])) {
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Question Details:', 0, 1);
        $pdf->SetFont('Arial', '', 11);

        foreach ($data['rawDetails'] as $index => $question) {
            $text = ($index + 1) . ". " . ($question['text'] ?? 'Question');
            // MultiCell is better for long question text
            $pdf->MultiCell(0, 7, $text, 0, 'L'); 
            $pdf->Cell(0, 5, "Answer: " . ($question['answer'] ?? '/'), 0, 1);
            $pdf->Ln(2);
        }
    }
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No data available for display.', 0, 1);
}

// 3. Output to Browser Tab
// 'I' = Inline (open in tab), 'SURE_results.pdf' = default filename
$pdf->Output('I', 'SURE_results.pdf');