<?php

namespace App\Ai\Tools;

use App\Services\OrderService;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Stringable;

class UpdateOrderStatus implements Tool
{
    public function __construct(protected ?int $customerId) {}

    public function signature(): string
    {
        return 'update_order_status(string $orderNumber, string $newStatus)';
    }

    public function description(): Stringable|string
    {
        return 'Update the status of an existing order. Valid statuses: pending, processing, shipped, delivered, cancelled.';
    }

    public function handle(Request $request): Stringable|string
    {
        if (!$this->customerId) {
            return "Cannot update order: No verified customer profile found.";
        }

        $orderNumber = $request['orderNumber'];
        $newStatus = $request['newStatus'];

        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return "Invalid status. Must be one of: " . implode(', ', $validStatuses);
        }

        $success = app(OrderService::class)->updateOrderStatus($this->customerId, $orderNumber, $newStatus);
        
        return $success 
            ? "I have successfully updated order {$orderNumber} to {$newStatus}." 
            : "Failed to update: Order {$orderNumber} not found.";
    }
    public function schema(JsonSchema $schema): array
    {
        return [
            'orderNumber' => $schema->string()->description('The order number.'),
            'newStatus' => $schema->string()
                ->enum(['pending', 'processing', 'shipped', 'delivered', 'cancelled'])
                ->description('The new status to apply.'),
        ];
    }
}