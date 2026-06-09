<?php

namespace App\Ai\Tools;

use App\Services\OrderService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteOrder implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'delete_order(string $orderNumber)';
    }

    public function description(): Stringable|string
    {
        return 'Cancel or delete an existing order. Use this when a customer wants to cancel an order.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'orderNumber' => $schema->string()->description('The unique order number to delete.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot delete order: No verified customer profile found.";
        }
        
        $orderNumber = $request['orderNumber'];
        $success = app(OrderService::class)->deleteOrder($this->customerId, $orderNumber);
        
        return $success
            ? "Successfully canceled and deleted order {$orderNumber}."
            : "Could not find an active order with the number {$orderNumber} associated with your account.";
    }
}