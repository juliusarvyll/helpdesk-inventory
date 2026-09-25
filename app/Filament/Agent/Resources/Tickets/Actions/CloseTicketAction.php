<?php

namespace App\Filament\Agent\Resources\Tickets\Actions;

use App\Models\Category;
use App\Models\Ticket;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Support\Icons\Heroicon;

/**
 * Closes a ticket after the agent confirms the issue subcategory it falls under.
 * Agents can create a missing subcategory from the popup; requesters never see this action.
 */
class CloseTicketAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'close';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-service-desk::service-desk.actions.close'))
            ->icon(Heroicon::XCircle)
            ->color('danger')
            ->modalHeading(__('filament-service-desk::service-desk.actions.close'))
            ->modalDescription(__('filament-service-desk::service-desk.messages.close_ticket_subcategory'))
            ->schema([
                Forms\Components\Select::make('subcategory_id')
                    ->label(__('filament-service-desk::service-desk.fields.subcategory'))
                    ->options(fn (): array => self::getSubcategoryOptions())
                    ->default(fn (Ticket $record): ?int => $record->subcategory_id)
                    ->required()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\Select::make('parent_id')
                            ->label(__('filament-service-desk::service-desk.fields.parent_category'))
                            ->options(fn (): array => Category::query()->root()->active()->ordered()->pluck('name', 'id')->all())
                            ->default(fn (Ticket $record): ?int => $record->category_id)
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-service-desk::service-desk.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->unique(Category::class, 'name', ignoreRecord: false),
                    ])
                    ->createOptionUsing(fn (array $data): int => Category::create([
                        'parent_id' => $data['parent_id'],
                        'name' => $data['name'],
                        'is_active' => true,
                    ])->getKey()),
            ])
            ->action(fn (Ticket $record, array $data): Ticket => app(TicketService::class)->close(
                $record,
                auth()->guard()->user(),
                Category::query()->whereNotNull('parent_id')->findOrFail($data['subcategory_id']),
            ))
            ->visible(fn (Ticket $record): bool => $record->isOpen());
    }

    /**
     * Active subcategories grouped under their parent category's name.
     *
     * @return array<string, array<int, string>>
     */
    private static function getSubcategoryOptions(): array
    {
        return Category::query()
            ->root()
            ->active()
            ->ordered()
            ->with(['children' => fn ($query) => $query->active()->ordered()])
            ->get()
            ->filter(fn (Category $parent): bool => $parent->children->isNotEmpty())
            ->mapWithKeys(fn (Category $parent): array => [
                $parent->name => $parent->children->pluck('name', 'id')->all(),
            ])
            ->all();
    }
}
