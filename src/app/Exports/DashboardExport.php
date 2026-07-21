<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\FromView;

class DashboardExport extends DefaultValueBinder implements FromView, ShouldAutoSize, WithEvents, WithColumnFormatting, WithCustomValueBinder
{
    public $reports;

    public function __construct($reports)
    {
        $this->reports = $reports;
    }

    public function view(): View
    {
        return view('exports.dashboard', [
            'reports' => $this->reports
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // reference
            'B' => NumberFormat::FORMAT_TEXT, // title
            'C' => NumberFormat::FORMAT_TEXT, // category
            'D' => NumberFormat::FORMAT_TEXT, // owner
            'E' => NumberFormat::FORMAT_TEXT, // status

            'F' => NumberFormat::FORMAT_DATE_DATETIME, // created_at
            'G' => NumberFormat::FORMAT_DATE_DATETIME, // updated_at
            'H' => NumberFormat::FORMAT_DATE_DATETIME, // sign_deadline_at
            'I' => NumberFormat::FORMAT_DATE_DATETIME, // expires_at

            'J' => NumberFormat::FORMAT_NUMBER, // total_signers
            'K' => NumberFormat::FORMAT_NUMBER, // signed_signers
            'L' => NumberFormat::FORMAT_NUMBER, // rejected_signers
            'M' => NumberFormat::FORMAT_NUMBER, // pending_signers

            'N' => NumberFormat::FORMAT_NUMBER_00, // approval_time_hours
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // Formatação global
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('SansSerif');
                $sheet->getParent()->getDefaultStyle()->getFont()->setSize(9);

                // Formatação do cabeçalho
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:' . $sheet->getHighestColumn() . '1');
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->getColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF6F7D97');

                // Formatação das colunas
                $columns = [
                    'A' => Alignment::HORIZONTAL_LEFT,
                    'B' => Alignment::HORIZONTAL_LEFT,
                    'C' => Alignment::HORIZONTAL_LEFT,
                    'D' => Alignment::HORIZONTAL_LEFT,
                    'E' => Alignment::HORIZONTAL_CENTER,

                    'F' => Alignment::HORIZONTAL_CENTER,
                    'G' => Alignment::HORIZONTAL_CENTER,
                    'H' => Alignment::HORIZONTAL_CENTER,
                    'I' => Alignment::HORIZONTAL_CENTER,

                    'J' => Alignment::HORIZONTAL_CENTER,
                    'K' => Alignment::HORIZONTAL_CENTER,
                    'L' => Alignment::HORIZONTAL_CENTER,
                    'M' => Alignment::HORIZONTAL_CENTER,

                    'N' => Alignment::HORIZONTAL_RIGHT,
                ];

                foreach ($columns as $column => $alignment) {
                    $sheet->getStyle($column)->getAlignment()->setHorizontal($alignment);
                }
            }
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Estilo de borda da célula
        $cell->getStyle($cell->getCoordinate())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF6F7D97'],
                ],
            ],
        ]);

        // Destaca o status do processo
        if ($cell->getColumn() === 'E') {

            $style = $cell->getStyle($cell->getCoordinate());

            switch ($value) {
                case 'approved':
                    $style->getFont()->getColor()->setARGB('FFFFFFFF');
                    $style->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF28A745');
                    break;

                case 'awaiting-approval':
                    $style->getFont()->getColor()->setARGB('FF000000');
                    $style->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFFFC107');
                    break;

                case 'draft':
                    $style->getFont()->getColor()->setARGB('FFFFFFFF');
                    $style->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF6C757D');
                    break;

                case 'failed':
                    $style->getFont()->getColor()->setARGB('FFFFFFFF');
                    $style->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFDC3545');
                    break;

                case 'canceled':
                    $style->getFont()->getColor()->setARGB('FFFFFFFF');
                    $style->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF343A40');
                    break;
            }
        }

        return parent::bindValue($cell, $value);
    }
}
