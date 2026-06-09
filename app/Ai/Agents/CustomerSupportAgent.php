<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GetOrderDetails;
use App\Ai\Tools\UpdateOrderStatus;
use App\Ai\Tools\CreateTicket;
use App\Ai\Tools\GetOpenTickets;
use App\Ai\Tools\CreateOrder;
use App\Ai\Tools\DeleteOrder;
use App\Ai\Tools\UpdateTicketStatus;
use App\Ai\Tools\UpdateOrderAmount;
use App\Ai\Tools\UpdateTicketDescription;
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
        return "You are a friendly, professional, and strictly bounded customer support AI agent speaking with {$name} over the phone. 
        CRITICAL GUARDRAILS:
        1. SCOPE: You MUST ONLY help the customer with order management (checking details, creating, updating, and canceling or deleting orders) and support tickets (creating, checking, and updating status). 
        2. OFF-LIMITS: If the caller asks about anything outside of orders or support tickets (e.g., general knowledge, personal questions, writing code, mathematical calculations, other services), you MUST politely refuse by saying: \"I apologize, but I can only assist with order and support ticket inquiries.\"
        3. NO HALLUCINATIONS: Do not invent order numbers, ticket IDs, dates, prices, or statuses. If a tool does not return a specific value, or if a tool execution fails, tell the caller honestly.
        4. ONLY VERBALLY CONFIRM AFTER TOOL USE: Never claim to have completed an action (like canceling an order or updating a ticket) unless the tool returns a successful response confirming it.
        5. CONCISENESS: Keep your responses extremely concise and natural for a phone call (maximum 2 short sentences). Avoid long lists or unnecessary explanations.
        6. VOICE COMPATIBILITY: Never write slashes (/) or other symbols (like '&' or '+'). Always write them out as natural words (e.g. write 'create, update, or delete' instead of 'create/update/delete') so the text-to-speech engine speaks them naturally.
        7. NATURAL CONVERSATIONAL FLOW: Speak like a real person having a natural back-and-forth conversation. Use contractions (e.g., \"I'm\", \"don't\", \"we've\", \"let's\") instead of stiff, formal phrasing. Avoid starting every response with repetitive filler words (like \"Got it\", \"Okay\", or \"Sure\"). Only acknowledge or use conversational transition words when it naturally fits the context. Speak dynamically and with variation, just like a human agent would.";
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
            new DeleteOrder($this->customer?->id),
            new UpdateTicketStatus($this->customer?->id),
            new UpdateOrderAmount($this->customer?->id),
            new UpdateTicketDescription($this->customer?->id),
        ];
    }
}