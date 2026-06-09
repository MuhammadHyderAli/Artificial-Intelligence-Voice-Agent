<?php

namespace App\Ai\Tools;

use App\Services\TicketService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class UpdateTicketStatus implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'update_ticket_status(int $ticketId, string $newStatus)';
    }

    public function description(): Stringable|string
    {
        return 'Update the status of an existing support ticket. Valid statuses: open, in_progress, resolved, closed.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticketId' => $schema->string()->description('The ID of the support ticket.'),
            'newStatus' => $schema->string()
                ->enum(['open', 'in_progress', 'resolved', 'closed'])
                ->description('The new status to apply to the ticket.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot update ticket: No verified customer profile found.";
        }

        $ticketId = (int) $request['ticketId'];
        $newStatus = $request['newStatus'];

        $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses)) {
            return "Invalid status. Must be one of: " . implode(', ', $validStatuses);
        }

        $success = app(TicketService::class)->updateTicketStatus($this->customerId, $ticketId, $newStatus);
        
        return $success 
            ? "Successfully updated ticket #{$ticketId} to {$newStatus}." 
            : "Failed to update ticket: Ticket #{$ticketId} not found or doesn't belong to this account.";
    }
}
