<?php

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Models\Department;
use App\Models\InventoryItem;

function writeInventoryCsvFixture(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'inventory_import_').'.csv';
    $handle = fopen($path, 'w');

    fputcsv($handle, ['inventory_category_id', 'category_name', 'category_type', 'parent_category_name', 'parent_category_type', 'asset_tag', 'name', 'description', 'serial_number', 'status', 'quantity', 'unit', 'location', 'assigned_to_user_id', 'metadata', 'purchased_at', 'warranty_expires_at']);

    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('imports inventory items from a csv file into the given department', function () {
    Department::factory()->create(['name' => 'IT Support']);

    $path = writeInventoryCsvFixture([
        ['', 'Mouse', 'asset', 'Computer Peripheral', 'asset_group', 'MOUSE-0001', 'Logitech M100', 'Wired mouse', 'SN-1', 'available', '', '', 'LR 101', '', '{"source_file":"a.docx"}', '2020-01-15', ''],
        ['', 'Keyboard', 'asset', 'Computer Peripheral', 'asset_group', 'KEYBOARD-0001', 'Logitech K120', 'Wired keyboard', 'SN-2', 'in_repair', '', '', 'LR 102', '', '', '', ''],
    ]);

    $this->artisan('service-desk:import-inventory-items', ['path' => $path, '--department' => 'IT Support'])
        ->assertSuccessful();

    expect(InventoryItem::count())->toBe(2);

    $mouse = InventoryItem::where('asset_tag', 'MOUSE-0001')->first();

    expect($mouse->name)->toBe('Logitech M100');
    expect($mouse->category)->toBe('Computer Peripheral > Mouse');
    expect($mouse->type)->toBe(InventoryItemType::Asset);
    expect($mouse->status)->toBe(InventoryItemStatus::Available);
    expect($mouse->location)->toBe('LR 101');
    expect($mouse->purchased_at->toDateString())->toBe('2020-01-15');
    expect($mouse->department->name)->toBe('IT Support');
    expect($mouse->metadata)->toBe(['source_file' => 'a.docx']);

    $keyboard = InventoryItem::where('asset_tag', 'KEYBOARD-0001')->first();
    expect($keyboard->status)->toBe(InventoryItemStatus::InRepair);
    expect($keyboard->purchased_at)->toBeNull();

    unlink($path);
});

it('upserts by asset tag instead of duplicating rows on re-import', function () {
    Department::factory()->create(['name' => 'IT Support']);

    $path = writeInventoryCsvFixture([
        ['', 'Mouse', 'asset', 'Computer Peripheral', 'asset_group', 'MOUSE-0001', 'Logitech M100', 'Wired mouse', 'SN-1', 'available', '', '', 'LR 101', '', '', '', ''],
    ]);

    $this->artisan('service-desk:import-inventory-items', ['path' => $path, '--department' => 'IT Support'])->assertSuccessful();
    $this->artisan('service-desk:import-inventory-items', ['path' => $path, '--department' => 'IT Support'])->assertSuccessful();

    expect(InventoryItem::count())->toBe(1);

    unlink($path);
});

it('fails when the target department does not exist', function () {
    $path = writeInventoryCsvFixture([
        ['', 'Mouse', 'asset', 'Computer Peripheral', 'asset_group', 'MOUSE-0001', 'Logitech M100', '', '', 'available', '', '', '', '', '', '', ''],
    ]);

    $this->artisan('service-desk:import-inventory-items', ['path' => $path, '--department' => 'Nonexistent'])
        ->assertFailed();

    unlink($path);
});
