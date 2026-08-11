<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/fpdf/tfpdf/tfpdf.php';

// 1. Get the POST data
$payload = isset($_POST['payload']) ? $_POST['payload'] : null;

// 2. Decode the JSON into a PHP associative array
$data = json_decode($payload, true);

// FPDF's core fonts (Arial etc.) expect Windows-1252, not UTF-8 - without
// this, curly quotes/em dashes in the personalised copy render as mojibake.
function toLatin1($str) {
    if ($str === null) return '';
    $converted = @iconv('UTF-8', 'CP1252//TRANSLIT', (string)$str);
    return $converted !== false ? $converted : (string)$str;
}

class PDF extends tFPDF
{
    function Header() {
        $this->Image(__DIR__ . '/../images/SURE_LOGO.png', 10, 10, 30);
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

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'SURE Evaluation Tool - Results', 0, 1, 'C');
$pdf->Ln(10);

if ($data) {
    $pdf->SetFont('Arial', '', 10);
    $exportDate = !empty($data['exportDate']) ? date('d F Y, H:i', strtotime($data['exportDate'])) : '';
    $pdf->Cell(0, 8, 'Generated: ' . $exportDate, 0, 1);
    $pdf->Ln(4);

    // Competency summary table
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Competency Summary', 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(31, 58, 42);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(70, 8, 'Competency', 1, 0, 'L', true);
    $pdf->Cell(30, 8, 'Score', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Status', 1, 0, 'C', true);
    $pdf->Cell(0, 8, 'Questions', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    $overallResults = $data['overallResults'] ?? [];
    foreach ($overallResults as $result) {
        $pdf->SetFillColor(245, 241, 229);
        $pdf->Cell(70, 8, toLatin1($result['competency'] ?? ''), 1, 0, 'L', $fill);
        $pdf->Cell(30, 8, ($result['averageScore'] ?? '?') . ' / 5', 1, 0, 'C', $fill);
        $pdf->Cell(40, 8, toLatin1($result['status'] ?? ''), 1, 0, 'C', $fill);
        $pdf->Cell(0, 8, (string)($result['questionCount'] ?? ''), 1, 1, 'C', $fill);
        $fill = !$fill;
    }
    $pdf->Ln(6);

    // Per-competency recommendation
    foreach ($overallResults as $result) {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, toLatin1($result['competency'] ?? '') . ':', 0, 1);
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, toLatin1($result['recommendation'] ?? ''), 0, 'L');
        $pdf->Ln(2);
    }

    // Question-by-question detail, grouped by competency (questions arrive
    // in randomised quiz order, so group explicitly rather than relying on
    // consecutive entries sharing a competency).
    if (!empty($data['rawDetails'])) {
        $grouped = [];
        foreach ($data['rawDetails'] as $question) {
            $grouped[$question['Competency'] ?? 'Other'][] = $question;
        }

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Question Details', 0, 1);
        $pdf->Ln(2);

        $counter = 1;
        foreach ($grouped as $competency => $questions) {
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, toLatin1($competency), 0, 1);

            foreach ($questions as $question) {
                $pdf->SetFont('Arial', '', 11);
                $text = $counter . '. ' . toLatin1($question['Question_Text'] ?? 'Question');
                $pdf->MultiCell(0, 6, $text, 0, 'L');

                $answer = $question['Result'] ?? null;
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->Cell(0, 6, 'Answer: ' . ($answer !== null ? $answer . ' / 5' : 'Not answered'), 0, 1);
                $pdf->Ln(2);
                $counter++;
            }
            $pdf->Ln(3);
        }
    }
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No data available for display.', 0, 1);
}

// 3. Output to Browser Tab
// 'I' = Inline (open in tab), 'SURE_results.pdf' = default filename
$pdf->Output('I', 'SURE_results.pdf');