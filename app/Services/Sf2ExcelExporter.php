<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Sf2ExcelExporter
{
    private const TEMPLATE_DAY_COLUMNS = 25;

    private const MALE_START_ROW = 14;

    private const MALE_SLOT_COUNT = 21;

    private const MALE_TOTAL_ROW = 35;

    private const FEMALE_SLOT_COUNT = 25;

    private const FEMALE_TOTAL_ROW = 61;

    private const COMBINED_TOTAL_ROW = 62;

    private const FIRST_DAY_COLUMN = 4;

    private const ABSENT_COLUMN = 29;

    public function download(Sf2Register $register): StreamedResponse
    {
        $spreadsheet = $this->fill($register);
        $filename = 'SF2-'.$this->safeFilename($register->sectionName).'-'.$register->yearMonth.'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function fill(Sf2Register $register): Spreadsheet
    {
        $path = resource_path('templates/sf2-daily-attendance.xlsx');

        if (! is_file($path)) {
            throw new RuntimeException('The SF2 Excel template is missing.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $dayCount = count($register->days);
        $extraDayColumns = max(0, $dayCount - self::TEMPLATE_DAY_COLUMNS);

        $sheet->setCellValue('C6', '');
        $sheet->setCellValue('K6', $register->schoolYear);
        $sheet->setCellValue('X6', $register->monthLabel);
        $sheet->setCellValue('C8', $register->schoolName);
        $sheet->setCellValue('X8', $register->gradeLevel);

        if ($extraDayColumns > 0) {
            $sheet->insertNewColumnBefore('AC', $extraDayColumns);
        }

        $sheet->setCellValue(Coordinate::stringFromColumnIndex(self::ABSENT_COLUMN + $extraDayColumns).'8', $register->sectionName);

        foreach ($register->days as $index => $day) {
            $column = Coordinate::stringFromColumnIndex(self::FIRST_DAY_COLUMN + $index);
            $sheet->setCellValue($column.'11', $day['number']);
            $sheet->setCellValue($column.'12', $day['weekday_code']);
        }

        $absentColumn = Coordinate::stringFromColumnIndex(self::ABSENT_COLUMN + $extraDayColumns);
        $maleTotalRow = self::MALE_TOTAL_ROW;
        $femaleTotalRow = self::FEMALE_TOTAL_ROW;
        $combinedTotalRow = self::COMBINED_TOTAL_ROW;

        $extraMaleRows = max(0, count($register->males) - self::MALE_SLOT_COUNT);

        if ($extraMaleRows > 0) {
            $sheet->insertNewRowBefore($maleTotalRow, $extraMaleRows);
            $this->copyNameRowStyle($sheet, self::MALE_START_ROW, $maleTotalRow, $extraMaleRows, $extraDayColumns);
            $maleTotalRow += $extraMaleRows;
            $femaleTotalRow += $extraMaleRows;
            $combinedTotalRow += $extraMaleRows;
        }

        $femaleStartRow = $maleTotalRow + 1;
        $extraFemaleRows = max(0, count($register->females) - self::FEMALE_SLOT_COUNT);

        if ($extraFemaleRows > 0) {
            $sheet->insertNewRowBefore($femaleTotalRow, $extraFemaleRows);
            $this->copyNameRowStyle($sheet, $femaleStartRow, $femaleTotalRow, $extraFemaleRows, $extraDayColumns);
            $femaleTotalRow += $extraFemaleRows;
            $combinedTotalRow += $extraFemaleRows;
        }

        $this->writeLearnerRows($sheet, $register->males, self::MALE_START_ROW, $register->days, $absentColumn);
        $this->writeDailyTotals($sheet, $register->maleDailyPresent, $maleTotalRow, $register->days);
        $this->writeLearnerRows($sheet, $register->females, $femaleStartRow, $register->days, $absentColumn);
        $this->writeDailyTotals($sheet, $register->femaleDailyPresent, $femaleTotalRow, $register->days);
        $this->writeDailyTotals($sheet, $register->combinedDailyPresent, $combinedTotalRow, $register->days);

        return $spreadsheet;
    }

    /**
     * @param  list<array{student_id: int, name: string, marks: array<int, string>, absent_count: int}>  $rows
     * @param  list<array{number: int, date: string, weekday_code: string, is_weekday: bool, is_weekend: bool, is_past: bool, is_today: bool}>  $days
     */
    private function writeLearnerRows(Worksheet $sheet, array $rows, int $startRow, array $days, string $absentColumn): void
    {
        foreach ($rows as $offset => $row) {
            $excelRow = $startRow + $offset;
            $sheet->setCellValue('B'.$excelRow, $row['name']);

            foreach ($days as $index => $day) {
                $column = Coordinate::stringFromColumnIndex(self::FIRST_DAY_COLUMN + $index);
                $mark = $row['marks'][$day['number']] ?? '';
                $sheet->setCellValue($column.$excelRow, $mark === '(x)' ? '(x)' : '');
            }

            $sheet->setCellValue($absentColumn.$excelRow, $row['absent_count'] > 0 ? $row['absent_count'] : '');
        }
    }

    /**
     * @param  array<int, int>  $dailyPresent
     * @param  list<array{number: int, date: string, weekday_code: string, is_weekday: bool, is_weekend: bool, is_past: bool, is_today: bool}>  $days
     */
    private function writeDailyTotals(Worksheet $sheet, array $dailyPresent, int $row, array $days): void
    {
        foreach ($days as $index => $day) {
            $column = Coordinate::stringFromColumnIndex(self::FIRST_DAY_COLUMN + $index);
            $sheet->setCellValue($column.$row, $dailyPresent[$day['number']] ?? 0);
        }
    }

    private function copyNameRowStyle(Worksheet $sheet, int $sourceRow, int $insertRow, int $count, int $extraDayColumns): void
    {
        $lastColumn = Coordinate::stringFromColumnIndex(self::ABSENT_COLUMN + $extraDayColumns + 7);

        for ($offset = 0; $offset < $count; $offset++) {
            $destinationRow = $insertRow + $offset;
            $sheet->duplicateStyle(
                $sheet->getStyle('A'.$sourceRow.':'.$lastColumn.$sourceRow),
                'A'.$destinationRow.':'.$lastColumn.$destinationRow,
            );
        }
    }

    private function safeFilename(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9._-]/', '-', $value) ?: 'section';
    }
}
