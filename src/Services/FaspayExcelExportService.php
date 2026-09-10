<?php

namespace Vonso\FaspayTestLab\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Vonso\FaspayTestLab\Models\FaspayTestRun;

class FaspayExcelExportService
{
    private const ROWS = [
        '18.1' => 7, '18.2' => 8, '18.3' => 9, '18.4' => 10, '18.5' => 11,
        '18.6' => 12, '18.7' => 13, '18.8' => 14, '18.9' => 15, '18.10' => 16,
        '18.11' => 17, '18.12' => 18, '18.13' => 19, '18.14' => 20, '18.15' => 21,
        '18.16' => 22, '18.17' => 23, '18.18' => 24, '18.19' => 25, '18.20' => 26,
        '18.21' => 27, '18.22' => 28, '18.23' => 29, '18.24' => 30, '18.25' => 32,
    ];

    public function export(FaspayTestRun $run, string $path): string
    {
        $templatePath = $this->templatePath('FASPAY QRIS - Skenario Functional Test_V.3.2.xlsx');

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName('QR MPM');
        if ($sheet === null) {
            throw new RuntimeException('Worksheet QR MPM tidak ditemukan pada template resmi.');
        }

        $sheet->setCellValue('A3', 'Nama Penyedia Layanan: '.$run->merchant->name);

        foreach ($run->results as $item) {
            $row = self::ROWS[$item['test_no']] ?? null;
            if ($row === null) {
                continue;
            }

            if (is_array($item['request']) && is_array($item['response'])) {
                $sheet->setCellValue('E'.$row, $this->formatRequest($item['request']));
                $sheet->setCellValue('F'.$row, $this->formatResponse($item['response']));
            } else {
                $sheet->setCellValue('E'.$row, null);
                $sheet->setCellValue('F'.$row, null);
            }

            $sheet->setCellValue('G'.$row, $item['result']);
            if ($item['execution_type'] === 'manual' && filled($item['notes'])) {
                $notes = trim((string) $sheet->getCell('H'.$row)->getValue());
                $sheet->setCellValue('H'.$row, trim($notes."\n".$item['notes']));
            }
            $sheet->getStyle('E'.$row.':H'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    public function templatePath(string $filename): string
    {
        $published = storage_path('app/templates/faspay/'.$filename);
        if (is_file($published)) {
            return $published;
        }

        $bundled = __DIR__.'/../../templates/faspay/'.$filename;
        if (is_file($bundled)) {
            return $bundled;
        }

        throw new RuntimeException("Official Faspay XLSX template [{$filename}] tidak ditemukan.");
    }

    private function formatRequest(mixed $evidence): string
    {
        if (is_array($evidence) && array_is_list($evidence)) {
            return collect($evidence)->map(fn (array $request, int $index): string => '=== REQUEST '.($index + 1).($index === 0 ? ' / PREREQUISITE' : ' / TEST')." ===\n\n".$this->formatSingleRequest($request)
            )->implode("\n\n");
        }

        return is_array($evidence) ? $this->formatSingleRequest($evidence) : '';
    }

    private function formatSingleRequest(array $request): string
    {
        $headers = collect($request['headers'] ?? [])->only([
            'Content-Type', 'X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID',
        ])->map(fn (mixed $value, string $name): string => $name.': '.$value)->implode("\n");
        $body = json_encode($request['body'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return 'URL: '.($request['url'] ?? '')."\n\nHEADER:\n".$headers."\n\nBODY:\n".$body;
    }

    private function formatResponse(mixed $evidence): string
    {
        if (is_array($evidence) && array_is_list($evidence)) {
            return collect($evidence)->map(fn (array $response, int $index): string => '=== RESPONSE '.($index + 1).($index === 0 ? ' / PREREQUISITE' : ' / TEST')." ===\n\n".$this->encodeResponse($response)
            )->implode("\n\n");
        }

        return is_array($evidence) ? $this->encodeResponse($evidence) : '';
    }

    private function encodeResponse(array $response): string
    {
        return json_encode($response['body'] ?? $response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
