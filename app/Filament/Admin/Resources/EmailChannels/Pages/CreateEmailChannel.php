<?php

namespace App\Filament\Admin\Resources\EmailChannels\Pages;

use App\Filament\Admin\Resources\EmailChannels\EmailChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailChannel extends CreateRecord
{
    protected static string $resource = EmailChannelResource::class;
}
