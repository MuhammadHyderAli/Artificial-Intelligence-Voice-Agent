<?php

namespace App\Ai\Tools;

use App\Services\TicketService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTicket implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'create_support_ticket(string $issueType, string $description)';
    }

    public function description(): Stringable|string
    {
        return 'Create a new support ticket for the customer. Use this when the customer reports a problem, bug, or complaint. issueType should be a short category (e.g., "billing", "shipping", "technical").';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'issueType' => $schema->string()->description('Short category of the issue (e.g., billing, shipping, technical).'),
            'description' => $schema->string()->description('Detailed description of the problem.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot create ticket: No verified customer profile found. Ask the user to log in or verify their phone number.";
        }

        $issueType = $request['issueType'];
        $description = $request['description'];

        $ticket = app(TicketService::class)->createTicket($this->customerId, $issueType, $description);
        
        return "Successfully created ticket #{$ticket->id} for issue: {$issueType}. Let the customer know our team will review it shortly.";
    }
}