<?php

namespace App\Filament\Agent\Resources\ServiceRequests;

use App\Enums\ServiceRequestStatus;
use App\Filament\Agent\Resources\ServiceRequests\Schemas\ServiceRequestInfolist;
use App\Filament\Agent\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.agent.group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.service_request.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.service_request.plural_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', ServiceRequestStatus::Pending->value)
            ->count();

        return $count ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var Model $user */
        $user = auth()->guard()->user();

        $departmentIds = $user->serviceDeskDepartments()->pluck('service_desk_departments.id');

        return parent::getEloquentQuery()
            ->whereHas('service', fn (Builder $query) => $query->whereIn('department_id', $departmentIds));
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceRequestInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(null)->schema([]);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequests::route('/'),
            'view' => Pages\ViewServiceRequest::route('/{record}'),
        ];
    }
}
