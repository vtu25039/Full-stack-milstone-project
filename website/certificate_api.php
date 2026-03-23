<?php
require_once __DIR__ . '/config.php';
requireLogin();
require_once __DIR__ . '/fpdf/fpdf.php';

$cert_uuid = trim($_GET['cert_uuid'] ?? '');
if (!$cert_uuid) {
    http_response_code(400);
    die('Missing certificate ID.');
}

$db = getDB();
$uid = (int)$_SESSION['user_id'];

// Fetch certificate + attempt + user + quiz info
$sql = '
    SELECT c.cert_uuid, c.issued_at,
           u.name  AS user_name,
           q.title AS quiz_title,
           a.score
    FROM certificates c
    JOIN attempts a     ON a.id   = c.attempt_id
    JOIN users    u     ON u.id   = c.user_id
    JOIN quizzes  q     ON q.id   = a.quiz_id
    WHERE c.cert_uuid = ? AND c.user_id = ?
';
$stmt = $db->prepare($sql);
$stmt->bind_param('si', $cert_uuid, $uid);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();

if (!$cert) {
    http_response_code(404);
    die('Certificate not found or access denied.');
}

// ---- Build PDF -------------------------------------------------------
$pdf = new FPDF('L', 'mm', 'A4');   // Landscape A4
$pdf->SetMargins(0, 0, 0);
$pdf->AddPage();

$W = 297;  // page width  (landscape A4)
$H = 210;  // page height

// ---- Background: dark deep-blue gradient simulation (filled rects) ----
$pdf->SetFillColor(10, 15, 40);
$pdf->Rect(0, 0, $W, $H, 'F');

// Decorative corner accent top-left
$pdf->SetFillColor(80, 0, 220);
$pdf->Rect(0, 0, 60, 4, 'F');
$pdf->SetFillColor(120, 40, 255);
$pdf->Rect(0, 0, 4, 60, 'F');

// Decorative corner accent bottom-right
$pdf->SetFillColor(80, 0, 220);
$pdf->Rect($W-60, $H-4, 60, 4, 'F');
$pdf->SetFillColor(120, 40, 255);
$pdf->Rect($W-4, $H-60, 4, 60, 'F');

// Gold horizontal dividers
$pdf->SetDrawColor(212, 175, 55);
$pdf->SetLineWidth(0.8);
$pdf->Line(20, 28, $W-20, 28);
$pdf->Line(20, $H-28, $W-20, $H-28);

// ---- Header: site name -----------------------------------------------
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY(0, 10);
$pdf->Cell($W, 10, strtoupper(SITE_NAME), 0, 1, 'C');

// ---- Title -----------------------------------------------------------
$pdf->SetFont('Helvetica', 'B', 28);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetXY(0, 35);
$pdf->Cell($W, 18, 'CERTIFICATE OF ACHIEVEMENT', 0, 1, 'C');

// Sub-title
$pdf->SetFont('Helvetica', '', 13);
$pdf->SetTextColor(180, 180, 220);
$pdf->SetXY(0, 55);
$pdf->Cell($W, 10, 'This is to proudly certify that', 0, 1, 'C');

// ---- Recipient Name --------------------------------------------------
$pdf->SetFont('Helvetica', 'B', 24);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY(0, 68);
$pdf->Cell($W, 14, $cert['user_name'], 0, 1, 'C');

// Underline the name with gold
$nameW = $pdf->GetStringWidth($cert['user_name']);
$nameX = ($W - $nameW) / 2;
$pdf->SetDrawColor(212, 175, 55);
$pdf->SetLineWidth(0.5);
$pdf->Line($nameX, 83, $nameX + $nameW, 83);

// ---- Body text -------------------------------------------------------
$pdf->SetFont('Helvetica', '', 12);
$pdf->SetTextColor(200, 200, 230);
$pdf->SetXY(0, 87);
$pdf->Cell($W, 9, 'has successfully completed and passed the quiz:', 0, 1, 'C');

// Quiz title
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->SetTextColor(100, 200, 255);
$pdf->SetXY(0, 97);
$pdf->Cell($W, 12, $cert['quiz_title'], 0, 1, 'C');

// Score
$pdf->SetFont('Helvetica', '', 12);
$pdf->SetTextColor(200, 200, 230);
$pdf->SetXY(0, 111);
$pdf->Cell($W, 9, 'with a score of  ' . number_format($cert['score'], 1) . '%', 0, 1, 'C');

// ---- Footer section --------------------------------------------------
$issuedDate = date('F j, Y', strtotime($cert['issued_at']));

// Left: Issue date
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY(30, $H-24);
$pdf->Cell(60, 7, 'ISSUE DATE', 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(200, 200, 230);
$pdf->SetXY(30, $H-17);
$pdf->Cell(60, 6, $issuedDate, 0, 0, 'C');

// Center: Seal circle
$cx = $W / 2;
$cy = $H - 18;
$pdf->SetDrawColor(212, 175, 55);
$pdf->SetLineWidth(1.2);
// Draw a circle approximation with ellipse (using rect with rounded appearance via text)
$pdf->SetFont('Helvetica', 'B', 8);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY($cx-18, $cy-8);
$pdf->Cell(36, 6, strtoupper(SITE_NAME), 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 7);
$pdf->SetTextColor(180, 180, 220);
$pdf->SetXY($cx-18, $cy-2);
$pdf->Cell(36, 5, 'VERIFIED', 0, 1, 'C');
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY($cx-18, $cy+3);
$pdf->Cell(36, 6, chr(9733), 0, 1, 'C');   // star

// Right: Certificate ID
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(212, 175, 55);
$pdf->SetXY($W-90, $H-24);
$pdf->Cell(60, 7, 'CERTIFICATE ID', 0, 0, 'C');
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(160, 160, 200);
$pdf->SetXY($W-90, $H-17);
$pdf->Cell(60, 6, strtoupper(substr($cert['cert_uuid'], 0, 16)), 0, 0, 'C');

// ---- Output ----------------------------------------------------------
$filename = 'Certificate_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $cert['user_name']) . '.pdf';
$pdf->Output('D', $filename);
exit;
