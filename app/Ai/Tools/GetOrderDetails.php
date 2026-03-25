<?php

namespace App\Ai\Tools;

use App\Services\OrderService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class GetOrderDetails implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'get_order_details(string $orderNumber)';
    }

    public function description(): Stringable|string
    {
        return 'Get full details of a specific order by its order number.';
    }

    public function schema(JsonSchema $schema): array 
    {
        return [
            'orderNumber' => $schema->string()->description('The order number to look up.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot fetch order: No verified customer profile found.";
        }

        $orderNumber = $request['orderNumber'];

        $order = app(OrderService::class)->getOrderByNumber($this->customerId, $orderNumber);
        
        return $order 
            ? "Order {$order->order_number} is currently {$order->status}. Total amount is {$order->amount}. Delivery date is {$order->delivery_date->format('Y-m-d')}." 
            : "I could not find order number {$orderNumber}.";
    }
}