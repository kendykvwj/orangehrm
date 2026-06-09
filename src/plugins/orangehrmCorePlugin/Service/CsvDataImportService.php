<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Core\Service;

use OrangeHRM\Core\Exception\CSVUploadFailedException;
use OrangeHRM\Core\Import\CsvDataImport;
use OrangeHRM\Core\Import\CsvDataImportFactory;
use OrangeHRM\Core\Traits\LoggerTrait;
use Throwable;

class CsvDataImportService
{
    use LoggerTrait;

    /**
     * Control/whitespace characters normalised to a single space inside each CSV cell.
     */
    private const CONTROL_CHARS = ["\n", "\r", "\t", "\v", "\x00"];
    private const CONTROL_CHARS_PATTERN = '/[\n\r\t\v\x00]/';

    /**
     * @param string $fileContent
     * @param string $importType
     * @param array $headerValues
     * @return array
     * @throws CSVUploadFailedException
     */
    public function import(string $fileContent, string $importType, array $headerValues): array
    {
        $rows = $this->getEmployeeArrayFromCSV($fileContent, $headerValues);

        // The first row must match the expected header; otherwise there is nothing to import.
        if (empty($rows) || $rows[0] !== $headerValues) {
            return $this->buildResult(0, []);
        }

        $importer = (new CsvDataImportFactory())->getImportClassInstance($importType);
        return $this->importRows($importer, array_slice($rows, 1));
    }

    /**
     * @param CsvDataImport $importer
     * @param array $dataRows data rows with the header row already removed
     * @return array
     */
    private function importRows(CsvDataImport $importer, array $dataRows): array
    {
        $rowsImported = 0;
        $failedRows = [];
        foreach ($dataRows as $index => $row) {
            if ($this->importRow($importer, $row)) {
                $rowsImported++;
            } else {
                // +2: account for the stripped header row and convert the 0-based index to a 1-based line number.
                $failedRows[] = $index + 2;
            }
        }
        return $this->buildResult($rowsImported, $failedRows);
    }

    /**
     * @param CsvDataImport $importer
     * @param array $row
     * @return bool
     */
    private function importRow(CsvDataImport $importer, array $row): bool
    {
        try {
            return $importer->import($row);
        } catch (Throwable $e) {
            $this->getLogger()->error($e->getMessage());
            $this->getLogger()->error($e->getTraceAsString());
            return false;
        }
    }

    /**
     * @param int $imported
     * @param array $failedRows
     * @return array
     */
    private function buildResult(int $imported, array $failedRows): array
    {
        return ['success' => $imported, 'failed' => count($failedRows), 'failedRows' => $failedRows];
    }

    /**
     * Returns a multidimensional array where one array matches a row of the CSV
     * @param string $fileContent
     * @param array $headerValues
     * @return array
     * @throws CSVUploadFailedException
     */
    public function getEmployeeArrayFromCSV(string $fileContent, array $headerValues): array
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $fileContent);
        rewind($stream);

        $rows = [];
        $expectedColumnCount = count($headerValues);
        try {
            // length 0 = no line-length limit, so cells longer than 1000 bytes are not truncated.
            while (($data = fgetcsv($stream, 0, ",")) !== false) {
                // Each data row must have the same number of elements as the header.
                if (count($data) !== $expectedColumnCount) {
                    throw CSVUploadFailedException::validationFailed();
                }
                $rows[] = $this->normalizeRow($data);
            }
        } finally {
            fclose($stream);
        }
        return $rows;
    }

    /**
     * Collapses embedded control/whitespace characters in each cell to a single space and trims it.
     * @param array $row
     * @return array
     */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (preg_match(self::CONTROL_CHARS_PATTERN, $value)) {
                $row[$key] = trim(str_replace(self::CONTROL_CHARS, ' ', $value));
            }
        }
        return $row;
    }
}
