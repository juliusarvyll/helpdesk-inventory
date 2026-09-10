# Inventory System Design

**Date:** 2026-09-10
**Status:** Approved for implementation planning

## Summary

Add a department-scoped inventory system to the service desk. Users can
record inventory items (physical assets or stock/consumable items) under
their department, shared with everyone else in that department, and
reference one or more of those items when creating a support ticket.

## Motivation

Today a ticket only carries free-text title/description. Users who want to
report an issue with a specific asset ("this laptop") or request more of a
consumable ("5 reams of paper") have no structured way to say which item
they mean. This adds a lightweight inventory users maintain themselves and
a way to attach items to tickets.

## Data Model

### `users.department_id` (new column)

- Nullable `foreignId` → `service_desk_departments`, `nullOnDelete`.
- Gives each user a home department. Previously a user had no fixed
  department; department was chosen per-ticket only. That per-ticket
  choice is unchanged — this column is additive and drives inventory
  scoping specifically.
- Editable via the existing Admin `Users` Filament resource (add a
  `department_id` select field).
- A user with no `department_id` sees an empty inventory list with a
  notice to contact an admin. This is an accepted edge case, not an error
  state.

### `InventoryItem` model / `service_desk_inventory_items` table

```
id
department_id      foreignId -> service_desk_departments, cascadeOnDelete
created_by_id       foreignId -> users, cascadeOnDelete
type                string (InventoryItemType enum: asset|stock)
name                string
description         text, nullable
serial_number       string, nullable   -- meaningful for type=asset
quantity            integer, nullable  -- meaningful for type=stock
unit                string, nullable   -- meaningful for type=stock, e.g. "reams"
timestamps
softDeletes
```

- `App\Enums\InventoryItemType`: `Asset`, `Stock` — string-backed, with a
  `label()` method, following the existing `TicketPriority` enum pattern
  (`filament-service-desk::` / `service-desk::` translation namespace).
- Soft deletes so a ticket that already links an item keeps showing it
  even after the item is retired/removed from active inventory.
- `created_by_id` is an audit field only — it does **not** restrict who
  can edit or delete the item. Any user in the same department can edit
  or delete any item belonging to that department (per design decision:
  fully shared within department).

### Pivot `service_desk_ticket_inventory_items`

```
id
ticket_id           foreignId -> service_desk_tickets, cascadeOnDelete
inventory_item_id   foreignId -> service_desk_inventory_items, cascadeOnDelete
quantity            integer, nullable
timestamps

unique(ticket_id, inventory_item_id)
```

- `quantity` here is the amount relevant to *this ticket* (e.g. "requesting
  5 units"), independent of the item's own on-hand `quantity`. It's
  free-entry with no validation against stock on hand or decrement logic —
  building reservation/stock-management workflows is out of scope.
- Shown for both item types but only practically meaningful for `Stock`.

## Relationships

- `Department::inventoryItems()` — `hasMany(InventoryItem::class)`
- `Department::users()` — `hasMany(User::class)`
- `User::department()` — `belongsTo(Department::class)`
- `User::inventoryItems()` — `hasMany(InventoryItem::class, 'created_by_id')`
- `InventoryItem::department()` — `belongsTo(Department::class)`
- `InventoryItem::createdBy()` — `belongsTo(User::class, 'created_by_id')`
- `InventoryItem::tickets()` — `belongsToMany(Ticket::class, 'service_desk_ticket_inventory_items')->withPivot('quantity')->withTimestamps()`
- `Ticket::inventoryItems()` — inverse of the above

## Business Rules

1. **Visibility (shared within department):** a user sees and manages all
   `InventoryItem` rows where `department_id === $user->department_id`.
   This is a shared pool, not owner-restricted — any member of the
   department can view, edit, or delete any item belonging to it.
2. **Ticket linkage filtering:** when creating/editing a ticket, only
   inventory items belonging to the ticket's currently-selected
   `department_id` are selectable, mirroring the existing `category_id`
   filtering behavior in `TicketForm` (live-updated when department
   changes).
3. **Multiple items per ticket:** a ticket may reference zero or more
   inventory items via the pivot table (not limited to one).
4. **No department, no inventory:** a user with `department_id === null`
   cannot create inventory items and sees an empty list with guidance to
   contact an admin, rather than an error.

## Filament UI

### App panel — new `InventoryItems` resource

`app/Filament/App/Resources/InventoryItems/` following the existing
`Tickets` resource structure (`Schemas/`, `Tables/`, `Pages/`):

- **List/Table:** scoped query `InventoryItem::where('department_id', auth()->user()->department_id)`. Columns: name, type (badge), serial number
  or quantity+unit depending on type, created by, created at.
- **Form:** `type` select (live) toggles field visibility —
  `serial_number` for `Asset`, `quantity` + `unit` for `Stock`.
  `department_id` is set automatically from the authenticated user, not
  user-selectable (since visibility is already tied to `users.department_id`).
- **Edit/Delete:** available to any user whose `department_id` matches the
  item's `department_id` (enforced via the resource's Eloquent query scope
  and a policy, not just hiding UI).

### Ticket form (App panel) — `TicketForm`

Add an "Inventory Items" repeater:

- Each row: an item picker (`Select`, relationship-backed, filtered by the
  form's currently selected `department_id`, same `live()` pattern as
  `category_id`) + an optional `quantity` input.
- Persisted via `mutateFormDataBeforeCreate` / `afterSave` on the Ticket
  resource's Create/Edit pages, syncing the pivot table with per-row
  quantity — the plain Filament `relationship()` field doesn't cleanly
  carry extra pivot columns through a repeater, so this is handled
  explicitly in the page class.

### Ticket infolist (App, and read-only in Agent/Admin)

Add a "Linked Inventory Items" section to `TicketInfolist` (and the
corresponding Agent/Admin ticket infolists) listing name, type badge, and
quantity for each linked item. Read-only — no separate Admin/Agent
inventory management resource in this iteration (App panel owns
inventory CRUD).

## Error Handling / Edge Cases

- Deleting a `Department` cascades to its `InventoryItem`s (matches
  `Category`'s existing cascade behavior).
- Deleting an `InventoryItem` soft-deletes it; existing ticket links are
  preserved and still displayed (query linked items `withTrashed()` on the
  ticket infolist specifically, not in the general inventory list/picker).
- Deleting a `Ticket` cascades to its pivot rows (the linked items
  themselves are untouched).
- A user with no `department_id` gets an empty, non-error inventory state.

## Testing Plan

Pest feature tests:

- Department-scoped visibility: a user in Department A cannot see or edit
  an `InventoryItem` belonging to Department B.
- Any user in the same department can edit/delete another user's item
  (confirms shared, non-owner-restricted editing).
- CRUD via the App `InventoryItems` resource for both `Asset` and `Stock`
  types (field visibility toggling, required fields per type).
- Creating a ticket with one or more linked inventory items persists the
  correct pivot rows including `quantity`.
- Linked items still display on a ticket after the item is soft-deleted.
- Model relationship tests: `Department::inventoryItems`,
  `User::inventoryItems`, `Ticket::inventoryItems`.

## Out of Scope

- Stock reservation/decrement logic (pivot `quantity` is informational
  only).
- Admin/Agent-facing inventory management resource (App panel only for
  this iteration).
- Approval workflows for inventory requests (unlike `ServiceRequest`,
  inventory items don't go through an approval step).
- Cross-department inventory sharing or transfers.
