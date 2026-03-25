<?php

namespace App\Ai\Tools;

use App\Services\TicketService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class GetOpenTickets implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'get_open_tickets()';
    }

    public function description(): Stringable|string
    {
        return 'Check if the customer has any open or in-progress support tickets.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot fetch tickets: No verified customer profile found.";
        }

        $tickets = app(TicketService::class)->getOpenTickets($this->customerId);
        
        if ($tickets->isEmpty()) {
            return "The customer currently has no open support tickets.";
        }

        $ticketList = $tickets->map(function ($ticket) {
            return "Ticket #{$ticket->id} ({$ticket->issue_type}): Status is {$ticket->status}.";
        })->join(' | ');

        return "Found open tickets: " . $ticketList;
    }
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}