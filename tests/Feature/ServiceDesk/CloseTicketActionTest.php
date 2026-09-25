<?php

use App\Enums\TicketStatus;
use App\Filament\Agent\Resources\Tickets\Actions\CloseTicketAction;
use App\Filament\Agent\Resources\Tickets\Pages\ListTickets;
use App\Filament\Agent\Resources\Tickets\Pages\ViewTicket;
use App\Models\Category;
use App\Models\Operator;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel('agent');

    $this->agent = Operator::factory()->create();
    $this->hardware = Category::factory()->create(['name' => 'Hardware']);
    $this->laptops = Category::factory()->create(['parent_id' => $this->hardware->id, 'name' => 'Laptops']);
    $requester = User::factory()->create();
    $this->ticket = Ticket::factory()->create([
        'user_type' => $requester->getMorphClass(),
        'user_id' => $requester->id,
        'category_id' => $this->hardware->id,
        'assigned_to_type' => $this->agent->getMorphClass(),
        'assigned_to_id' => $this->agent->id,
    ]);
});

it('closes a ticket with the chosen issue subcategory', function () {
    Livewire::actingAs($this->agent, 'operator')
        ->test(ViewTicket::class, ['record' => $this->ticket->getRouteKey()])
        ->callAction(CloseTicketAction::class, ['subcategory_id' => $this->laptops->id])
        ->assertHasNoFormErrors();

    $this->ticket->refresh();

    expect($this->ticket->status)->toBe(TicketStatus::Closed)
        ->and($this->ticket->subcategory_id)->toBe($this->laptops->id)
        ->and($this->ticket->category_id)->toBe($this->hardware->id);
});

it('requires an issue subcategory to close a ticket from the tickets table', function () {
    Livewire::actingAs($this->agent, 'operator')
        ->test(ListTickets::class)
        ->callAction(TestAction::make('close')->table($this->ticket), ['subcategory_id' => null])
        ->assertHasFormErrors(['subcategory_id' => 'required']);

    expect($this->ticket->refresh()->status)->toBe(TicketStatus::Open);
});

it('aligns the ticket category with a subcategory chosen from another parent', function () {
    $software = Category::factory()->create(['name' => 'Software']);
    $email = Category::factory()->create(['parent_id' => $software->id, 'name' => 'Email']);

    Livewire::actingAs($this->agent, 'operator')
        ->test(ViewTicket::class, ['record' => $this->ticket->getRouteKey()])
        ->callAction(CloseTicketAction::class, ['subcategory_id' => $email->id]);

    expect($this->ticket->refresh()->category_id)->toBe($software->id);
});

it('lets an agent create a new subcategory from the close popup', function () {
    Livewire::actingAs($this->agent, 'operator')
        ->test(ViewTicket::class, ['record' => $this->ticket->getRouteKey()])
        ->mountAction(CloseTicketAction::class)
        ->callAction(TestAction::make('createOption')->schemaComponent('subcategory_id'), [
            'parent_id' => $this->hardware->id,
            'name' => 'Docking Stations',
        ])
        ->assertHasNoFormErrors();

    $created = Category::where('name', 'Docking Stations')->first();

    expect($created)->not->toBeNull()
        ->and($created->parent_id)->toBe($this->hardware->id)
        ->and($created->is_active)->toBeTrue();
});

it('does not offer the close action on a closed ticket', function () {
    $this->ticket->update(['status' => TicketStatus::Closed]);

    Livewire::actingAs($this->agent, 'operator')
        ->test(ViewTicket::class, ['record' => $this->ticket->getRouteKey()])
        ->assertActionHidden(CloseTicketAction::class);
});
