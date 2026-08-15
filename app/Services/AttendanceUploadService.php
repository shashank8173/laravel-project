<?php

namespace App\Services;

use App\Models\AttendanceMachineDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Shuchkin\SimpleXLSX;

class AttendanceUploadService
{
    /**
     * Import biometric machine XLSX using the same layout as the legacy uploader.
     *
     * @return array{imported:int,skipped:bool,message:string}
     */
    public function importBiometricXlsx(UploadedFile $file): array
    {
        require_once base_path('legacy/SimpleXLSX.php');

        $xlsx = SimpleXLSX::parse($file->getRealPath());
        if (! $xlsx) {
            throw new \RuntimeException(SimpleXLSX::parseError() ?: 'Unable to parse XLSX');
        }

        $dim = $xlsx->dimension();
        $cols = $dim[0] ?? 0;
        $x = 1;
        $countRow = 0;
        $countRow8 = 0;
        $realRowCount = 0;
        $index = 0;
        $date = '';
        $empIdStr = '';
        $empNameStr = '';
        $dayNumber = '';
        $rowWiseTimeArray = [];
        $calculate = 0;

        foreach ($xlsx->readRows() as $r) {
            $realRowCount++;
            $timeRow1 = '';

            for ($i = 0; $i < $cols; $i++) {
                if ($i == 2 && $x == 65) {
                    $date = $r[$i] ?? '';
                }

                if ($i == 2 && $x == 127) {
                    $countRow = 1;
                    $countRow8 = 1;
                    $empIdStr = ($r[$i] ?? '').',';
                }

                if ($i == 2 && $x > 127 && $countRow % 2 != 0) {
                    $calculate = $countRow8 - 1;
                    if ($x - ($calculate * 31) == 127) {
                        $empIdStr .= ($r[$i] ?? '').',';
                    }
                }

                if ($i == 10 && $x == 135) {
                    $empNameStr = ($r[$i] ?? '').',';
                }

                if ($i == 10 && $x > 135 && $countRow % 2 != 0) {
                    if ($x - ($calculate * 31) == 135) {
                        $empNameStr .= ($r[$i] ?? '').',';
                    }
                }

                if ($x >= 94 && $x <= 123) {
                    $dayNumber .= ($r[$i] ?? '').',';
                }

                if ($realRowCount >= 6 && $realRowCount % 2 == 0) {
                    $timeRow1 .= ($r[$i] ?? '').',';
                }

                $x++;
            }

            $countRow++;
            $countRow8++;

            if ($realRowCount >= 6 && $realRowCount % 2 == 0) {
                $rowWiseTimeArray[$index] = $timeRow1;
                $index++;
            }
        }

        $date = str_replace(' ', '', (string) $date);
        if ($date === '' || ! str_contains($date, '~')) {
            throw new \RuntimeException('Could not detect date range from the XLSX. Use the biometric export format.');
        }

        $startDate = substr($date, 0, strpos($date, '~'));
        $endDate = substr($date, strpos($date, '~') + 1);
        $year = substr($date, 0, 4);
        $month = (int) substr($date, 5, 2);

        $exists = AttendanceMachineDetail::query()
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($exists) {
            return [
                'imported' => 0,
                'skipped' => true,
                'message' => "Attendance for {$year}-{$month} already exists. Delete existing rows first or use another month.",
            ];
        }

        $empIds = array_values(array_filter(array_map('trim', explode(',', trim($empIdStr, ',')))));
        $empNames = array_values(array_filter(array_map(
            fn ($n) => strtolower(trim($n)),
            explode(',', trim($empNameStr, ','))
        )));

        $count = min(count($empIds), count($empNames), count($rowWiseTimeArray));
        $imported = 0;
        $today = now()->toDateString();
        $addedBy = Auth::id();

        for ($i = 0; $i < $count; $i++) {
            $s = rtrim(trim($rowWiseTimeArray[$i] ?? ''), ',');
            $parts = $s === '' ? [] : explode(',', $s);

            $payload = [
                'year' => $year,
                'month' => $month,
                'added_date' => $today,
                'from_date' => $startDate,
                'to_date' => $endDate,
                'added_by' => $addedBy,
                'attandance_id' => (int) $empIds[$i],
                'employee_name' => $empNames[$i],
            ];

            for ($d = 0; $d < 31; $d++) {
                $payload['date_in_out'.($d + 1)] = $parts[$d] ?? '';
                $payload['date'.($d + 1)] = sprintf('%s-%02d-%02d', $year, $month, $d + 1);
            }

            AttendanceMachineDetail::create($payload);
            $imported++;
        }

        $dir = public_path('attandance-file');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $stored = uniqid().'-'.preg_replace('/\s+/', '-', $file->getClientOriginalName());
        $file->move($dir, $stored);

        return [
            'imported' => $imported,
            'skipped' => false,
            'message' => "Imported {$imported} employee attendance rows for {$year}-{$month}.",
        ];
    }

    /**
     * Simple CSV: attendance_id,employee_name,year,month,in_out1,in_out2,...,in_out31
     */
    public function importCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw new \RuntimeException('Unable to open CSV');
        }

        $header = fgetcsv($handle);
        $imported = 0;
        $today = now()->toDateString();
        $addedBy = Auth::id();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            $attendanceId = (int) $row[0];
            $name = strtolower(trim((string) $row[1]));
            $year = (string) $row[2];
            $month = (int) $row[3];

            $exists = AttendanceMachineDetail::query()
                ->where('year', $year)
                ->where('month', $month)
                ->where('attandance_id', $attendanceId)
                ->exists();

            if ($exists) {
                continue;
            }

            $payload = [
                'year' => $year,
                'month' => $month,
                'added_date' => $today,
                'from_date' => sprintf('%s-%02d-01', $year, $month),
                'to_date' => date('Y-m-t', strtotime(sprintf('%s-%02d-01', $year, $month))),
                'added_by' => $addedBy,
                'attandance_id' => $attendanceId,
                'employee_name' => $name,
            ];

            for ($d = 0; $d < 31; $d++) {
                $payload['date_in_out'.($d + 1)] = $row[4 + $d] ?? '';
                $payload['date'.($d + 1)] = sprintf('%s-%02d-%02d', $year, $month, $d + 1);
            }

            AttendanceMachineDetail::create($payload);
            $imported++;
        }

        fclose($handle);

        return [
            'imported' => $imported,
            'skipped' => false,
            'message' => "Imported {$imported} CSV attendance rows.",
        ];
    }
}
