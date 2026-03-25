<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetOrderDetails;
use App\Ai\Tools\UpdateOrderStatus;
use App\Ai\Tools\CreateTicket;
use App\Ai\Tools\GetOpenTickets;
use App\Ai\Tools\CreateOrder;
use App\Models\Customer;
use App\Models\ConversationMessage;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Messages\Message;


class CustomerSupportAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    public function __construct(
        public ?Customer $customer,
        public ?int $callLogId
    ) {}

    public function instructions(): string
    {
        $name = $this->customer ? $this->customer->name : 'Guest';
        return "You are a friendly, helpful customer support AI agent. You are speaking with {$name} over the phone. Be concise (max 2 sentences). Use your tools to look up/manage their orders or create/check support tickets when asked. Do not guess information; always use your tools.";
    }

    public function messages(): iterable
    {
        if (!$this->callLogId) {
            return [];
        }

        return ConversationMessage::where('call_log_id', $this->callLogId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($msg) {
                return new Message($msg->role, $msg->content);
            })
            ->all();
    }

    public function tools(): iterable
    {
        return [
            new GetOrderDetails($this->customer?->id),
            new UpdateOrderStatus($this->customer?->id),
            new CreateTicket($this->customer?->id),
            new GetOpenTickets($this->customer?->id),
            new CreateOrder($this->customer?->id),
        ];
    }
}