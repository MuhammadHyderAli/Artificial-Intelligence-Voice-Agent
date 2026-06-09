<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderService
{
    public function getLatestOrder(int $customerId): ?Order
    {
        return Order::where('customer_id', $customerId)
            ->latest()
            ->first();
    }

    public function getOrderByNumber(int $customerId, string $orderNumber): ?Order
    {
        $cleanInput = preg_replace('/[^A-Z0-9]/', '', strtoupper($orderNumber));
        return Order::where('customer_id', $customerId)
            ->where(function ($query) use ($cleanInput) {
                $query->whereRaw("REPLACE(REPLACE(UPPER(order_number), '-', ''), '_', '') = ?", [$cleanInput]);
                if (!str_starts_with($cleanInput, 'ORD')) {
                    $query->orWhereRaw("REPLACE(REPLACE(UPPER(order_number), '-', ''), '_', '') = ?", ['ORD' . $cleanInput]);
                }
            })
            ->first();
    }

    public function createOrder(int $customerId, float $amount): Order
    {
        return Order::create([
            'customer_id' => $customerId,
            'order_number' => 'ORD-' . strtoupper(Str::random(6)),
            'status' => 'pending',
            'amount' => $amount,
            'delivery_date' => now()->addDays(5),
        ]);
    }

    public function updateOrderStatus(int $customerId, string $orderNumber, string $newStatus): bool
    {
        $order = $this->getOrderByNumber($customerId, $orderNumber);
        return $order ? $order->update(['status' => $newStatus]) : false;
    }

    public function deleteOrder(int $customerId, string $orderNumber): bool
    {
        $order = $this->getOrderByNumber($customerId, $orderNumber);
        return $order ? $order->delete() : false;
    }
}