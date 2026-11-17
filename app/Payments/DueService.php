<?php

namespace App\Payments;

use App\Enums\DueStatus;
use App\Enums\FamilyStatus;
use App\Enums\FeePeriod;
use App\Models\Due;
use App\Models\Family;
use App\Models\FeePlan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Monthly dues: generating a month for every active family, months paid in advance, and
 * waiving months with a reason.
 */
class DueService
{
    /**
     * The monthly subscription from the active monthly fee plan.
     */
    public function monthlyFee(): int
    {
        $plan = FeePlan::query()->where('period', FeePeriod::Monthly->value)->where('active', true)->orderBy('id')->first();

        return $plan !== null ? $plan->amount : (int) config('club.monthly_fee');
    }

    /**
     * Add the month's due for every active family that had joined by then. Running it again
     * for the same month adds nothing (one row per family and month, unique in the database).
     */
    public function generate(CarbonInterface $month): int
    {
        $period = Carbon::parse($month)->startOfMonth();
        $fee = $this->monthlyFee();
        $now = now();

        $rows = Family::query()
            ->where('status', FamilyStatus::Active->value)
            ->where(fn ($q) => $q->whereNull('joined_on')->orWhere('joined_on', '<=', $period->copy()->endOfMonth()->toDateString()))
            ->pluck('id')
            ->map(fn ($id): array => [
                'family_id' => $id,
                'period' => $period->toDateString(),
                'amount' => $fee,
                'status' => DueStatus::Due->value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $added = 0;

        foreach ($rows->chunk(500) as $chunk) {
            $added += DB::table('dues')->insertOrIgnore($chunk->values()->all());
        }

        return $added;
    }

    /**
     * Months after the family's last month, for paying ahead (status due until the payment
     * is confirmed in the same transaction).
     *
     * @return Collection<int, Due>
     */
    public function advanceMonths(Family $family, int $count): Collection
    {
        $last = Due::query()->where('family_id', $family->id)->max('period');
        $start = is_string($last) ? Carbon::parse($last)->startOfMonth()->addMonth() : now()->startOfMonth();
        $fee = $this->monthlyFee();
        $created = collect();

        for ($i = 0; $i < $count; $i++) {
            $created->push(Due::query()->create([
                'family_id' => $family->id,
                'period' => $start->copy()->addMonths($i)->toDateString(),
                'amount' => $fee,
                'status' => DueStatus::Due,
            ]));
        }

        return $created;
    }

    /**
     * Waive unpaid months (hardship, honorary members). Months waiting for a UPI confirmation
     * or already paid are left alone. Returns how many months were waived.
     *
     * @param  list<int>  $dueIds
     */
    public function waive(Family $family, array $dueIds, string $reason, User $by): int
    {
        return DB::transaction(function () use ($family, $dueIds, $reason, $by): int {
            $dues = Due::query()->where('family_id', $family->id)->whereIn('id', $dueIds)->payable()->lockForUpdate()->get();

            if ($dues->isEmpty()) {
                return 0;
            }

            Due::query()->whereIn('id', $dues->modelKeys())->update([
                'status' => DueStatus::Waived->value,
                'waived_reason' => $reason,
                'waived_by' => $by->id,
                'waived_at' => now(),
                'updated_at' => now(),
            ]);

            activity('payments')->performedOn($family)->causedBy($by)
                ->withProperties(['months' => $dues->map(fn (Due $due): string => $due->period->format('Y-m'))->all(), 'reason' => $reason])
                ->event('waived')
                ->log("Dues waived for {$family->family_no} ({$dues->count()} months): {$reason}");

            return $dues->count();
        });
    }
}
