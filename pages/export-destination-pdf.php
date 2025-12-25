<?php
/**
 * Export PDF d'une destination
 * Utilise FPDF pour générer un PDF
 */

require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Télécharger FPDF si pas installé
if (!file_exists('../vendor/fpdf/fpdf.php')) {
    // Créer le répertoire vendor si nécessaire
    if (!is_dir('../vendor/fpdf')) {
        mkdir('../vendor/fpdf', 0755, true);
    }
    
    // Télécharger FPDF
    $fpdfContent = file_get_contents('http://www.fpdf.org/en/dl.php?v=186&f=zip');
    if ($fpdfContent) {
        file_put_contents('../vendor/fpdf.zip', $fpdfContent);
        $zip = new ZipArchive;
        if ($zip->open('../vendor/fpdf.zip') === TRUE) {
            $zip->extractTo('../vendor/fpdf');
            $zip->close();
        }
        unlink('../vendor/fpdf.zip');
    }
}

// Vérifier si FPDF est disponible
if (!file_exists('../vendor/fpdf/fpdf.php')) {
    die('FPDF non disponible. Impossible de générer le PDF.');
}

require_once('../vendor/fpdf/fpdf.php');

$pdo = getDBConnection();
$id = $_GET['id'] ?? null;

if (!$id) {
    die('ID de destination manquant');
}

// Récupérer la destination
$stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ? AND actif = 1");
$stmt->execute([$id]);
$dest = $stmt->fetch();

if (!$dest) {
    die('Destination introuvable');
}

// Récupérer les clubs associés
$stmtClubs = $pdo->prepare("
    SELECT c.nom, c.email, c.telephone, c.site_web
    FROM destination_clubs dc
    JOIN clubs c ON dc.club_id = c.id
    WHERE dc.destination_id = ?
");
$stmtClubs->execute([$id]);
$clubs = $stmtClubs->fetchAll();

// Créer le PDF
class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(14, 165, 233);
        $this->Cell(0, 10, 'VOYAGES ULM', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 5, 'Fiche de destination', 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(148, 163, 184);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - Club ULM Evasion - Maubeuge', 0, 0, 'C');
    }
    
    function SectionTitle($title)
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(15, 23, 42);
        $this->Cell(0, 8, $title, 0, 1);
        $this->SetDrawColor(14, 165, 233);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    function InfoRow($label, $value)
    {
        if (!$value) return;
        
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(71, 85, 105);
        $this->Cell(50, 6, $label . ' :', 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(15, 23, 42);
        $this->MultiCell(0, 6, $value);
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Titre principal
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(12, 74, 110);
$pdf->Cell(0, 10, iconv('UTF-8', 'windows-1252', $dest['nom'] ?: $dest['aerodrome']), 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', $dest['ville'] . ', ' . $dest['pays']), 0, 1);
$pdf->Ln(5);

// Code OACI
if ($dest['code_oaci']) {
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetFillColor(224, 242, 254);
    $pdf->SetTextColor(14, 165, 233);
    $pdf->Cell(0, 10, 'Code OACI: ' . $dest['code_oaci'], 0, 1, 'C', true);
    $pdf->Ln(5);
}

// Informations générales
$pdf->SectionTitle('Informations generales');
$pdf->InfoRow('Aerodrome', iconv('UTF-8', 'windows-1252', $dest['aerodrome']));
$pdf->InfoRow('Coordonnees GPS', $dest['latitude'] . ', ' . $dest['longitude']);
$pdf->InfoRow('Type de piste', iconv('UTF-8', 'windows-1252', $dest['type_piste']));
$pdf->InfoRow('Longueur piste', $dest['longueur_piste_m'] ? $dest['longueur_piste_m'] . ' m' : null);
$pdf->InfoRow('Frequence radio', $dest['frequence_radio']);
$pdf->Ln(3);

// Accès
$pdf->SectionTitle('Acces autorise');
$acces = [];
if ($dest['acces_ulm']) $acces[] = 'ULM';
if ($dest['acces_avion']) $acces[] = 'Avion';
$pdf->InfoRow('Types d\'aeronefs', implode(', ', $acces));
$pdf->Ln(3);

// Services
$pdf->SectionTitle('Services disponibles');
$pdf->InfoRow('Carburant', $dest['carburant'] ? 'Disponible' : 'Non disponible');
$pdf->InfoRow('Restaurant', $dest['restaurant'] ? 'Disponible' : 'Non disponible');
$pdf->InfoRow('Hebergement', $dest['hebergement'] ? 'Disponible' : 'Non disponible');
$pdf->Ln(3);

// Description
if ($dest['description']) {
    $pdf->SectionTitle('Description');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', strip_tags($dest['description'])));
    $pdf->Ln(3);
}

// Points d'intérêt
if ($dest['points_interet']) {
    $pdf->SectionTitle('Points d\'interet');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(15, 23, 42);
    $pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', strip_tags($dest['points_interet'])));
    $pdf->Ln(3);
}

// Clubs associés
if (!empty($clubs)) {
    $pdf->SectionTitle('Clubs sur place');
    foreach ($clubs as $club) {
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(12, 74, 110);
        $pdf->Cell(0, 6, iconv('UTF-8', 'windows-1252', $club['nom']), 0, 1);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(71, 85, 105);
        if ($club['telephone']) {
            $pdf->Cell(0, 5, '  Tel: ' . iconv('UTF-8', 'windows-1252', $club['telephone']), 0, 1);
        }
        if ($club['email']) {
            $pdf->Cell(0, 5, '  Email: ' . $club['email'], 0, 1);
        }
        if ($club['site_web']) {
            $pdf->Cell(0, 5, '  Web: ' . iconv('UTF-8', 'windows-1252', $club['site_web']), 0, 1);
        }
        $pdf->Ln(2);
    }
}

// Output
$filename = 'destination_' . ($dest['code_oaci'] ?: $dest['id']) . '.pdf';
$pdf->Output('D', $filename);
