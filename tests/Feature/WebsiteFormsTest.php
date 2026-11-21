<?php

namespace Tests\Feature;

use App\Enums\EnquiryKind;
use App\Models\CateringPackage;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contact_form_is_saved_as_an_enquiry(): void
    {
        $this->from('/contact')->post('/contact', [
            'name' => 'Rina Chatterjee',
            'phone' => '+91 98300 11111',
            'topic' => 'membership',
            'message' => 'How do I join?',
            'reply_by' => 'whatsapp',
        ])->assertRedirect('/contact')->assertSessionHas('status');

        $enquiry = Enquiry::query()->sole();
        $this->assertSame(EnquiryKind::Contact, $enquiry->kind);
        $this->assertSame('9830011111', $enquiry->phone);
        $this->assertSame('How do I join?', $enquiry->payload['message']);
        $this->assertNull($enquiry->handled_at);
    }

    public function test_the_thank_you_message_is_shown_in_bengali(): void
    {
        $this->withSession(['locale' => 'bn'])->from('/contact')->post('/contact', [
            'name' => 'রিনা', 'phone' => '9830011111', 'topic' => 'other', 'message' => 'নমস্কার', 'reply_by' => 'call',
        ]);

        $this->get('/contact')->assertSee(__('Thank you. We usually reply within a day, by phone or WhatsApp.', [], 'bn'));
        $this->assertSame('bn', Enquiry::query()->sole()->locale);
    }

    public function test_the_membership_application_keeps_the_household(): void
    {
        $this->post('/join', [
            'name' => 'Sample Applicant',
            'phone' => '9831000000',
            'dob' => '1985-04-01',
            'blood_group' => 'B+',
            'address' => '3 School Road',
            'area' => 'School Road',
            'aadhaar_last4' => '1234',
            'members' => [['name' => 'Sample Spouse', 'relation' => 'Spouse', 'dob' => '1987-01-01'], ['name' => '', 'relation' => '', 'dob' => '']],
            'plan' => 'yearly',
            'agree' => '1',
        ])->assertSessionHasNoErrors();

        $enquiry = Enquiry::query()->sole();
        $this->assertSame(EnquiryKind::Join, $enquiry->kind);
        $this->assertCount(1, $enquiry->payload['members']);
        $this->assertSame('yearly', $enquiry->payload['plan']);
    }

    public function test_the_membership_application_needs_the_pledge(): void
    {
        $this->post('/join', ['name' => 'A', 'phone' => '9831000000', 'dob' => '1985-04-01', 'address' => 'x', 'area' => 'Lane 1', 'plan' => 'monthly'])
            ->assertSessionHasErrors('agree');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_a_catering_enquiry_needs_a_published_package(): void
    {
        $live = CateringPackage::factory()->create(['slug' => 'live']);
        CateringPackage::factory()->draft()->create(['slug' => 'hidden']);
        $data = ['name' => 'Sen family', 'phone' => '9830022222', 'occasion' => 'annaprashan', 'event_date' => now()->addMonth()->toDateString(), 'guests' => 70];

        $this->post('/catering', [...$data, 'package' => 'hidden'])->assertSessionHasErrors('package');
        $this->post('/catering', [...$data, 'package' => $live->slug, 'is_member' => '1'])->assertSessionHasNoErrors();

        $this->assertTrue(Enquiry::query()->sole()->payload['is_member']);
    }

    public function test_volunteers_pick_at_least_one_job(): void
    {
        $this->post('/volunteer', ['name' => 'Bapi', 'phone' => '9830033333', 'area' => 'Lane 1'])->assertSessionHasErrors('skills');
        $this->post('/volunteer', ['name' => 'Bapi', 'phone' => '9830033333', 'area' => 'Lane 1', 'skills' => ['gate', 'bhog'], 'times' => ['weekends']])
            ->assertSessionHasNoErrors();

        $this->assertSame(['gate', 'bhog'], Enquiry::query()->sole()->payload['skills']);
    }

    public function test_photos_and_chanda_pledges_are_saved(): void
    {
        $this->post('/gallery/photos', ['name' => 'Moumita', 'phone' => '9830044444', 'event' => 'Durga Puja', 'link' => 'https://photos.example.com/album'])
            ->assertSessionHasNoErrors();
        $this->post('/chanda/pledge', ['name' => 'Gopal', 'phone' => '9830055555', 'area' => 'Lane 2', 'amount' => 501, 'pay_by' => 'visit'])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing([EnquiryKind::Photos, EnquiryKind::Chanda], Enquiry::query()->get()->pluck('kind')->all());
    }

    public function test_bots_that_fill_the_honeypot_are_turned_away(): void
    {
        $this->post('/contact', [
            'name' => 'Bot', 'phone' => '9830011111', 'topic' => 'other', 'message' => 'spam', 'reply_by' => 'call',
            'website' => 'http://spam.example.com',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Enquiry::count());
    }

    public function test_phone_numbers_are_checked(): void
    {
        $this->post('/contact', ['name' => 'A', 'phone' => '12345', 'topic' => 'other', 'message' => 'x', 'reply_by' => 'call'])
            ->assertSessionHasErrors('phone');
    }

    public function test_validation_messages_are_in_bengali_on_the_bengali_site(): void
    {
        $this->withSession(['locale' => 'bn'])->post('/contact', [])->assertSessionHasErrors(['name' => 'আপনার নাম দিন।']);
    }

    public function test_forms_are_rate_limited(): void
    {
        $data = ['name' => 'A', 'phone' => '9830011111', 'topic' => 'other', 'message' => 'x', 'reply_by' => 'call'];

        foreach (range(1, 5) as $try) {
            $this->post('/contact', $data)->assertRedirect();
        }

        $this->post('/contact', $data)->assertTooManyRequests();
        $this->assertSame(5, Enquiry::count());
    }

    public function test_the_club_letter_takes_a_whatsapp_number_or_an_email_once(): void
    {
        $this->from('/events')->post('/news', ['via' => 'wa', 'contact' => '98300 11111'])
            ->assertRedirect(url('/events').'#club-letter')->assertSessionHas('news_done', 'wa');
        $this->from('/events')->post('/news', ['via' => 'wa', 'contact' => '+91 98300 11111']);
        $this->from('/')->post('/news', ['via' => 'email', 'contact' => 'Sample@Example.org'])->assertSessionHas('news_done', 'email');

        $this->assertSame(2, Enquiry::query()->where('kind', EnquiryKind::News->value)->count());
        $this->assertTrue(Enquiry::query()->where('email', 'sample@example.org')->exists());

        $this->from('/')->post('/news', ['via' => 'email', 'contact' => 'not-an-email'])->assertSessionHasErrors('contact');
        $this->from('/')->post('/news', ['via' => 'wa', 'contact' => '12345'])->assertSessionHasErrors('contact');
        $this->get('/')->assertSee('Where should we send it?');
    }

    public function test_validation_messages_are_plain_english_with_proper_field_names(): void
    {
        $this->post('/contact', ['email' => 'not-an-email'])->assertSessionHasErrors([
            'name' => 'Enter your name.',
            'topic' => 'Choose topic.',
            'message' => 'Enter your message.',
        ]);
        $this->from('/contact')->followingRedirects()->post('/contact', [])
            ->assertSee('Please check these', false)->assertSee('data-error-summary', false)->assertSee('Enter your name.', false);
    }
}
