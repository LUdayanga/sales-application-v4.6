<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DemoPrint extends Controller
{
   public function AddToPdf(){
    $pdf = new Fpdi();


    // add a page
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',14);

    // set the source file
    $path = public_path("white.pdf");

    $pdf->setSourceFile($path);

    // import page 1
    $tplId = $pdf->importPage(1);


    // use the imported page and place it at point 10,10 with a width of 100 mm
    $pdf->useTemplate($tplId, null, null, null, 210, true);

    $pdf->SetXY(90, 110);
    $pdf->Write(0.1,"Demo Test");

// Preview PDF
    $pdf->Output('I',"Demotest.pdf");
   } 
}
