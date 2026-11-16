<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventKind;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventRequest extends WebsiteContentRequest
{
    protected function modelClass(): string
    {
        return Event::class;
    }

    protected function routeKey(): string
    {
        return 'event';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonRules(withPhoto: true),
            'title' => ['required', 'string', 'max:200'],
            'title_bn' => ['nullable', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', $this->uniqueSlug('events')],
            'kind' => ['required', Rule::enum(EventKind::class)],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'time_label' => ['nullable', 'string', 'max:100'],
            'time_label_bn' => ['nullable', 'string', 'max:100'],
            'place' => ['nullable', 'string', 'max:200'],
            'place_bn' => ['nullable', 'string', 'max:200'],
            'pass_required' => ['sometimes', 'boolean'],
            'chanda_box' => ['sometimes', 'boolean'],
            'contact_role' => ['nullable', 'string', 'max:50'],
            'about' => ['nullable', 'string', 'max:5000'],
            'about_bn' => ['nullable', 'string', 'max:5000'],
            'schedule' => ['nullable', 'string', 'max:5000'],
            'bring' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Model attributes from the form. Programme lines are "time | time (bn) | what | what (bn)";
     * things to bring are "text | text (bn)".
     *
     * @return array<string, mixed>
     */
    public function eventData(): array
    {
        $data = $this->safe()->except(['action', 'photo_upload', 'remove_photo', 'date', 'time', 'schedule', 'bring']);

        $data['starts_at'] = Carbon::parse($this->string('date')->toString().' '.($this->input('time') ?: '09:00'));
        $data['pass_required'] = $this->boolean('pass_required');
        $data['chanda_box'] = $this->boolean('chanda_box');
        $data['schedule'] = array_map(function (string $line): array {
            $parts = array_map('trim', explode('|', $line)) + ['', '', '', ''];

            return ['time' => $parts[0], 'time_bn' => $parts[1], 'what' => $parts[2], 'what_bn' => $parts[3]];
        }, $this->lines('schedule'));
        $data['bring'] = array_map(function (string $line): array {
            $parts = array_map('trim', explode('|', $line)) + ['', ''];

            return ['text' => $parts[0], 'text_bn' => $parts[1]];
        }, $this->lines('bring'));

        return $data;
    }
}
