<?php

namespace App\Filament\Admin\Resources\EscalationRules;

use App\Filament\Admin\Resources\EscalationRules\Schemas\EscalationRuleForm;
use App\Filament\Admin\Resources\EscalationRules\Tables\EscalationRulesTable;
use App\Models\EscalationRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EscalationRuleResource extends Resource
{
    protected static ?string $model = EscalationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.admin.sla_group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.escalation_rule.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.escalation_rule.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EscalationRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EscalationRulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEscalationRules::route('/'),
            'create' => Pages\CreateEscalationRule::route('/create'),
            'edit' => Pages\EditEscalationRule::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.admin.resources.escalation_rule') !== null;
    }
}
