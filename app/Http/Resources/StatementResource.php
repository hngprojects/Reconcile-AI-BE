<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatementResource extends JsonResource
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
            'bank' => $this->statementFile->bankAccount->bank_name,
            'accountNumber' => $this->statementFile->bankAccount->account_number,
            'accountName' => $this->statementFile->bankAccount->account_name
        ];
    }
}
