<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChandaMode;
use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\Event;
use App\Models\User;
use App\Passes\GateKeeper;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A chanda receipt: donor, area, amount in whole rupees, cash or UPI, who collected it.
 * A volunteer may only write in an area assigned to them, and always as the collector.
 */
class ChandaEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('event');
        $user = $this->user();

        return $event instanceof Event && $user instanceof User
            && $user->can('viewChanda', $event)
            && $user->can('recordIn', [ChandaEntry::class, $this->area()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $eventId = $event instanceof Event ? $event->id : 0;

        return [
            'area_id' => ['nullable', 'integer', Rule::exists('chanda_areas', 'id')->where('event_id', $eventId)],
            'donor_name' => ['required', 'string', 'max:120'],
            'donor_phone' => ['nullable', 'string', 'regex:/^\+?[0-9 ]{10,15}$/'],
            'family_no' => ['nullable', 'string', 'max:20'],
            'show_name' => ['sometimes', 'boolean'],
            'amount' => ['required', 'integer', 'min:1', 'max:10000000'],
            'mode' => ['required', Rule::enum(ChandaMode::class)],
            'upi_ref' => ['nullable', 'required_if:mode,upi', 'string', 'regex:/^[A-Za-z0-9]{6,40}$/'],
            'received_on' => ['nullable', 'date', 'before_or_equal:today', 'after:2014-01-01'],
            'collected_by' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_committee', true)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('family_no') && GateKeeper::findFamily($this->string('family_no')->toString()) === null) {
                    $validator->errors()->add('family_no', __('No family with this number'));
                }
            },
        ];
    }

    public function area(): ?ChandaArea
    {
        $event = $this->route('event');

        if (! $event instanceof Event || ! $this->filled('area_id')) {
            return null;
        }

        return ChandaArea::query()->where('event_id', $event->id)->find($this->integer('area_id'));
    }

    /**
     * @return array{donor_name: string, donor_phone: ?string, family_id: ?int, area: ?ChandaArea, amount: int, mode: ChandaMode, upi_ref: ?string, show_name: bool, collected_by: int, received_at: CarbonInterface}
     */
    public function entryData(): array
    {
        $user = $this->user();
        assert($user instanceof User);
        $event = $this->route('event');
        $manager = $event instanceof Event && $user->can('manageChanda', $event);
        $family = $this->filled('family_no') ? GateKeeper::findFamily($this->string('family_no')->toString()) : null;
        $date = $this->filled('received_on') ? Carbon::parse($this->string('received_on')->toString()) : null;

        return [
            'donor_name' => $this->string('donor_name')->trim()->toString(),
            'donor_phone' => $this->filled('donor_phone') ? $this->string('donor_phone')->replace(' ', '')->toString() : null,
            'family_id' => $family?->id,
            'area' => $this->area(),
            'amount' => $this->integer('amount'),
            'mode' => ChandaMode::from($this->string('mode')->toString()),
            'upi_ref' => $this->filled('upi_ref') ? $this->string('upi_ref')->toString() : null,
            'show_name' => $this->boolean('show_name'),
            'collected_by' => $manager && $this->filled('collected_by') ? $this->integer('collected_by') : $user->id,
            'received_at' => $date !== null && ! $date->isToday() ? $date->setTime(12, 0) : now(),
        ];
    }
}
