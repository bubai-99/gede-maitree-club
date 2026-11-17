<?php

namespace App\Http\Requests\Member;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Register the signed-in member's family for an event: how many people are coming (the
 * family limit, seats and cut-off are checked by App\Passes\Registrations).
 */
class RegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->family_id !== null && $this->route('event') instanceof Event;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $limit = $event instanceof Event ? $event->familyLimit() : Event::DEFAULT_FAMILY_LIMIT;

        return [
            'people' => ['required', 'integer', 'min:1', 'max:'.$limit],
        ];
    }
}
