<?php

namespace App\Imports;

use App\Models\CibBlacklist;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CibBlacklistImport implements ToCollection, WithHeadingRow
{
    protected $entityType;
    protected $batchId;

    public function __construct($entityType, $batchId)
    {
        $this->entityType = $entityType;
        $this->batchId = $batchId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue; // Skip invalid rows
            }

            $blacklistNumber = $row['blacklist_number'] ?? null;
            $blacklistDate = isset($row['blacklist_date']) ? \Carbon\Carbon::parse($row['blacklist_date'])->toDateString() : null;

            // Check duplicate/update logic
            $existing = CibBlacklist::where('entity_type', $this->entityType)
                ->where('blacklist_number', $blacklistNumber)
                ->first();

            if ($existing) {
                // If same date and number, just update batch_id to keep it alive
                if ($existing->blacklist_date?->toDateString() === $blacklistDate) {
                    $existing->update(['upload_batch_id' => $this->batchId]);
                    continue;
                }
                
                // If different, update the record
                $this->updateRecord($existing, $row, $blacklistDate);
            } else {
                // Create new
                $this->createRecord($row, $blacklistDate);
            }
        }
    }

    protected function createRecord($row, $blacklistDate)
    {
        $data = $this->mapRowToData($row, $blacklistDate);
        CibBlacklist::create($data);
    }

    protected function updateRecord($existing, $row, $blacklistDate)
    {
        $data = $this->mapRowToData($row, $blacklistDate);
        $existing->update($data);
    }

    protected function mapRowToData($row, $blacklistDate)
    {
        $data = [
            'entity_type' => $this->entityType,
            'name' => $row['name'] ?? null,
            'upload_batch_id' => $this->batchId,
            'blacklist_count' => $row['blacklist_count'] ?? null,
            'blacklist_number' => $row['blacklist_number'] ?? null,
            'blacklist_date' => $blacklistDate,
            'blacklist_type' => $row['blacklist_type'] ?? null,
            'blacklist_nature_of_relation' => $row['blacklist_nature_of_relation'] ?? null,
        ];

        if ($this->entityType === 'individual') {
            $data = array_merge($data, [
                'date_of_birth' => isset($row['date_of_birth']) ? \Carbon\Carbon::parse($row['date_of_birth'])->toDateString() : null,
                'gender' => $row['gender'] ?? null,
                'father_name' => $row['father_name'] ?? null,
                'citizenship_count' => $row['citizenship_count'] ?? null,
                'citizenship_number' => $row['citizenship_number'] ?? null,
                'ctz_issue_date' => isset($row['ctz_issue_date']) ? \Carbon\Carbon::parse($row['ctz_issue_date'])->toDateString() : null,
                'ctz_issue_district' => $row['ctz_issue_district'] ?? null,
                'blacklist_sector' => $row['blacklist_sector'] ?? null,
            ]);
        } else {
            $data = array_merge($data, [
                'pan_details' => $row['pan_details'] ?? null,
                'pan_count' => $row['pan_count'] ?? null,
                'pan' => $row['pan'] ?? null,
                'pan_issue_date' => isset($row['pan_issue_date']) ? \Carbon\Carbon::parse($row['pan_issue_date'])->toDateString() : null,
                'pan_issue_district' => $row['pan_issue_district'] ?? null,
                'company_count' => $row['company_count'] ?? null,
                'company_reg_number' => $row['company_reg_number'] ?? null,
                'reg_date' => isset($row['reg_date']) ? \Carbon\Carbon::parse($row['reg_date'])->toDateString() : null,
                'reg_authority' => $row['reg_authority'] ?? null,
            ]);
        }

        return $data;
    }
}
