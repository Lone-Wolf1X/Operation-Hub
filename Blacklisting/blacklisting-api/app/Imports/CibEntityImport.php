<?php

namespace App\Imports;

use App\Models\CibEntity;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CibEntityImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new CibEntity([
            'name'        => $row['name'] ?? $row['entity_name'] ?? 'Unknown',
            'entity_type' => $row['type'] ?? $row['entity_type'] ?? 'Individual',
            'status'      => $row['status'] ?? 'Blacklisted',
            'cib_date'    => isset($row['date']) ? date('Y-m-d', strtotime($row['date'])) : null,
        ]);
    }
}
