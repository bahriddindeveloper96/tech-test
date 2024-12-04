<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'uz');
        $translation = $this->translations->where('locale', $locale)->first();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user' => new UserResource($this->whenLoaded('user')),
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'delivery_method' => new DeliveryMethodResource($this->whenLoaded('deliveryMethod')),
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'shipping_address' => $this->shipping_address,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'translations' => [
                'status_message' => $translation?->status_message,
                'delivery_notes' => $translation?->delivery_notes,
            ],
        ];
    }
}
