<?php

namespace Database\Seeders;

use App\Enums\DueStatus;
use App\Enums\EventKind;
use App\Enums\FamilyStatus;
use App\Enums\NoticeAudience;
use App\Enums\PaymentMode;
use App\Enums\PaymentStatus;
use App\Enums\PlantCategory;
use App\Enums\PlantLight;
use App\Enums\PlantWater;
use App\Enums\PublishStatus;
use App\Models\CateringPackage;
use App\Models\CommitteeMember;
use App\Models\Due;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * SAMPLE content from the clickable prototype (database/seeders/data/prototype.json):
 * events, plants, serving packages, committee, notices and sample families.
 * Names, phone numbers, amounts and photos are placeholders, not real member data.
 * The site shows a "sample content" note while site_settings.sample_data is true.
 */
class SampleDataSeeder extends Seeder
{
    /** @var array<string, mixed> */
    private array $data;

    public function run(): void
    {
        $this->data = json_decode(File::get(database_path('seeders/data/prototype.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->events();
        $this->plants();
        $this->packages();
        $this->committee();
        $this->notices();
        $this->families();
        $this->chanda();
        $this->gallery();

        SiteSetting::query()->updateOrCreate(['key' => 'sample_data'], ['value' => true]);
    }

    /**
     * The current puja chanda drive (SAMPLE figures): the budget, target and "show on the
     * website" switch live on the event; last puja's spending and the club's documents are
     * site settings. Areas, receipts and sponsors are made by SampleEventsSeeder.
     */
    private function chanda(): void
    {
        $drive = $this->data['drive'];
        $event = Event::query()->where('chanda_box', true)->first();

        $event?->update([
            'chanda_target' => $drive['target'],
            'chanda_public' => true,
            'budget' => array_map(fn (array $b): array => ['head' => $b['head'], 'head_bn' => $b['headBn'], 'amount' => $b['amount']], $drive['budget']),
        ]);

        SiteSetting::query()->updateOrCreate(['key' => 'chanda'], ['value' => [
            'event_slug' => $event?->slug,
            'last_puja' => [
                'title' => 'Durga Puja 2026',
                'title_bn' => 'দুর্গাপূজা ২০২৬',
                'spend' => array_map(fn (array $s): array => ['head' => $s['en'], 'head_bn' => $s['bn'], 'amount' => $s['v'], 'colour' => $s['c']], $this->data['pujaSpend']),
            ],
            'documents' => array_map(fn (array $d): array => [
                'title' => $d['en'], 'title_bn' => $d['bn'], 'note' => $d['note'], 'note_bn' => $d['noteBn'], 'size' => $d['size'], 'size_bn' => $d['sizeBn'],
            ], $this->data['documents']),
        ]]);
    }

    /**
     * Gallery albums, date-wise (SAMPLE Unsplash photos, credited on the page).
     */
    private function gallery(): void
    {
        SiteSetting::query()->updateOrCreate(['key' => 'gallery'], ['value' => [
            'albums' => array_map(fn (array $a): array => ['key' => $a['key'], 'title' => $a['en'], 'title_bn' => $a['bn']], $this->data['ALBUMS']),
            'days' => array_map(fn (array $g): array => [
                'id' => $g['id'],
                'album' => $g['album'],
                'date' => $g['date'],
                'title' => $g['en'],
                'title_bn' => $g['bn'],
                'items' => array_map(fn (array $item): array => isset($item['v'])
                    ? ['type' => 'video', 'photo' => $item['poster'], 'caption' => $item['v'], 'caption_bn' => $item['vBn'], 'duration' => $item['dur']]
                    : ['type' => 'photo', 'photo' => $item['p']], $g['items']),
            ], $this->data['GALLERY']),
        ]]);
    }

    private function events(): void
    {
        foreach ($this->data['events'] as $sort => $row) {
            $info = $this->data['eventInfo'][$row['id']] ?? [];
            $schedule = array_map(fn (array $s): array => [
                'time' => $s[0], 'time_bn' => $s[1], 'what' => $s[2], 'what_bn' => $s[3],
            ], $info['schedule'] ?? []);

            $start = Carbon::parse($row['date'].' '.($info['schedule'][0][0] ?? '9:00 am'));

            Event::query()->updateOrCreate(['slug' => $row['id']], [
                'title' => $row['title'],
                'title_bn' => $row['titleBn'],
                'kind' => EventKind::from($row['kind']),
                'starts_at' => $start,
                'time_label' => $row['time'],
                'time_label_bn' => $row['timeBn'] ?? $info['timeBn'] ?? null,
                'place' => $row['place'],
                'place_bn' => $row['placeBn'],
                'pass_required' => (bool) $row['pass'],
                'registration_open' => (bool) $row['pass'],
                'family_limit' => $row['pass'] ? 6 : null,
                'capacity' => $row['id'] === 'picnic' ? 120 : null,
                'contact_role' => $info['contact'] ?? 'Secretary',
                'chanda_box' => (bool) ($info['chanda'] ?? false),
                'about' => $info['about'] ?? null,
                'about_bn' => $info['aboutBn'] ?? null,
                'schedule' => $schedule,
                'bring' => array_map(fn (array $b): array => ['text' => $b[0], 'text_bn' => $b[1]], $info['bring'] ?? []),
                'photo' => $info['photo'] ?? null,
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);
        }
    }

    private function plants(): void
    {
        foreach ($this->data['plants'] as $sort => $row) {
            $info = $this->data['plantInfo'][$row['id']] ?? [];

            Plant::query()->updateOrCreate(['slug' => Str::slug(Str::before($row['name'], ' ('))], [
                'name' => $row['name'],
                'name_bn' => $row['nameBn'],
                'category' => PlantCategory::from($row['cat']),
                'pot' => $row['pot'],
                'pot_bn' => $row['potBn'] ?? null,
                'price' => $row['price'],
                'member_price' => (int) round($row['price'] * 0.9),
                'stock' => $row['stock'],
                'is_new' => (bool) ($row['new'] ?? false),
                'light' => isset($info['light']) ? PlantLight::from($info['light']) : null,
                'water' => isset($info['water']) ? PlantWater::from($info['water']) : null,
                'about' => $info['about'][0] ?? null,
                'about_bn' => $info['about'][1] ?? null,
                'tip' => $info['tip'][0] ?? null,
                'tip_bn' => $info['tip'][1] ?? null,
                'photo' => $row['id'],
                'active' => true,
                'sort' => $sort + 1,
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);
        }
    }

    private function packages(): void
    {
        foreach ($this->data['cateringPackages'] as $sort => $row) {
            CateringPackage::query()->updateOrCreate(['slug' => $row['id']], [
                'name' => $row['name'],
                'name_bn' => $row['nameBn'],
                'for' => $row['for'],
                'for_bn' => $row['forBn'],
                'rate' => $row['rate'],
                'min_guests' => $row['min'],
                'popular' => (bool) ($row['popular'] ?? false),
                'menu' => $row['menu'],
                'menu_bn' => $row['menuBn'],
                'sort' => $sort + 1,
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);
        }
    }

    private function committee(): void
    {
        foreach ($this->data['committee'] as $sort => $row) {
            CommitteeMember::query()->updateOrCreate(['role' => $row['role']], [
                'name' => $row['name'],
                'name_bn' => $row['nameBn'],
                'role_bn' => $row['roleBn'],
                'phone' => $this->data['club']['phone'],
                'since' => $row['since'],
                'sort' => $sort + 1,
                'status' => PublishStatus::Published,
                'published_at' => now(),
            ]);
        }
    }

    private function notices(): void
    {
        foreach ($this->data['publicNotices'] as $row) {
            Notice::query()->updateOrCreate(['slug' => Str::slug($row['title'])], [
                'title' => $row['title'],
                'title_bn' => $row['titleBn'],
                'tag' => $row['tag'],
                'tag_bn' => $row['tagBn'],
                'summary' => $row['body'],
                'summary_bn' => $row['bodyBn'],
                'body' => $row['more'] ?? null,
                'body_bn' => $row['moreBn'] ?? null,
                'audience' => NoticeAudience::Public,
                'pinned' => (bool) ($row['pinned'] ?? false),
                'status' => PublishStatus::Published,
                'published_at' => Carbon::parse($row['iso'])->setTime(10, 0),
            ]);
        }

        Notice::query()->updateOrCreate(['slug' => 'agm-agenda-for-members'], [
            'title' => 'AGM agenda for members',
            'title_bn' => 'সদস্যদের জন্য এজিএম-এর আলোচ্যসূচি',
            'tag' => 'Members',
            'tag_bn' => 'সদস্য',
            'summary' => 'The draft agenda and last year’s audited accounts are ready to read before the AGM.',
            'summary_bn' => 'এজিএম-এর আগে খসড়া আলোচ্যসূচি ও গত বছরের অডিট করা হিসাব পড়ে নিন।',
            'body' => 'Bring your questions to the AGM. Members can vote on the agenda in the app.',
            'body_bn' => 'প্রশ্ন নিয়ে এজিএম-এ আসুন। সদস্যরা অ্যাপে আলোচ্যসূচিতে ভোট দিতে পারবেন।',
            'audience' => NoticeAudience::Members,
            'pinned' => false,
            'status' => PublishStatus::Published,
            'published_at' => now()->subDays(2),
        ]);
    }

    private function families(): void
    {
        $fee = (int) $this->data['club']['monthlyFee'];
        $thisMonth = now()->startOfMonth();

        foreach ($this->data['families'] as $row) {
            $family = Family::query()->updateOrCreate(['family_no' => $row['no']], [
                'head_name' => $row['head'],
                'head_name_bn' => $row['headBn'],
                'address' => $row['lane'].', Netaji Para',
                'area' => $row['lane'],
                'area_bn' => $row['laneBn'],
                'phone' => $row['phone'],
                'status' => $row['status'] === 'lapsed' ? FamilyStatus::Lapsed : FamilyStatus::Active,
                'joined_on' => Carbon::create((int) $row['since'], 4, 1),
            ]);

            $family->members()->updateOrCreate(['is_head' => true], [
                'name' => $row['head'],
                'name_bn' => $row['headBn'],
                'relation' => 'Head of family',
                'relation_bn' => 'পরিবারের প্রধান',
                'phone' => $row['phone'],
                'member_no' => $row['no'].'-1',
            ]);

            // Months still due, most recent first; the six months before that are paid.
            for ($i = 0; $i < 6 + (int) $row['dueMonths']; $i++) {
                $period = $thisMonth->copy()->subMonths($i);
                Due::query()->updateOrCreate(['family_id' => $family->id, 'period' => $period->toDateString()], [
                    'amount' => $fee,
                    'status' => $i < (int) $row['dueMonths'] ? DueStatus::Due : DueStatus::Paid,
                ]);
            }
        }

        $this->sampleHousehold();
    }

    /**
     * The prototype's signed-in member: family MC-0104 with two dependents and a payment history.
     */
    private function sampleHousehold(): void
    {
        $family = Family::query()->where('family_no', $this->data['me']['family']['no'])->firstOrFail();

        foreach ($this->data['me']['dependents'] as $i => $person) {
            Member::query()->updateOrCreate(['family_id' => $family->id, 'name' => $person['name']], [
                'name_bn' => $person['nameBn'],
                'relation' => Str::before($person['rel'], ','),
                'relation_bn' => Str::before($person['relBn'], ','),
                'dob' => str_contains($person['rel'], 'Son') ? now()->subYears(12)->subMonths(3) : now()->subYears(41),
                'blood_group' => $i === 0 ? 'B+' : 'O+',
                'member_no' => $family->family_no.'-'.($i + 2),
            ]);
        }

        $family->members()->where('is_head', true)->update(['blood_group' => 'A+', 'dob' => now()->subYears(39)->toDateString()]);

        $paid = $family->dues()->where('status', DueStatus::Paid->value)->orderByDesc('period')->get();

        foreach ($this->data['me']['history'] as $i => $row) {
            $payment = Payment::query()->updateOrCreate(['receipt_no' => $row['receipt']], [
                'family_id' => $family->id,
                'amount' => $row['amount'],
                'mode' => strtolower($row['mode']) === 'upi' ? PaymentMode::Upi : PaymentMode::Cash,
                'upi_ref' => strtolower($row['mode']) === 'upi' ? '4312'.str_pad((string) ($i + 1), 8, '0', STR_PAD_LEFT) : null,
                'status' => PaymentStatus::Confirmed,
                'paid_at' => Carbon::parse($row['date'])->setTime(18, 30),
            ]);

            $payment->dues()->sync($paid->slice($i * 2, 2)->pluck('id')->all());
        }

    }
}
