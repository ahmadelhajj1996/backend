<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Transaction */
class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            // 'type'        => $this->type->value,
            'type_label'  => $this->type->label(),
            'amount'      => $this->amount,      
            'currency'    => $this->currency,
            'description' => $this->description,
            'notes'       => $this->notes,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at->toIso8601String(),
        ];
    }
}