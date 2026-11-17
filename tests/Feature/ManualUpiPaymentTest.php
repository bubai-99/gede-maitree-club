<?php

namespace Tests\Feature;

use App\Enums\DueStatus;
use App\Enums\Gateway;
use App\Enums\LedgerAccount;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Due;
use App\Models\Family;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Payments\ManualUpiGateway;
use App\Payments\PaymentGateway;
use Database\Seeders\BookSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * UPI to the club (no Razorpay keys): QR code, the member's UTR, the treasurer's
 * confirmation, and matching a bank statement.
 */
class ManualUpiPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Family $family;

    private User $member;

    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, BookSeeder::class]);
        config([
            'services.razorpay.key_id' => null,
            'services.razorpay.key_secret' => null,
            'services.upi.id' => 'club@upi',
            'services.upi.name' => 'Gede Maitree Club',
        ]);
        Http::preventStrayRequests();

        $this->family = Family::factory()->create(['family_no' => 'MC-0600']);
        foreach (['2026-09-01', '2026-10-01'] as $period) {
            Due::factory()->for($this->family)->create(['period' => $period, 'amount' => 100]);
        }
        $this->member = User::factory()->forFamily($this->family)->create();
        $this->member->assignRole(Role::Member->value);

        $this->treasurer = User::factory()->committee()->create();
        $this->treasurer->assignRole(Role::Treasurer->value);
    }

    private function start(?User $member = null, ?Family $family = null): Payment
    {
        $family ??= $this->family;
        $this->actingAs($member ?? $this->member)
            ->post('/app/pay', ['dues' => $family->dues()->pluck('id')->all()])
            ->assertRedirect();

        return Payment::query()->where('family_id', $family->id)->latest('id')->firstOrFail();
    }

    private function submit(Payment $payment, string $utr = '412356789012', ?User $member = null): Payment
    {
        $this->actingAs($member ?? $this->member)->post('/app/pay/'.$payment->id.'/utr', ['utr' => $utr]);

        return $payment->fresh() ?? $payment;
    }

    public function test_without_razorpay_keys_members_pay_by_upi_to_the_club(): void
    {
        $this->assertInstanceOf(ManualUpiGateway::class, app(PaymentGateway::class));

        $payment = $this->start();
        $this->assertSame(Gateway::ManualUpi, $payment->gateway);
        $this->assertSame(200, $payment->amount);

        $this->actingAs($this->member)->get('/app/pay/'.$payment->id)
            ->assertOk()
            ->assertSee('upi://pay?pa=club%40upi&amp;pn=Gede%20Maitree%20Club&amp;am=200.00&amp;cu=INR&amp;tn=MC-0600%20'.$payment->reference, false)
            ->assertSee('<svg', false)
            ->assertSee('Open UPI app');
    }

    public function test_the_member_sends_the_utr_and_the_treasurer_confirms_it(): void
    {
        $payment = $this->submit($this->start(), '4123 5678 9012');

        $this->assertSame(PaymentStatus::AwaitingConfirmation, $payment->status);
        $this->assertSame('412356789012', $payment->upi_ref);
        $this->actingAs($this->member)->get('/app/pay')->assertSee('Payment sent, waiting for the treasurer');

        $this->actingAs($this->treasurer)->get('/admin/payments')->assertOk()->assertSee('412356789012')->assertSee('MC-0600');
        $this->actingAs($this->treasurer)->post('/admin/payments/'.$payment->id.'/confirm')->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame($this->treasurer->id, $payment->received_by);
        $this->assertNotNull($payment->receipt_no);
        $this->assertSame(0, $this->family->dues()->where('status', DueStatus::Due->value)->count());
        $this->assertSame(LedgerAccount::Bank, LedgerEntry::query()->sole()->account);

        $this->actingAs($this->member)->get('/app/receipts')->assertSee((string) $payment->receipt_no);
    }

    public function test_the_treasurer_can_reject_a_utr_and_the_months_stay_due(): void
    {
        $payment = $this->submit($this->start());

        $this->actingAs($this->treasurer)->post('/admin/payments/'.$payment->id.'/reject', ['reason' => 'Not in the bank'])->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::Rejected, $payment->status);
        $this->assertNull($payment->receipt_no);
        $this->assertSame(2, $this->family->dues()->where('status', DueStatus::Due->value)->count());
        $this->actingAs($this->member)->get('/app/pay')->assertSee('Not in the bank');

        // A rejected UTR can be sent again (for example after a typo was fixed).
        $again = $this->submit($this->start(), '412356789012');
        $this->assertSame(PaymentStatus::AwaitingConfirmation, $again->status);
    }

    public function test_a_rejection_needs_a_reason(): void
    {
        $payment = $this->submit($this->start());

        $this->actingAs($this->treasurer)->post('/admin/payments/'.$payment->id.'/reject', ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(PaymentStatus::AwaitingConfirmation, $payment->fresh()?->status);
    }

    public function test_a_utr_must_have_twelve_digits(): void
    {
        $payment = $this->start();

        $this->actingAs($this->member)->post('/app/pay/'.$payment->id.'/utr', ['utr' => '12345'])->assertSessionHasErrors('utr');
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()?->status);
    }

    public function test_a_utr_already_used_is_refused(): void
    {
        $this->submit($this->start(), '412356789012');

        $otherFamily = Family::factory()->create();
        Due::factory()->for($otherFamily)->create(['period' => '2026-10-01', 'amount' => 100]);
        $otherMember = User::factory()->forFamily($otherFamily)->create();
        $otherMember->assignRole(Role::Member->value);
        $other = $this->start($otherMember, $otherFamily);

        $this->actingAs($otherMember)->post('/app/pay/'.$other->id.'/utr', ['utr' => '412356789012'])->assertSessionHasErrors('utr');
        $this->assertSame(PaymentStatus::Pending, $other->fresh()?->status);
    }

    public function test_months_waiting_for_the_treasurer_cannot_be_paid_twice(): void
    {
        $this->submit($this->start());

        $this->actingAs($this->member)->get('/app/pay')->assertOk()->assertSee('Nothing else to pay right now.');
        $this->actingAs($this->member)->post('/app/pay', ['dues' => $this->family->dues()->pluck('id')->all()])->assertRedirect('/app/pay')->assertSessionHas('error');
    }

    public function test_another_family_cannot_send_a_utr_for_this_payment(): void
    {
        $payment = $this->start();
        $stranger = User::factory()->forFamily(Family::factory()->create())->create();
        $stranger->assignRole(Role::Member->value);

        $this->actingAs($stranger)->post('/app/pay/'.$payment->id.'/utr', ['utr' => '412356789012'])->assertForbidden();
    }

    public function test_bank_statement_lines_are_matched_by_utr_and_amount(): void
    {
        Storage::fake('local');
        $match = $this->submit($this->start(), '412356789012');

        $second = Family::factory()->create(['family_no' => 'MC-0601']);
        Due::factory()->for($second)->create(['period' => '2026-10-01', 'amount' => 100]);
        $secondMember = User::factory()->forFamily($second)->create();
        $secondMember->assignRole(Role::Member->value);
        $wrongAmount = $this->submit($this->start($secondMember, $second), '598765432109', $secondMember);

        $csv = "\u{FEFF}Txn Date,Value Date,Description,Ref No.,Debit,Credit,Balance\n"
            ."15/11/2026,15/11/2026,UPI/CR/412356789012/ANANYA/SBIN/upi,,,200.00,10200.00\n"
            ."15/11/2026,15/11/2026,UPI/CR/598765432109/SAHA/HDFC/upi,,,500.00,10700.00\n"
            ."16/11/2026,16/11/2026,UPI/CR/700000000001/UNKNOWN/ICIC/upi,,,100.00,10800.00\n"
            ."16/11/2026,16/11/2026,CESC BILL PAYMENT,,1870.00,,8930.00\n";

        $this->actingAs($this->treasurer)
            ->post('/admin/payments/import', ['statement' => UploadedFile::fake()->createWithContent('statement.csv', $csv)])
            ->assertRedirect();

        $token = (string) collect(session('bank_imports'))->keys()->first();

        $this->actingAs($this->treasurer)->get('/admin/payments/import/'.$token)
            ->assertOk()
            ->assertSee('Matched')
            ->assertSee('MC-0600')
            ->assertSee('The UTR matches, but the amount is different.')
            ->assertSee('No UPI payment is waiting with this UTR.');

        $this->actingAs($this->treasurer)
            ->post('/admin/payments/import/'.$token, ['date' => 0, 'amount' => 5, 'reference' => 2])
            ->assertRedirect(route('admin.payments.index'));

        $this->assertSame(PaymentStatus::Confirmed, $match->fresh()?->status);
        $this->assertSame(PaymentStatus::AwaitingConfirmation, $wrongAmount->fresh()?->status);
        Storage::disk('local')->assertMissing('imports/'.$token.'.csv');
    }

    public function test_someone_else_cannot_open_an_uploaded_statement(): void
    {
        Storage::fake('local');
        $this->actingAs($this->treasurer)
            ->post('/admin/payments/import', ['statement' => UploadedFile::fake()->createWithContent('s.csv', "Date,Description,Credit\n")]);
        $token = (string) collect(session('bank_imports'))->keys()->first();

        $other = User::factory()->committee()->create();
        $other->assignRole(Role::Treasurer->value);

        $this->flushSession();
        $this->actingAs($other)->get('/admin/payments/import/'.$token)->assertNotFound();
    }
}
