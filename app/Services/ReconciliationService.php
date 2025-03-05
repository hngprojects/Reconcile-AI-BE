<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;

class ReconciliationService
{
    public function reconcileFiles(string $file1Path, string $file2Path, string $keyColumn)
    {
        $data1 = $this->loadFile($file1Path, $keyColumn);
        $data2 = $this->loadFile($file2Path, $keyColumn);

        $matches = [];
        $differences = [];
        $onlyInFile1 = [];
        $onlyInFile2 = [];

        $indexed1 = array_column($data1, null, $keyColumn);
        $indexed2 = array_column($data2, null, $keyColumn);

        foreach ($indexed1 as $key => $row1) {
            if (isset($indexed2[$key])) {
                $row2 = $indexed2[$key];
                $matches[] = $row1;

                $diff = [];
                foreach ($row1 as $col => $val1) {
                    if ($col !== $keyColumn && $val1 != ($row2[$col] ?? null)) {
                        $diff[$col] = ['file1' => $val1, 'file2' => $row2[$col] ?? null];
                    }
                }
                if (!empty($diff)) {
                    $diff[$keyColumn] = $key;
                    $differences[] = $diff;
                }
                unset($indexed2[$key]);
            } else {
                $onlyInFile1[] = $row1;
            }
        }

        $onlyInFile2 = array_values($indexed2);

        return [
            'matches' => count($matches),
            'differences' => $differences,
            'only_in_file1' => $onlyInFile1,
            'only_in_file2' => $onlyInFile2,
        ];
    }

    protected function loadFile(string $filePath, string $keyColumn): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (in_array(strtolower($extension), ['csv'])) {
            return $this->loadCsv($filePath, $keyColumn);
        } elseif (in_array(strtolower($extension), ['xls', 'xlsx'])) {
            return $this->loadExcel($filePath, $keyColumn);
        }

        throw new \Exception("Unsupported file format.");
    }

    protected function loadCsv(string $filePath, string $keyColumn): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!in_array($keyColumn, $headers)) {
                throw new \Exception("Key column '$keyColumn' not found in $filePath");
            }
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    protected function loadExcel(string $filePath, string $keyColumn): array
    {
        $array = Excel::toArray([], $filePath)[0];

        if (empty($array)) {
            throw new \Exception("Empty Excel file.");
        }

        $headers = array_shift($array);
        if (!in_array($keyColumn, $headers)) {
            throw new \Exception("Key column '$keyColumn' not found in Excel file.");
        }

        return array_map(fn ($row) => array_combine($headers, $row), $array);
    }
}