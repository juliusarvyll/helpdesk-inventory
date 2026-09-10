<?php

namespace App\Filament\Admin\Resources\EmailChannels;

use App\Filament\Admin\Resources\EmailChannels\Schemas\EmailChannelForm;
use App\Filament\Admin\Resources\EmailChannels\Tables\EmailChannelsTable;
use App\Models\EmailChannel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmailChannelResource extends Resource
{
    protected static ?string $model = EmailChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('filament-service-desk::service-desk.navigation.admin.group');
    }

    public static function getModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.email_channel.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-service-desk::service-desk.resources.email_channel.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return EmailChannelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailChannelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailChannels::route('/'),
            'create' => Pages\CreateEmailChannel::route('/create'),
            'edit' => Pages\EditEmailChannel::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('filament-service-desk.admin.resources.email_channel') !== null;
    }
}
