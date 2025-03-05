<?php
namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ReconciliationService
{
    public function reconcileFiles(string $file1Path, string $file2Path)
    {
        $data1 = $this->loadFile($file1Path);
        $data2 = $this->loadFile($file2Path);

        $columns1 = $this->detectColumns($data1);
        $columns2 = $this->detectColumns($data2);

        $matches = [];
        $onlyInFile1 = [];
        $onlyInFile2 = [];

        foreach ($data1 as $row1) {
            $bestMatch = null;
            $highestScore = 0;

            $cleanedName1 = $this->extractNameFromDescription($row1[$columns1['name']]);
            $normalizedName1 = $this->normalizeName($cleanedName1);

            foreach ($data2 as $index => $row2) {
                $normalizedName2 = $this->normalizeName($row2[$columns2['name']]);
                $nameScore = $this->calculateNameSimilarity($normalizedName1, $normalizedName2);

                if ($nameScore > 70) {
                    if ($nameScore > $highestScore) {
                        $highestScore = $nameScore;
                        $bestMatch = $index;
                    }
                }
            }

            if ($bestMatch !== null) {
                $matches[] = [
                    'file1_transaction' => $row1,
                    'file2_transaction' => $data2[$bestMatch],
                    'match_score' => $highestScore
                ];
                unset($data2[$bestMatch]);
            } else {
                $onlyInFile1[] = $row1;
            }
        }

        $onlyInFile2 = array_values($data2);

        return [
            'matches' => $matches,
            'matches_count' => count($matches),
            'only_in_file1' => $onlyInFile1,
            'only_in_file2' => $onlyInFile2,
        ];
    }

    protected function detectColumns(array $data)
    {
        $headers = array_keys($data[0]);

        return [
            'name' => $this->findBestColumn($headers, ['name', 'full name', 'student name', 'description']),
            'amount' => $this->findBestColumn($headers, ['amount', 'transaction amount', 'total']),
            'date' => $this->findBestColumn($headers, ['date', 'transaction date', 'payment date'])
        ];
    }

    protected function findBestColumn(array $headers, array $expectedNames)
    {
        foreach ($expectedNames as $expected) {
            foreach ($headers as $header) {
                if (stripos($header, $expected) !== false) {
                    return $header;
                }
            }
        }
        return null;
    }

    protected function extractNameFromDescription(string $description): string
    {
        if (preg_match('/[A-Z][a-z]+\s[A-Z][a-z]+/', $description, $matches)) {
            return trim($matches[0]);
        }
        return $description;
    }

    protected function normalizeName(string $name): string
    {
        $name = strtolower(trim(str_replace(',', '', $name)));

        $parts = explode(' ', $name);
        if (count($parts) > 1) {
            return implode(' ', array_reverse($parts));
        }
        return $name;
    }



    protected function calculateNameSimilarity($name1, $name2)
    {
        $name1 = $this->normalizeName($name1);
        $name2 = $this->normalizeName($name2);

        if (str_contains($name1, $name2) || str_contains($name2, $name1)) {
            return 100;
        }

        if ($name1 === implode(' ', array_reverse(explode(' ', $name2)))) {
            return 95;
        }

        similar_text($name1, $name2, $percent);
        return ($percent >= 75) ? $percent : 0;
    }

    protected function amountsAreClose($amount1, $amount2, $tolerance = 500)
    {
        return abs($amount1 - $amount2) <= $tolerance;
    }

    protected function datesAreClose($date1, $date2, $tolerance = 2)
    {
        if (!$date1 || !$date2) {
            return false;
        }
        $date1 = strtotime($date1);
        $date2 = strtotime($date2);
        return abs($date1 - $date2) <= ($tolerance * 86400);
    }

    protected function loadFile(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($extension === 'csv') {
            return $this->loadCsv($filePath);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return $this->loadExcel($filePath);
        }

        throw new \Exception("Unsupported file format.");
    }

    protected function loadCsv(string $filePath): array
    {
        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    protected function loadExcel(string $filePath): array
    {
        $array = Excel::toArray([], $filePath)[0];
        if (empty($array)) {
            throw new \Exception("Empty Excel file.");
        }
        $headers = array_shift($array);
        return array_map(fn ($row) => array_combine($headers, $row), $array);
    }
}
