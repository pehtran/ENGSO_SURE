<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/fpdf/tfpdf/tfpdf.php';

// 1. Get the POST data - a single action object (see actions.json shape),
// posted from the modal's "Download PDF" button (sureGPVUE.js downloadActionPDF).
$payload = isset($_POST['payload']) ? $_POST['payload'] : null;
$data = json_decode($payload, true);

// FPDF's core fonts (Arial etc.) expect Windows-1252, not UTF-8 - without
// this, curly quotes/em dashes in the source content render as mojibake.
function toLatin1($str) {
    if ($str === null) return '';
    $converted = @iconv('UTF-8', 'CP1252//TRANSLIT', (string)$str);
    return $converted !== false ? $converted : (string)$str;
}

function toLatin1List($items) {
    return array_map('toLatin1', is_array($items) ? $items : []);
}

// Same category ribbon colours used across the site (sureGPVUE.js /
// sureVUE.js categoryColors), duplicated here since PHP can't share the
// JS module directly.
$categoryColors = [
    'SOCIAL SUPPORT' => '#c2410c',
    'COACHING' => '#92400e',
    'FINANCIAL STABILITY' => '#4d7c0f',
    'PARTICIPATION' => '#047857',
    'FACILITIES' => '#0f766e',
    'GOVERNANCE' => '#be185d',
];

function hexToRgb($hex) {
    $hex = ltrim((string)$hex, '#');
    if (strlen($hex) !== 6) return [159, 199, 170]; // fallback: brand sage green
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

$category = $data['category'] ?? '';
$accent = hexToRgb($categoryColors[strtoupper($category)] ?? '#9fc7aa');

class PDF extends tFPDF
{
    public $accent = [159, 199, 170];

    function Header() {
        $this->Image(__DIR__ . '/../images/SURE_LOGO.png', 10, 8, 24);
        $this->SetFont('Arial', 'B', 9);
        $this->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->SetXY(-70, 12);
        $this->Cell(60, 8, 'CRISIS RESILIENT CLUB GUIDE', 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $this->SetY(24);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130, 130, 130);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function SectionLabel($label) {
        if ($this->GetY() > 260) $this->AddPage();
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor($this->accent[0], $this->accent[1], $this->accent[2]);
        $this->Cell(0, 8, strtoupper($label), 0, 1, 'L');
        $this->SetTextColor(35, 35, 35);
        $this->SetFont('Arial', '', 10.5);
        $this->Ln(1);
    }

    function BodyText($text) {
        $this->SetFont('Arial', '', 10.5);
        $this->SetTextColor(50, 50, 50);
        $this->MultiCell(0, 6, $text, 0, 'L');
    }

    function ListItems($items, $numbered = false) {
        $this->SetFont('Arial', '', 10.5);
        $this->SetTextColor(50, 50, 50);
        $i = 1;
        foreach ($items as $item) {
            if ($this->GetY() > 265) $this->AddPage();
            $marker = $numbered ? ($i . '.') : chr(149); // bullet char in cp1252
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Cell(7, 6, $marker);
            $this->SetXY($x + 7, $y);
            $this->MultiCell(0, 6, $item, 0, 'L');
            $this->Ln(0.5);
            $i++;
        }
    }
}

$pdf = new PDF();
$pdf->accent = $accent;
$pdf->AddPage();

if ($data) {
    $title = toLatin1($data['title'] ?? 'Untitled action');

    // Category ribbon
    $pdf->SetFillColor($accent[0], $accent[1], $accent[2]);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $catLabel = toLatin1(strtoupper($category));
    $catWidth = $pdf->GetStringWidth($catLabel) + 8;
    $pdf->Cell($catWidth, 7, $catLabel, 0, 1, 'C', true);
    $pdf->Ln(3);

    // Title
    $pdf->SetTextColor(31, 58, 42);
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->MultiCell(0, 9, $title, 0, 'L');
    $pdf->Ln(1);

    // Development area / crisis type / target group, as compact meta lines
    $meta = [];
    if (!empty($data['development_area'])) $meta[] = 'Development area: ' . implode(', ', $data['development_area']);
    if (!empty($data['crysis_type'])) $meta[] = 'Crisis type: ' . implode(', ', $data['crysis_type']);
    if (!empty($data['target_group'])) $meta[] = 'Target group: ' . implode(', ', $data['target_group']);
    if ($meta) {
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->SetTextColor(110, 110, 110);
        foreach ($meta as $line) {
            $pdf->MultiCell(0, 5, toLatin1(ucfirst($line)), 0, 'L');
        }
    }
    $pdf->Ln(3);

    if (!empty($data['overview'])) {
        $pdf->SectionLabel('Overview');
        $pdf->BodyText(toLatin1($data['overview']));
    }

    if (!empty($data['guidelines'])) {
        $pdf->SectionLabel('Guidelines');
        $pdf->ListItems(toLatin1List($data['guidelines']), true);
    }

    if (!empty($data['cooperation_guidance'])) {
        $pdf->SectionLabel('Cooperation Guidance');
        $pdf->ListItems(toLatin1List($data['cooperation_guidance']), false);
    }

    if (!empty($data['case_example']) && is_array($data['case_example'])) {
        $ce = $data['case_example'];
        $hasContent = !empty($ce['organization']) || !empty($ce['context']) || !empty($ce['key_approaches']) || !empty($ce['quote']);
        if ($hasContent) {
            $pdf->SectionLabel('Case Example');

            if (!empty($ce['organization'])) {
                $orgLine = toLatin1($ce['organization']) . (!empty($ce['location']) ? ' - ' . toLatin1($ce['location']) : '');
                $pdf->SetFont('Arial', 'B', 10.5);
                $pdf->SetTextColor(40, 40, 40);
                $pdf->MultiCell(0, 6, $orgLine, 0, 'L');
            }
            if (!empty($ce['context'])) {
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->MultiCell(0, 6, toLatin1($ce['context']), 0, 'L');
            }
            $pdf->Ln(1);

            if (!empty($ce['key_approaches'])) {
                $pdf->ListItems(toLatin1List($ce['key_approaches']), false);
            }
            if (!empty($ce['outcome'])) {
                $pdf->SetFont('Arial', 'I', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->MultiCell(0, 6, toLatin1($ce['outcome']), 0, 'L');
                $pdf->Ln(1);
            }
            if (!empty($ce['quote'])) {
                $pdf->SetFont('Arial', 'I', 10.5);
                $pdf->SetTextColor(60, 60, 60);
                $pdf->MultiCell(0, 6, '"' . toLatin1($ce['quote']) . '"', 0, 'L');
            }
            if (!empty($ce['bullets'])) {
                $pdf->Ln(1);
                $pdf->ListItems(toLatin1List($ce['bullets']), false);
            }
        }
    }

    if (!empty($data['sources_and_resources'])) {
        $pdf->SectionLabel('Sources & Resources');
        $pdf->SetFont('Arial', '', 10);
        foreach ($data['sources_and_resources'] as $src) {
            if ($pdf->GetY() > 265) $pdf->AddPage();
            $label = toLatin1($src['title'] ?? '');
            $url = toLatin1($src['url'] ?? '');
            $pdf->SetTextColor(40, 40, 40);
            $pdf->MultiCell(0, 6, $label, 0, 'L');
            if ($url) {
                $pdf->SetFont('Arial', 'U', 9.5);
                $pdf->SetTextColor(30, 90, 130);
                $pdf->Write(5, $url, $src['url']);
                $pdf->Ln(6);
                $pdf->SetFont('Arial', '', 10);
            }
            $pdf->Ln(1);
        }
    }
} else {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No data available for display.', 0, 1);
}

$filenameBase = preg_replace('/[^A-Za-z0-9\-]+/', '_', strtolower(trim($data['title'] ?? 'action')));
$filenameBase = trim($filenameBase, '_');
if ($filenameBase === '') $filenameBase = 'action';

// 'I' = Inline (open in tab)
$pdf->Output('I', $filenameBase . '.pdf');
