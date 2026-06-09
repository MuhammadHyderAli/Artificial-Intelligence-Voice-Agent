<?php

namespace App\Ai\Tools;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Illuminate\Support\Str;

class CreateOrder implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'create_order(float $amount)';
    }

    public function description(): Stringable|string
    {
        return 'Create a new order for the customer. Use this when the customer wants to place a new order. You must ask the caller for the total amount or price of the order before calling this tool.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()->description('The total price or amount for the new order.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot create order: No verified customer profile found.";
        }

        $amount = $request['amount'];

        $orderNumber = 'ORD-' . strtoupper(Str::random(2)) . '-' . rand(1, 9);
        $order = Order::create([
            'customer_id' => $this->customerId,
            'order_number' => $orderNumber,
            'status' => 'pending',
            'amount' => $amount,
            'delivery_date' => now()->addDays(5),
        ]);
        
        return $order 
            ? "Successfully created a new order! The order number is {$order->order_number} for a total of {$amount}. The status is currently pending." 
            : "I'm sorry, I failed to create the order due to a system error.";
    }
}