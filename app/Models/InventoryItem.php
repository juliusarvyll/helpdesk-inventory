<?php

namespace App\Models;

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
 * @property int $created_by_id
 * @property InventoryItemType $type
 * @property string $name
 * @property string|null $description
 * @property string|null $serial_number
 * @property int|null $quantity
 * @property string|null $unit
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
        'type',
        'name',
        'description',
        'serial_number',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'type' => InventoryItemType::class,
        'quantity' => 'integer',
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

    /** @return static|null */
    public function fresh($with = [])
    {
        if (! $this->exists) {
            return;
        }

        $instance = $this->setKeysForSaveQuery($this->newModelQuery())
            ->first();

        // If soft-deleted, return null (apply soft delete scope)
        if ($instance && $instance->trashed()) {
            return null;
        }

        return $instance ?: null;
    }
}
