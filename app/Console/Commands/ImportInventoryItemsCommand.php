<?php

namespace App\Console\Commands;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Models\Department;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ImportInventoryItemsCommand extends Command
{
    protected $signature = 'service-desk:import-inventory-items
        {path : Path to the inventory CSV file}
        {--department=IT Support : Name of the department to assign imported items to}
        {--chunk=200 : Number of rows to upsert per batch}';

    protected $description = 'Bulk import inventory items from a CSV file, upserting by asset tag';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $department = Department::where('name', $this->option('department'))->first();

        if (! $department) {
            $this->error("Department not found: {$this->option('department')}");

            return self::FAILURE;
        }

        $reader = Reader::createFromPath($path, 'r');
        $reader->setHeaderOffset(0);

        $now = Carbon::now();
        $rows = [];
        $imported = 0;
        $chunkSize = (int) $this->option('chunk');

        foreach ($reader->getRecords() as $record) {
            $rows[] = $this->mapRow($record, $department->id, $now);

            if (count($rows) >= $chunkSize) {
                $this->upsert($rows);
                $imported += count($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $this->upsert($rows);
            $imported += count($rows);
        }

        $this->info("Imported {$imported} inventory item(s) into department [{$department->name}].");

        return self::SUCCESS;
    }

    /** @param array<string, string> $record */
    private function mapRow(array $record, int $departmentId, Carbon $now): array
    {
        $category = trim(($record['parent_category_name'] ?? '') !== ''
            ? "{$record['parent_category_name']} > {$record['category_name']}"
            : ($record['category_name'] ?? ''));

        $status = InventoryItemStatus::tryFrom($record['status'] ?? '') ?? InventoryItemStatus::Available;
        $type = InventoryItemType::tryFrom($record['category_type'] ?? '') ?? InventoryItemType::Asset;

        $metadata = null;
        if (! empty($record['metadata'])) {
            $decoded = json_decode($record['metadata'], true);
            $metadata = json_last_error() === JSON_ERROR_NONE ? json_encode($decoded) : null;
        }

        return [
            'department_id' => $departmentId,
            'created_by_id' => null,
            'asset_tag' => $record['asset_tag'] ?: null,
            'type' => $type->value,
            'category' => $category !== '' ? $category : null,
            'name' => $record['name'],
            'description' => $record['description'] ?: null,
            'status' => $status->value,
            'serial_number' => $record['serial_number'] ?: null,
            'quantity' => $record['quantity'] !== '' ? (int) $record['quantity'] : null,
            'unit' => $record['unit'] ?: null,
            'location' => $record['location'] ?: null,
            'purchased_at' => $this->parseDate($record['purchased_at'] ?? null),
            'warranty_expires_at' => $this->parseDate($record['warranty_expires_at'] ?? null),
            'metadata' => $metadata,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /** @param list<array<string, mixed>> $rows */
    private function upsert(array $rows): void
    {
        DB::table('service_desk_inventory_items')->upsert(
            $rows,
            ['asset_tag'],
            ['department_id', 'type', 'category', 'name', 'description', 'status', 'serial_number', 'quantity', 'unit', 'location', 'purchased_at', 'warranty_expires_at', 'metadata', 'updated_at']
        );
    }
}
