<?php

namespace App\Ai\Tools;

use App\Services\OrderService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class UpdateOrderAmount implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'update_order_amount(string $orderNumber, float $newAmount)';
    }

    public function description(): Stringable|string
    {
        return 'Update the total amount or price of an existing order.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot update order amount: No verified customer profile found.";
        }

        $orderNumber = $request['orderNumber'];
        $newAmount = (float) $request['newAmount'];

        $success = app(OrderService::class)->updateOrderAmount($this->customerId, $orderNumber, $newAmount);
        
        return $success 
            ? "Successfully updated order {$orderNumber} total amount to {$newAmount}." 
            : "Failed to update amount: Order {$orderNumber} not found.";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'orderNumber' => $schema->string()->description('The order number to update.'),
            'newAmount' => $schema->number()->description('The new total amount or price for the order.'),
        ];
    }
}
