<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

require('backend/lib/fpdf/fpdf.php');
include_once 'backend/config/database.php';
include_once 'backend/core/student.php';
include_once 'backend/core/payment.php';

$database = new Database();
$db = $database->getConnection();
$student = new Student($db);
$payment = new Payment($db);

$student_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['student_id'];

$student_data = $student->getById($student_id);
$payments = $payment->getByStudentId($student_id);

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial','B',12);
        $this->Cell(80);
        $this->Cell(30,10,'Payment History Report',1,0,'C');
        $this->Ln(20);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times','',12);

$pdf->Cell(0,10,'Student: '.$student_data['first_name'].' '.$student_data['last_name'],0,1);
$pdf->Cell(0,10,'Student Code: '.$student_data['student_code'],0,1);
$pdf->Ln(10);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(40,10,'Semester',1);
$pdf->Cell(40,10,'Payment Type',1);
$pdf->Cell(30,10,'Amount',1);
$pdf->Cell(40,10,'Payment Date',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
foreach($payments as $row)
{
    $pdf->Cell(40,10,$row['semester_name'],1);
    $pdf->Cell(40,10,$row['payment_type'],1);
    $pdf->Cell(30,10,$row['amount'],1);
    $pdf->Cell(40,10,$row['payment_date'],1);
    $pdf->Ln();
}

$pdf->Output();
?>