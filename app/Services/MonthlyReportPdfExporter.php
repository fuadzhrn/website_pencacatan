<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory;

class MonthlyReportPdfExporter
{
    public function __construct(private Factory $viewFactory) {}

    /**
     * @param  array<string, mixed>  $report
     */
    public function make(array $report): string
    {
        $options = new Options([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
        ]);
        $options->setChroot(base_path());
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('a3', 'landscape');
        $dompdf->loadHtml($this->viewFactory->make('reports.monthly-pdf', [
            'report' => $report,
        ])->render(), 'UTF-8');
        $dompdf->render();

        return $dompdf->output(['compress' => false]);
    }
}
