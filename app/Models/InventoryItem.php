<?php

namespace App\Models;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $department_id
 * @property int|null $created_by_id
 * @property string|null $asset_tag
 * @property InventoryItemType $type
 * @property string|null $category
 * @property string $name
 * @property string|null $description
 * @property InventoryItemStatus $status
 * @property string|null $serial_number
 * @property int|null $quantity
 * @property string|null $unit
 * @property string|null $location
 * @property Carbon|null $purchased_at
 * @property Carbon|null $warranty_expires_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Department $department
 * @property-read User $createdBy
 * @property-read Collection<int, Ticket> $tickets
 */
class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'service_desk_inventory_items';

    /** @return Factory<self> */
    protected static function newFactory(): Factory
    {
        return InventoryItemFactory::new();
    }

    protected $fillable = [
        'department_id',
        'created_by_id',
        'asset_tag',
        'type',
        'category',
        'name',
        'description',
        'status',
        'serial_number',
        'quantity',
        'unit',
        'location',
        'purchased_at',
        'warranty_expires_at',
        'metadata',
    ];

    protected $casts = [
        'type' => InventoryItemType::class,
        'status' => InventoryItemStatus::class,
        'quantity' => 'integer',
        'purchased_at' => 'date',
        'warranty_expires_at' => 'date',
        'metadata' => 'array',
    ];

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** @return BelongsToMany<Ticket, $this> */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'service_desk_ticket_inventory_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
