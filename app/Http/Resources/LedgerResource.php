<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'Date' => $this->date,
            'Description' => $this->person,
            'Amount' => $this->amount,
            'type' => $this->transaction_type,
            'ledger' => $this->ledgerType,
            'status' => $this->payment->payment_status ?? 'Pending',
            'amount_paid' => $this->payment->amount_paid ?? 0,
            'reconciled' => $this->match !== null ? true : false,
            'reference' => $this->payment && $this->payment->reference ||
                $this->match && $this->match->statement->person
                || ""
        ];
    }
}
