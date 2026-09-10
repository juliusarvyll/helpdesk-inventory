# Inventory System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a user maintain a shared, department-scoped inventory (assets and stock items) and link one or more of those items — with an optional per-link quantity — to a support ticket.

**Architecture:** Two new tables (`service_desk_inventory_items`, and pivot `service_desk_ticket_inventory_items`) plus a new nullable `users.department_id` column. A new `InventoryItem` model is scoped per-department; visibility and edit rights are enforced purely through the Filament resource's `getEloquentQuery()` (this app's existing convention — no Laravel Policy classes exist anywhere in it). Ticket linking uses a `TicketInventoryItem` pivot model with a `HasMany` relation so a Filament `Repeater` can use `->relationship()` to auto-sync the pivot rows (including the per-line `quantity`), following Filament's documented pattern for `BelongsToMany` + repeater.

**Tech Stack:** Laravel 12 / PHP 8.5, Filament 5.8, Pest 5.

**Spec:** `docs/superpowers/specs/2026-09-10-inventory-system-design.md`

## Global Constraints

- Always use curly braces for control structures, even single-line.
- Explicit return types and parameter type hints on every method.
- Table names are prefixed `service_desk_` (matches every sibling migration).
- Models use `HasFactory`; models representing "content" (not pivots) use `SoftDeletes`, matching `Department`, `Category`, `Ticket`.
- No `app/Policies` classes exist in this app — access control for Filament resources is done exclusively via `getEloquentQuery()` scoping (see `TicketResource::getEloquentQuery()`). Follow this convention; do not introduce a Policy class.
- Translation keys live in two namespaces already wired up in this app: `service-desk::service-desk.*` (`lang/service-desk/{en,pt_BR}/service-desk.php` — enum labels, misc nouns) and `filament-service-desk::service-desk.*` (`lang/filament-service-desk/{en,pt_BR}/service-desk.php` — resource/field/section labels). Add new keys to **both** `en` and `pt_BR` files.
- `app/Providers/Filament/AppPanelProvider.php` has no `discoverResources()` call — every App-panel resource is registered explicitly and conditionally in `App\Filament\App\ServiceDeskPlugin::register()`, driven by `config('filament-service-desk.user.resources')`. A new App-panel resource must be added there, never by adding a `discoverResources()` call (see `.ai/rules/filament.md`).
- After any PHP file change, run `vendor/bin/pint --dirty --format agent`.
- Tests: Pest, `it()` style (matches `tests/Feature/ServiceDesk/TicketLifecycleTest.php`), placed under `tests/Feature/ServiceDesk/`.

---

### Task 1: `users.department_id` + Department ↔ User relationship

**Files:**
- Create: `database/migrations/2026_09_10_000001_add_department_id_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Department.php`
- Modify: `app/Filament/Admin/Resources/Users/Schemas/UserForm.php`
- Test: `tests/Feature/ServiceDesk/UserDepartmentTest.php`

**Interfaces:**
- Produces: `User::department(): BelongsTo`, `User->department_id: int|null`, `Department::users(): HasMany`. Later tasks (2, 4) rely on `$user->department_id` to scope inventory.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/UserDepartmentTest.php`:

```php
<?php

use App\Models\Department;
use App\Models\User;

it('assigns a user to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    expect($user->department->is($department))->toBeTrue();
    expect($department->users->pluck('id'))->toContain($user->id);
});

it('allows a user to have no department', function () {
    $user = User::factory()->create();

    expect($user->department_id)->toBeNull();
    expect($user->department)->toBeNull();
});

it('nulls the user department when the department is force-deleted', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $department->forceDelete();

    expect($user->refresh()->department_id)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserDepartmentTest --compact`
Expected: FAIL — `department_id` is not a fillable/known column on `users` yet (SQL error or mass-assignment silently dropped, so `department()`/`users()` are undefined method errors).

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('id')
                ->constrained('service_desk_departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
```

- [ ] **Step 4: Add the relationship and fillable to `User`**

In `app/Models/User.php`, add to the imports (after the existing `use Illuminate\Notifications\Notifiable;` line):

```php
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Add `'department_id'` to `$fillable`:

```php
    protected $fillable = [
        'department_id',
        'status',
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields',
        'locale',
        'theme_color',
    ];
```

Add to the class docblock (after `@property bool $status`):

```php
 * @property int|null $department_id
```

and after the existing `@property-read int|null $notifications_count` line:

```php
 * @property-read Department|null $department
 * @property-read Collection<int, InventoryItem> $inventoryItems
```

Add the relationship methods at the end of the class, just before the closing `}` of `casts()`'s surrounding class body (i.e. after the `casts()` method):

```php
    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'created_by_id');
    }
```

`Department` and `InventoryItem` are both in `App\Models`, same namespace as `User` — no import needed. (`InventoryItem` doesn't exist yet; it's created in Task 2. This is fine — PHP resolves the class name at call time, not at parse time, so `User.php` will still be syntactically valid and Task 1's own test doesn't touch `inventoryItems()`.)

- [ ] **Step 5: Add the inverse relationship to `Department`**

In `app/Models/Department.php`, add after the `tickets()` method:

```php
    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }
```

Add to the class docblock, after `@property-read Collection<int, Ticket> $tickets`:

```php
 * @property-read Collection<int, User> $users
```

- [ ] **Step 6: Let admins assign a user's department**

In `app/Filament/Admin/Resources/Users/Schemas/UserForm.php`, add the import:

```php
use Filament\Forms\Components\Select;
```

Add a field to the `Section`'s `schema` array, after the `Toggle::make('status')` field:

```php
                        Select::make('department_id')
                            ->label(__('filament-service-desk::service-desk.fields.department'))
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=UserDepartmentTest --compact`
Expected: PASS

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_09_10_000001_add_department_id_to_users_table.php app/Models/User.php app/Models/Department.php app/Filament/Admin/Resources/Users/Schemas/UserForm.php tests/Feature/ServiceDesk/UserDepartmentTest.php
git commit -m "Add users.department_id and Department<->User relationship"
```

---

### Task 2: `InventoryItem` model

**Files:**
- Create: `database/migrations/2026_09_10_000002_create_service_desk_inventory_items_table.php`
- Create: `app/Enums/InventoryItemType.php`
- Create: `app/Models/InventoryItem.php`
- Create: `database/factories/InventoryItemFactory.php`
- Modify: `app/Models/Department.php`
- Modify: `lang/service-desk/en/service-desk.php`
- Modify: `lang/service-desk/pt_BR/service-desk.php`
- Test: `tests/Feature/ServiceDesk/InventoryItemTest.php`

**Interfaces:**
- Consumes: `Department` (Task 1), `User` (Task 1, via `created_by_id`).
- Produces: `InventoryItem` model with `type: InventoryItemType`, `department(): BelongsTo`, `createdBy(): BelongsTo`, `InventoryItemFactory` with `asset()`/`stock()` states. `Department::inventoryItems(): HasMany`. Task 3 adds `InventoryItem::tickets()`; Task 4 builds the Filament resource on top of this model.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/InventoryItemTest.php`:

```php
<?php

use App\Enums\InventoryItemType;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;

it('creates an asset-type inventory item scoped to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'created_by_id' => $user->id,
        'name' => 'Dell Laptop',
        'serial_number' => 'SN-12345',
    ]);

    expect($item->type)->toBe(InventoryItemType::Asset);
    expect($item->department->is($department))->toBeTrue();
    expect($item->createdBy->is($user))->toBeTrue();
    expect($department->inventoryItems->pluck('id'))->toContain($item->id);
});

it('creates a stock-type inventory item with quantity and unit', function () {
    $item = InventoryItem::factory()->stock()->create([
        'name' => 'Printer Paper',
        'quantity' => 50,
        'unit' => 'reams',
    ]);

    expect($item->type)->toBe(InventoryItemType::Stock);
    expect($item->quantity)->toBe(50);
    expect($item->unit)->toBe('reams');
});

it('soft deletes an inventory item', function () {
    $item = InventoryItem::factory()->create();

    $item->delete();

    expect($item->fresh())->toBeNull();
    expect(InventoryItem::withTrashed()->find($item->id))->not->toBeNull();
});

it('cascades inventory items when their department is force-deleted', function () {
    $department = Department::factory()->create();
    $item = InventoryItem::factory()->create(['department_id' => $department->id]);

    $department->forceDelete();

    expect(InventoryItem::withTrashed()->find($item->id))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InventoryItemTest --compact`
Expected: FAIL — class `App\Models\InventoryItem` not found.

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_desk_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('service_desk_departments')->cascadeOnDelete();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_desk_inventory_items');
    }
};
```

- [ ] **Step 4: Create the `InventoryItemType` enum**

```php
<?php

namespace App\Enums;

enum InventoryItemType: string
{
    case Asset = 'asset';
    case Stock = 'stock';

    public function label(): string
    {
        return __('service-desk::service-desk.inventory_item_type.'.$this->value);
    }
}
```

- [ ] **Step 5: Add enum labels to the lang files**

In `lang/service-desk/en/service-desk.php`, add a new top-level key (after the `'comment_type'` block, before `'history_action'`, to sit alongside the other enum-label blocks):

```php
    /*
    |--------------------------------------------------------------------------
    | Inventory Item Types
    |--------------------------------------------------------------------------
    */
    'inventory_item_type' => [
        'asset' => 'Asset',
        'stock' => 'Stock',
    ],

```

In `lang/service-desk/pt_BR/service-desk.php`, add the same key in the same position:

```php
    /*
    |--------------------------------------------------------------------------
    | Inventory Item Types
    |--------------------------------------------------------------------------
    */
    'inventory_item_type' => [
        'asset' => 'Ativo',
        'stock' => 'Estoque',
    ],

```

- [ ] **Step 6: Create the `InventoryItem` model**

```php
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
}
```

Note: `tickets()` references the pivot table created in Task 3. Task 2's own test doesn't call `tickets()`, so this is safe to add now (matches the final target shape and avoids touching this file again in Task 3).

- [ ] **Step 7: Create the factory**

```php
<?php

namespace Database\Factories;

use App\Enums\InventoryItemType;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'created_by_id' => User::factory(),
            'type' => InventoryItemType::Asset,
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'quantity' => null,
            'unit' => null,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn () => [
            'type' => InventoryItemType::Asset,
            'serial_number' => fake()->unique()->bothify('SN-########'),
            'quantity' => null,
            'unit' => null,
        ]);
    }

    public function stock(): static
    {
        return $this->state(fn () => [
            'type' => InventoryItemType::Stock,
            'serial_number' => null,
            'quantity' => fake()->numberBetween(1, 100),
            'unit' => fake()->randomElement(['reams', 'boxes', 'units']),
        ]);
    }
}
```

- [ ] **Step 8: Add `Department::inventoryItems()`**

In `app/Models/Department.php`, add after the `users()` method added in Task 1:

```php
    /** @return HasMany<InventoryItem, $this> */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'department_id');
    }
```

Add `@property-read Collection<int, InventoryItem> $inventoryItems` to the docblock, after `@property-read Collection<int, User> $users`.

- [ ] **Step 9: Run test to verify it passes**

Run: `php artisan test --filter=InventoryItemTest --compact`
Expected: PASS

- [ ] **Step 10: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_09_10_000002_create_service_desk_inventory_items_table.php app/Enums/InventoryItemType.php app/Models/InventoryItem.php database/factories/InventoryItemFactory.php app/Models/Department.php lang/service-desk/en/service-desk.php lang/service-desk/pt_BR/service-desk.php tests/Feature/ServiceDesk/InventoryItemTest.php
git commit -m "Add InventoryItem model, type enum, and factory"
```

---

### Task 3: Ticket ↔ InventoryItem pivot

**Files:**
- Create: `database/migrations/2026_09_10_000003_create_service_desk_ticket_inventory_items_table.php`
- Create: `app/Models/TicketInventoryItem.php`
- Modify: `app/Models/Ticket.php`
- Test: `tests/Feature/ServiceDesk/TicketInventoryLinkTest.php`

**Interfaces:**
- Consumes: `Ticket` (existing), `InventoryItem` (Task 2).
- Produces: `Ticket::ticketInventoryItems(): HasMany` (the relation Task 5's Filament `Repeater::relationship()` binds to), `Ticket::inventoryItems(): BelongsToMany`, `Ticket::inventoryItemsIncludingTrashed(): BelongsToMany` (used by Task 6's infolist so a soft-deleted item still displays on a ticket that already linked it).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/TicketInventoryLinkTest.php`:

```php
<?php

use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;

it('links an inventory item to a ticket with a per-line quantity', function () {
    $department = Department::factory()->create();
    $item = InventoryItem::factory()->stock()->create(['department_id' => $department->id]);
    $ticket = Ticket::factory()->create(['department_id' => $department->id]);

    $ticket->ticketInventoryItems()->create([
        'inventory_item_id' => $item->id,
        'quantity' => 5,
    ]);

    $linked = $ticket->inventoryItems()->first();

    expect($ticket->inventoryItems)->toHaveCount(1);
    expect($linked->id)->toBe($item->id);
    expect($linked->pivot->quantity)->toBe(5);
    expect($item->tickets()->first()->id)->toBe($ticket->id);
});

it('still shows a soft-deleted inventory item on a ticket that already linked it', function () {
    $item = InventoryItem::factory()->create();
    $ticket = Ticket::factory()->create(['department_id' => $item->department_id]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    $item->delete();

    expect($ticket->inventoryItems()->count())->toBe(0);
    expect($ticket->inventoryItemsIncludingTrashed()->count())->toBe(1);
    expect($ticket->inventoryItemsIncludingTrashed()->first()->id)->toBe($item->id);
});

it('cascades pivot rows when the ticket is force-deleted', function () {
    $item = InventoryItem::factory()->create();
    $ticket = Ticket::factory()->create(['department_id' => $item->department_id]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    $ticket->forceDelete();

    expect(\App\Models\TicketInventoryItem::where('inventory_item_id', $item->id)->count())->toBe(0);
    expect($item->fresh())->not->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TicketInventoryLinkTest --compact`
Expected: FAIL — `ticketInventoryItems()`/`inventoryItems()`/`inventoryItemsIncludingTrashed()` undefined on `Ticket`, and `service_desk_ticket_inventory_items` table doesn't exist.

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_desk_ticket_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_desk_tickets')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('service_desk_inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'inventory_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_desk_ticket_inventory_items');
    }
};
```

- [ ] **Step 4: Create the `TicketInventoryItem` pivot model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $inventory_item_id
 * @property int|null $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ticket $ticket
 * @property-read InventoryItem $inventoryItem
 */
class TicketInventoryItem extends Pivot
{
    public $incrementing = true;

    protected $table = 'service_desk_ticket_inventory_items';

    protected $fillable = [
        'ticket_id',
        'inventory_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return BelongsTo<InventoryItem, $this> */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
```

`$incrementing = true` is required so Filament's `Repeater::relationship()` (used in Task 5) can track which repeater rows are new vs. existing, per Filament's documented `BelongsToMany` + repeater pattern.

- [ ] **Step 5: Add relationships to `Ticket`**

In `app/Models/Ticket.php`, add after the `serviceRequest()` method (before the `scopeByStatus` scope):

```php
    /** @return HasMany<TicketInventoryItem, $this> */
    public function ticketInventoryItems(): HasMany
    {
        return $this->hasMany(TicketInventoryItem::class, 'ticket_id');
    }

    /** @return BelongsToMany<InventoryItem, $this> */
    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'service_desk_ticket_inventory_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    /** @return BelongsToMany<InventoryItem, $this> */
    public function inventoryItemsIncludingTrashed(): BelongsToMany
    {
        return $this->inventoryItems()->withTrashed();
    }
```

Add to the class docblock, after `@property-read ServiceRequest|null $serviceRequest`:

```php
 * @property-read Collection<int, TicketInventoryItem> $ticketInventoryItems
 * @property-read Collection<int, InventoryItem> $inventoryItems
```

No new `use` imports are needed: `HasMany`, `BelongsToMany`, and `Collection` are already imported in `Ticket.php`.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=TicketInventoryLinkTest --compact`
Expected: PASS

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_09_10_000003_create_service_desk_ticket_inventory_items_table.php app/Models/TicketInventoryItem.php app/Models/Ticket.php tests/Feature/ServiceDesk/TicketInventoryLinkTest.php
git commit -m "Add Ticket<->InventoryItem pivot relationship"
```

---

### Task 4: App panel `InventoryItems` Filament resource

**Files:**
- Create: `app/Filament/App/Resources/InventoryItems/InventoryItemResource.php`
- Create: `app/Filament/App/Resources/InventoryItems/Schemas/InventoryItemForm.php`
- Create: `app/Filament/App/Resources/InventoryItems/Tables/InventoryItemsTable.php`
- Create: `app/Filament/App/Resources/InventoryItems/Pages/ListInventoryItems.php`
- Create: `app/Filament/App/Resources/InventoryItems/Pages/CreateInventoryItem.php`
- Create: `app/Filament/App/Resources/InventoryItems/Pages/EditInventoryItem.php`
- Modify: `app/Filament/App/ServiceDeskPlugin.php`
- Modify: `config/filament-service-desk.php`
- Modify: `lang/filament-service-desk/en/service-desk.php`
- Modify: `lang/filament-service-desk/pt_BR/service-desk.php`
- Test: `tests/Feature/ServiceDesk/InventoryItemVisibilityTest.php`

**Interfaces:**
- Consumes: `InventoryItem` (Task 2), `InventoryItemType` (Task 2), `User::$department_id` (Task 1).
- Produces: the App-panel `/app/inventory-items` CRUD UI. Task 5's ticket repeater selects from the same `InventoryItem` records this resource creates (no direct code dependency, but they share the department-scoping convention).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/InventoryItemVisibilityTest.php`:

```php
<?php

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\User;

it('only includes inventory items from the current user\'s department', function () {
    $departmentA = Department::factory()->create();
    $departmentB = Department::factory()->create();
    $userA = User::factory()->create(['department_id' => $departmentA->id]);

    $ownItem = InventoryItem::factory()->create(['department_id' => $departmentA->id]);
    $otherItem = InventoryItem::factory()->create(['department_id' => $departmentB->id]);

    $this->actingAs($userA);

    $ids = InventoryItemResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($ownItem->id);
    expect($ids)->not->toContain($otherItem->id);
});

it('includes an inventory item created by a different user in the same department', function () {
    $department = Department::factory()->create();
    $creator = User::factory()->create(['department_id' => $department->id]);
    $viewer = User::factory()->create(['department_id' => $department->id]);

    $item = InventoryItem::factory()->create([
        'department_id' => $department->id,
        'created_by_id' => $creator->id,
    ]);

    $this->actingAs($viewer);

    expect(InventoryItemResource::getEloquentQuery()->pluck('id'))->toContain($item->id);
});

it('excludes all inventory items for a user with no department', function () {
    $department = Department::factory()->create();
    InventoryItem::factory()->create(['department_id' => $department->id]);
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(InventoryItemResource::getEloquentQuery()->count())->toBe(0);
});

it('does not allow creating inventory when the user has no department', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(InventoryItemResource::canCreate())->toBeFalse();
});

it('allows creating inventory when the user has a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    $this->actingAs($user);

    expect(InventoryItemResource::canCreate())->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InventoryItemVisibilityTest --compact`
Expected: FAIL — class `App\Filament\App\Resources\InventoryItems\InventoryItemResource` not found.

- [ ] **Step 3: Create the form schema**

`app/Filament/App/Resources/InventoryItems/Schemas/InventoryItemForm.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems\Schemas;

use App\Enums\InventoryItemType;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Forms\Components\Select::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->options(collect(InventoryItemType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()]))
                    ->required()
                    ->live()
                    ->native(false),
                Forms\Components\TextInput::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('filament-service-desk::service-desk.fields.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('serial_number')
                    ->label(__('filament-service-desk::service-desk.fields.serial_number'))
                    ->maxLength(255)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Asset->value),
                Forms\Components\TextInput::make('quantity')
                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Stock->value),
                Forms\Components\TextInput::make('unit')
                    ->label(__('filament-service-desk::service-desk.fields.unit'))
                    ->maxLength(255)
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('type') === InventoryItemType::Stock->value),
            ]);
    }
}
```

- [ ] **Step 4: Create the table**

`app/Filament/App/Resources/InventoryItems/Tables/InventoryItemsTable.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems\Tables;

use App\Enums\InventoryItemType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-service-desk::service-desk.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (InventoryItemType $state) => $state->label())
                    ->color(fn (InventoryItemType $state) => match ($state) {
                        InventoryItemType::Asset => 'info',
                        InventoryItemType::Stock => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label(__('filament-service-desk::service-desk.fields.serial_number'))
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                    ->placeholder('—')
                    ->formatStateUsing(fn ($state, $record) => $record->type === InventoryItemType::Stock
                        ? trim(($state ?? '—').' '.($record->unit ?? ''))
                        : '—'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label(__('filament-service-desk::service-desk.fields.created_by'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-service-desk::service-desk.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('filament-service-desk::service-desk.fields.type'))
                    ->options(collect(InventoryItemType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading(fn () => auth()->guard()->user()?->department_id
                ? __('filament-service-desk::service-desk.messages.no_inventory_items')
                : __('filament-service-desk::service-desk.messages.no_department_assigned'));
    }
}
```

- [ ] **Step 5: Create the pages**

`app/Filament/App/Resources/InventoryItems/Pages/ListInventoryItems.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

`app/Filament/App/Resources/InventoryItems/Pages/CreateInventoryItem.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        $data['department_id'] = $user->department_id;
        $data['created_by_id'] = $user->getKey();

        return $data;
    }
}
```

`app/Filament/App/Resources/InventoryItems/Pages/EditInventoryItem.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems\Pages;

use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryItem extends EditRecord
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
```

- [ ] **Step 6: Create the resource**

`app/Filament/App/Resources/InventoryItems/InventoryItemResource.php`:

```php
<?php

namespace App\Filament\App\Resources\InventoryItems;

use App\Filament\App\Resources\InventoryItems\Schemas\InventoryItemForm;
use App\Filament\App\Resources\InventoryItems\Tables\InventoryItemsTable;
use App\Models\InventoryItem;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.user.group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.inventory_item.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.inventory_item.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        return parent::getEloquentQuery()
            ->where('department_id', $user->department_id);
    }

    public static function canCreate(): bool
    {
        /** @var User $user */
        $user = auth()->guard()->user();

        return $user->department_id !== null;
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.user.resources.inventory_item') !== null;
    }
}
```

Note: `where('department_id', $user->department_id)` when `$user->department_id` is `null` compiles to `WHERE department_id = NULL`, which SQL never matches — this is what makes "no department → empty inventory" work without a special case.

- [ ] **Step 7: Register the resource in `ServiceDeskPlugin`**

In `app/Filament/App/ServiceDeskPlugin.php`, change:

```php
        $enabled = [
            $resources['ticket'] ?? Resources\Tickets\TicketResource::class,
        ];
```

to:

```php
        $enabled = [
            $resources['ticket'] ?? Resources\Tickets\TicketResource::class,
            $resources['inventory_item'] ?? Resources\InventoryItems\InventoryItemResource::class,
        ];
```

- [ ] **Step 8: Add the config override entry**

In `config/filament-service-desk.php`, add the import at the top (alongside the other `use App\Filament\App\Resources\...` line):

```php
use App\Filament\App\Resources\InventoryItems\InventoryItemResource;
```

In the `'user' => ['resources' => [...]]` array, add:

```php
            'inventory_item' => InventoryItemResource::class,
```

so it reads:

```php
        'resources' => [
            'ticket' => App\Filament\App\Resources\Tickets\TicketResource::class,
            'service_request' => ServiceRequestResource::class,
            'inventory_item' => InventoryItemResource::class,
        ],
```

- [ ] **Step 9: Add lang keys**

In `lang/filament-service-desk/en/service-desk.php`, add to `'resources'`:

```php
        'inventory_item' => ['label' => 'Inventory Item', 'plural_label' => 'Inventory Items'],
```

Add to `'fields'`:

```php
        'serial_number' => 'Serial Number',
        'quantity' => 'Quantity',
        'unit' => 'Unit',
        'created_by' => 'Created By',
```

Add a new top-level `'messages'` entries (the `messages` key already exists with `'auto_generated'`; add alongside it):

```php
    'messages' => [
        'auto_generated' => 'Auto-generated on save',
        'no_inventory_items' => 'No inventory items yet.',
        'no_department_assigned' => 'You are not assigned to a department yet. Contact an administrator to get started.',
    ],
```

In `lang/filament-service-desk/pt_BR/service-desk.php`, make the equivalent additions:

```php
        'inventory_item' => ['label' => 'Item de Inventário', 'plural_label' => 'Itens de Inventário'],
```

```php
        'serial_number' => 'Número de Série',
        'quantity' => 'Quantidade',
        'unit' => 'Unidade',
        'created_by' => 'Criado Por',
```

```php
    'messages' => [
        'auto_generated' => 'Gerado automaticamente ao salvar',
        'no_inventory_items' => 'Nenhum item de inventário ainda.',
        'no_department_assigned' => 'Você ainda não está atribuído a um departamento. Contate um administrador para começar.',
    ],
```

(`'type'`, `'name'`, `'description'`, `'created_at'` already exist in both files — reused as-is.)

- [ ] **Step 10: Run test to verify it passes**

Run: `php artisan test --filter=InventoryItemVisibilityTest --compact`
Expected: PASS

- [ ] **Step 11: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/App/Resources/InventoryItems app/Filament/App/ServiceDeskPlugin.php config/filament-service-desk.php lang/filament-service-desk tests/Feature/ServiceDesk/InventoryItemVisibilityTest.php
git commit -m "Add App panel InventoryItems Filament resource"
```

---

### Task 5: Link inventory items when creating a ticket

**Files:**
- Modify: `app/Filament/App/Resources/Tickets/Schemas/TicketForm.php`
- Modify: `lang/filament-service-desk/en/service-desk.php`
- Modify: `lang/filament-service-desk/pt_BR/service-desk.php`
- Test: `tests/Feature/ServiceDesk/TicketInventoryFormTest.php`

**Interfaces:**
- Consumes: `Ticket::ticketInventoryItems()` (Task 3), `InventoryItem` (Task 2).
- Produces: no new interfaces — this wires the existing pieces into the ticket-creation UI.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/TicketInventoryFormTest.php`:

```php
<?php

use App\Filament\App\Resources\Tickets\Pages\CreateTicket;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

it('links selected inventory items to the created ticket with quantity', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->stock()->create(['department_id' => $department->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Need more paper',
            'description' => 'Running low on printer paper.',
            'ticketInventoryItems' => [
                ['inventory_item_id' => $item->id, 'quantity' => 3],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->latest('id')->first();

    expect($ticket->ticketInventoryItems)->toHaveCount(1);
    expect($ticket->ticketInventoryItems->first()->inventory_item_id)->toBe($item->id);
    expect($ticket->ticketInventoryItems->first()->quantity)->toBe(3);
});

it('creates a ticket with no inventory items linked', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);

    Livewire::actingAs($user)
        ->test(CreateTicket::class)
        ->fillForm([
            'department_id' => $department->id,
            'title' => 'Cannot log in',
            'description' => 'Password reset email never arrives.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->latest('id')->first();

    expect($ticket->ticketInventoryItems)->toHaveCount(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TicketInventoryFormTest --compact`
Expected: FAIL — `ticketInventoryItems` is not a known form field on `TicketForm` (validation/state error), so no pivot row is created.

- [ ] **Step 3: Add the repeater to `TicketForm`**

In `app/Filament/App/Resources/Tickets/Schemas/TicketForm.php`, add the repeater field to the `Section`'s `schema` array, after the `priority` field (last field in the array):

```php
                        Forms\Components\Repeater::make('ticketInventoryItems')
                            ->relationship()
                            ->label(__('filament-service-desk::service-desk.fields.inventory_items'))
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->label(__('filament-service-desk::service-desk.fields.inventory_item'))
                                    ->relationship(
                                        'inventoryItem',
                                        'name',
                                        fn ($query, Schemas\Components\Utilities\Get $get) => $query
                                            ->where('department_id', $get('../../department_id'))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->columns(2)
                            ->addActionLabel(__('filament-service-desk::service-desk.actions.add_inventory_item'))
                            ->visible(fn (Schemas\Components\Utilities\Get $get) => filled($get('department_id')))
                            ->columnSpanFull(),
```

Add the `filled()` import at the top of the file (after the `use` block, before the class declaration):

```php
use function filled;
```

- [ ] **Step 4: Add lang keys**

In `lang/filament-service-desk/en/service-desk.php`, add to `'fields'`:

```php
        'inventory_items' => 'Inventory Items',
        'inventory_item' => 'Inventory Item',
```

Add to `'actions'`:

```php
        'add_inventory_item' => 'Add Item',
```

In `lang/filament-service-desk/pt_BR/service-desk.php`, add the equivalent:

```php
        'inventory_items' => 'Itens de Inventário',
        'inventory_item' => 'Item de Inventário',
```

```php
        'add_inventory_item' => 'Adicionar Item',
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=TicketInventoryFormTest --compact`
Expected: PASS

If the `../../department_id` relative path doesn't resolve as expected (the first test fails with an empty options list / validation error on `inventory_item_id`), check the actual nesting: the repeater sits directly in the same `schema` array as `department_id` (both children of the outer `Section`), so from inside a repeater item, `../../department_id` should reach it — one `../` exits the item to the repeater's own level, a second exits the repeater to the `Section`'s level. If it's still wrong, try `../department_id` and re-run.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/App/Resources/Tickets/Schemas/TicketForm.php lang/filament-service-desk tests/Feature/ServiceDesk/TicketInventoryFormTest.php
git commit -m "Let users link inventory items to a ticket on creation"
```

---

### Task 6: Show linked inventory items on the ticket view (App, Agent, Admin)

**Files:**
- Modify: `app/Filament/App/Resources/Tickets/Schemas/TicketInfolist.php`
- Modify: `app/Filament/Agent/Resources/Tickets/Schemas/TicketInfolist.php`
- Modify: `app/Filament/Admin/Resources/Tickets/Schemas/TicketInfolist.php`
- Modify: `lang/filament-service-desk/en/service-desk.php`
- Modify: `lang/filament-service-desk/pt_BR/service-desk.php`
- Test: `tests/Feature/ServiceDesk/TicketInventoryInfolistTest.php`

**Interfaces:**
- Consumes: `Ticket::inventoryItemsIncludingTrashed()` (Task 3).
- Produces: none — this is the final, display-only task.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ServiceDesk/TicketInventoryInfolistTest.php`:

```php
<?php

use App\Filament\App\Resources\Tickets\Pages\ViewTicket;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Livewire;

it('shows linked inventory items on the ticket view page', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'name' => 'Dell Laptop',
    ]);
    $ticket = Ticket::factory()->create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);

    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Dell Laptop');
});

it('still shows a soft-deleted linked inventory item on the ticket view page', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create(['department_id' => $department->id]);
    $item = InventoryItem::factory()->asset()->create([
        'department_id' => $department->id,
        'name' => 'Retired Printer',
    ]);
    $ticket = Ticket::factory()->create([
        'department_id' => $department->id,
        'user_type' => $user->getMorphClass(),
        'user_id' => $user->id,
    ]);
    $ticket->ticketInventoryItems()->create(['inventory_item_id' => $item->id]);
    $item->delete();

    Livewire::actingAs($user)
        ->test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Retired Printer');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=TicketInventoryInfolistTest --compact`
Expected: FAIL — "Dell Laptop" / "Retired Printer" not present in the rendered page.

- [ ] **Step 3: Add the section to the App `TicketInfolist`**

In `app/Filament/App/Resources/Tickets/Schemas/TicketInfolist.php`, add after the existing `Section::make(...sections.ticket_details...)` block (as a new sibling `Section` inside the outer `->schema([...])` array):

```php
                Schemas\Components\Section::make(__('filament-service-desk::service-desk.sections.linked_inventory_items'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('inventoryItemsIncludingTrashed')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('filament-service-desk::service-desk.fields.name')),
                                Infolists\Components\TextEntry::make('type')
                                    ->label(__('filament-service-desk::service-desk.fields.type'))
                                    ->badge()
                                    ->formatStateUsing(fn (\App\Enums\InventoryItemType $state) => $state->label()),
                                Infolists\Components\TextEntry::make('pivot.quantity')
                                    ->label(__('filament-service-desk::service-desk.fields.quantity'))
                                    ->placeholder('—'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn ($record) => $record->inventoryItemsIncludingTrashed()->exists()),
```

- [ ] **Step 4: Add the same section to the Agent `TicketInfolist`**

In `app/Filament/Agent/Resources/Tickets/Schemas/TicketInfolist.php`, add the identical block (shown in Step 3) as a new `Section` inside the `Schemas\Components\Section::make(__('filament-service-desk::service-desk.sections.info'))`'s parent schema array — i.e. as a sibling section alongside the `sections.info` section, inside the same `Group`.

- [ ] **Step 5: Add the same section to the Admin `TicketInfolist`**

In `app/Filament/Admin/Resources/Tickets/Schemas/TicketInfolist.php`, add the identical block as a new sibling `Section` inside the second `Schemas\Components\Group::make()->schema([...])` block (alongside `sections.ticket_metadata`, `sections.reference`, `sections.sla`).

- [ ] **Step 6: Add the lang key**

In `lang/filament-service-desk/en/service-desk.php`, add to `'sections'`:

```php
        'linked_inventory_items' => 'Linked Inventory Items',
```

In `lang/filament-service-desk/pt_BR/service-desk.php`, add:

```php
        'linked_inventory_items' => 'Itens de Inventário Vinculados',
```

- [ ] **Step 7: Run test to verify it passes**

Run: `php artisan test --filter=TicketInventoryInfolistTest --compact`
Expected: PASS

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/App/Resources/Tickets/Schemas/TicketInfolist.php app/Filament/Agent/Resources/Tickets/Schemas/TicketInfolist.php app/Filament/Admin/Resources/Tickets/Schemas/TicketInfolist.php lang/filament-service-desk tests/Feature/ServiceDesk/TicketInventoryInfolistTest.php
git commit -m "Show linked inventory items on ticket view pages"
```

---

## After All Tasks

Ask the user to run the full suite: `php artisan test --compact`.
