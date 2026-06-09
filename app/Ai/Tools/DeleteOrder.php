<?php

namespace App\Ai\Tools;

use App\Services\OrderService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteOrder implements Tool
{
    // Inject the OrderService alongside your optional customer ID
    public function __construct(
        protected ?int $customerId,
        protected OrderService $orderService
    ) {}

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
            'order_number' => $schema->string()->description('The unique order number to delete.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot delete order: No verified customer profile found.";
        }
        $success = $this->orderService->deleteOrder($this->customerId, $request['order_number']);
        return $success
            ? "Successfully canceled and deleted order {$request['order_number']}."
            : "Could not find an active order with the number {$request['order_number']} associated with your account.";
    }
}