<?php

namespace App\Support;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Ticket createTicket(array $data, \Illuminate\Database\Eloquent\Model $user)
 * @method static \App\Models\Ticket updateTicket(\App\Models\Ticket $ticket, array $data, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \App\Models\Ticket changeStatus(\App\Models\Ticket $ticket, \App\Enums\TicketStatus $status, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \App\Models\Ticket assignTicket(\App\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $operator, ?\Illuminate\Database\Eloquent\Model $assignedBy = null)
 * @method static \App\Models\Ticket closeTicket(\App\Models\Ticket $ticket, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \App\Models\Ticket reopenTicket(\App\Models\Ticket $ticket, ?\Illuminate\Database\Eloquent\Model $performer = null)
 * @method static \App\Models\Ticket findTicketByUuid(string $uuid)
 * @method static \App\Models\Ticket findTicketByReference(string $reference)
 * @method static \App\Models\TicketComment addComment(\App\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $author, string $body, array $options = [])
 * @method static \App\Models\TicketComment addNote(\App\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $author, string $body, array $options = [])
 * @method static \App\Models\Department createDepartment(array $data)
 * @method static \App\Services\TicketService tickets()
 * @method static \App\Services\CommentService comments()
 * @method static \App\Services\DepartmentService departments()
 * @method static \App\Services\AttachmentService attachments()
 *
 * @see ServiceDeskManager
 */
class ServiceDesk extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceDeskManager::class;
    }
}
