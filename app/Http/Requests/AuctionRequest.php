<?php

namespace App\Http\Requests;

use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AuctionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AuctionTypeEnum::class)],
            'status' => ['required', Rule::enum(AuctionStatusEnum::class)],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => [
                'nullable',
                'required_if:type,'.AuctionTypeEnum::ONGOING->value,
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],
            'end_time' => [
                'nullable',
                'required_if:type,'.AuctionTypeEnum::ONGOING->value,
                'date_format:H:i',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_date.required_if' => 'An ongoing auction needs an end date.',
            'end_time.required_if' => 'An ongoing auction needs an end time.',
        ];
    }

    /**
     * Collapses the separate date and time inputs into the single timestamps
     * the auctions table stores.
     *
     * @return array<string, mixed>
     */
    public function auctionAttributes(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'status' => $validated['status'],
            'starts_at' => Carbon::parse("{$validated['start_date']} {$validated['start_time']}"),
            'ends_at' => $this->endsAt($validated),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function endsAt(array $validated): ?Carbon
    {
        if (empty($validated['end_date']) || empty($validated['end_time'])) {
            return null;
        }

        return Carbon::parse("{$validated['end_date']} {$validated['end_time']}");
    }
}
