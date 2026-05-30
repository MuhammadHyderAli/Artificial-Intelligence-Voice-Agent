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

        $orders = Order::where('customer_id', $customerId)->get();

        return $orders->first(function ($order) use ($cleanInput) {
            $cleanOrderNumber = preg_replace('/[^A-Z0-9]/', '', strtoupper($order->order_number));

            if ($cleanOrderNumber === $cleanInput) {
                return true;
            }

            if (str_starts_with($cleanOrderNumber, 'ORD')) {
                $withoutOrd = substr($cleanOrderNumber, 3);
                if ($withoutOrd === $cleanInput) {
                    return true;
                }
            }

            return false;
        });
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
        if ($order) {
            return $order->update(['status' => $newStatus]);
        }
        return false;
    }

    public function deleteOrder(int $customerId, string $orderNumber): bool
    {
        $order = $this->getOrderByNumber($customerId, $orderNumber);
        return $order ? $order->delete() : false;
    }
}