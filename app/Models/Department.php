<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $email
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Ticket> $tickets
 * @property-read Collection<int, CannedResponse> $cannedResponses
 * @property-read Collection<int, EmailChannel> $emailChannels
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, InventoryItem> $inventoryItems
 */
class Department extends Model
{
    use HasFactory, HasSlug, SoftDeletes;

    protected $table = 'service_desk_departments';

    /** @return Factory<self> */
    protected static function newFactory(): Factory
    {
        return DepartmentFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'email',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'department_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'department_id');
    }

    /** @return HasMany<CannedResponse, $this> */
    public function cannedResponses(): HasMany
    {
        return $this->hasMany(CannedResponse::class, 'department_id');
    }

    /** @return HasMany<EmailChannel, $this> */
    public function emailChannels(): HasMany
    {
        return $this->hasMany(EmailChannel::class, 'department_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'department_id');
    }

    /** @param Builder<static> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<static> $query */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
