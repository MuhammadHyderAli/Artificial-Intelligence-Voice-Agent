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
        return 'get_open_tickets(string $issueType = null)';
    }

    public function description(): Stringable|string
    {
        return 'Check if the customer has any open or in-progress support tickets, optionally filtering by issue type.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot fetch tickets: No verified customer profile found.";
        }

        $issueType = $request['issueType'] ?? null;
        $tickets = app(TicketService::class)->getOpenTickets($this->customerId, $issueType);
        
        if ($tickets->isEmpty()) {
            $filterMsg = $issueType ? " for issue type '{$issueType}'" : "";
            return "The customer currently has no open support tickets{$filterMsg}.";
        }

        $ticketList = $tickets->map(function ($ticket) {
            return "Ticket #{$ticket->id} ({$ticket->issue_type}): Status is {$ticket->status}. Description: {$ticket->description}";
        })->join(' | ');

        return "Found open tickets: " . $ticketList;
    }
    public function schema(JsonSchema $schema): array
    {
        return [
            'issueType' => $schema->string()->description('Optional issue type category (e.g. billing, shipping, technical) to filter by.'),
        ];
    }
}