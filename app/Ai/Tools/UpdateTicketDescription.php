<?php

namespace App\Ai\Tools;

use App\Services\TicketService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class UpdateTicketDescription implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'update_ticket_description(string $issueType, string $newDescription)';
    }

    public function description(): Stringable|string
    {
        return 'Update the description or problem details of an active support ticket identified by its issue type (e.g., billing, shipping, technical).';
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot update ticket details: No verified customer profile found.";
        }

        $issueType = $request['issueType'];
        $newDescription = $request['newDescription'];

        $success = app(TicketService::class)->updateTicketDescriptionByIssueType($this->customerId, $issueType, $newDescription);
        
        return $success 
            ? "Successfully updated the description for your active '{$issueType}' support ticket." 
            : "Could not find an active '{$issueType}' support ticket associated with your account to update.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'issueType' => $schema->string()->description('The issue type category of the ticket to update (e.g. billing, shipping, technical).'),
            'newDescription' => $schema->string()->description('The new description or details of the problem.'),
        ];
    }
}
