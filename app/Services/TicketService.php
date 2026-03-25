<?php

namespace App\Services;

use App\Models\Ticket;

class TicketService
{
    public function createTicket(int $customerId, string $issueType, string $description): Ticket
    {
        return Ticket::create([
            'customer_id' => $customerId,
            'issue_type' => $issueType,
            'description' => $description,
            'status' => 'open',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Ticket>
     */
    public function getOpenTickets(int $customerId)
    {
        return Ticket::where('customer_id', $customerId)
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getTicketById(int $customerId, int $ticketId): ?Ticket
    {
        return Ticket::where('customer_id', $customerId)
            ->where('id', $ticketId)
            ->first();
    }

    public function updateTicketStatus(int $customerId, int $ticketId, string $newStatus): bool
    {
        $ticket = $this->getTicketById($customerId, $ticketId);
        if ($ticket) {
            return $ticket->update(['status' => $newStatus]);
        }
        return false;
    }
}