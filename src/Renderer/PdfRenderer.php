<?php

namespace App\Estimations\Renderer;

use Dompdf\Dompdf;
use App\Estimations\Nodes\ListNode;

class PdfRenderer
{
  public function render(string $html): string
  {
    $dompdf = new Dompdf();
    $dompdf->loadHtml("$html");
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
  }
}
