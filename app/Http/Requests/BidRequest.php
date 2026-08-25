<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BidRequest extends FormRequest
{
    /**
     * Shape only. Whether the bid is actually acceptable is decided inside the
     * locked transaction in BiddingService, where it cannot race.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ];
    }
}
