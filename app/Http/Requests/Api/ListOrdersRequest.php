<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in([
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])],
            'payment_status' => ['nullable', Rule::in([
                Order::PAYMENT_PENDING,
                Order::PAYMENT_PAID,
                Order::PAYMENT_FAILED,
                Order::PAYMENT_REFUNDED,
            ])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $defaultPerPage = (int) config('orders.pagination.default_per_page', 20);
        $maxPerPage = (int) config('orders.pagination.max_per_page', 100);
        $perPage = (int) $this->input('per_page', $defaultPerPage);

        if ($perPage < 1) {
            $perPage = $defaultPerPage;
        }

        $this->merge([
            'per_page' => min($perPage, $maxPerPage),
        ]);
    }
}
