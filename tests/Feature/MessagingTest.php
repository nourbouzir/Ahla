<?php

namespace Tests\Feature;

use App\Contracts\Fakes\NullTranslator;
use App\Contracts\Translation\Translator;
use App\Events\MessageSent;
use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Experience\Models\Experience;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Services\LibreTranslateTranslator;
use App\Modules\Messaging\Services\MessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    private User $tourist;

    private User $host;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->tourist = User::factory()->tourist()->create(['preferred_language' => 'fr']);
        $this->host = User::factory()->verifiedHost()->create(['preferred_language' => 'en']);
        $this->booking = Booking::factory()->create(['tourist_id' => $this->tourist->id, 'experience_id' => Experience::factory()->create(['host_profile_id' => $this->host->hostProfile->id])->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function say(User $who, string $body, ?Booking $booking = null)
    {
        return $this->actingAs($who)->post('/bookings/'.($booking ?? $this->booking)->id.'/messages', ['body' => $body]);
    }

    public function test_tourist_and_host_converse_with_original_kept_and_translation_added(): void
    {
        /** @var NullTranslator $translator */
        $translator = app(Translator::class);
        $translator->setTranslations(['fr|en|Bonjour, avez-vous de la place ?' => 'Hello, do you have room?', 'en|fr|Yes, come at ten.' => 'Oui, venez à dix heures.']);

        $this->say($this->tourist, 'Bonjour, avez-vous de la place ?')->assertRedirect();
        $this->say($this->host, 'Yes, come at ten.')->assertRedirect();

        $first = Message::orderBy('id')->first();
        $this->assertSame('Bonjour, avez-vous de la place ?', $first->original_text, 'the original is always kept');
        $this->assertSame('Hello, do you have room?', $first->translated_text);
        $this->assertSame(['fr', 'en'], [$first->language, $first->translated_language]);

        // The host reads the English translation and can open the original.
        $this->actingAs($this->host)->get("/bookings/{$this->booking->id}/messages")->assertOk()
            ->assertSee('Hello, do you have room?')->assertSee('Show original')->assertSee('Bonjour, avez-vous de la place ?');
        // The tourist reads the French translation of the host's answer.
        app()->setLocale('fr');
        $this->actingAs($this->tourist)->get("/bookings/{$this->booking->id}/messages")
            ->assertSee('Oui, venez à dix heures.')->assertSee('Voir l’original');
    }

    public function test_same_language_needs_no_translation(): void
    {
        $this->host->update(['preferred_language' => 'fr']);

        $this->say($this->tourist, 'Salut');

        $this->assertNull(Message::first()->translated_language);
        $this->assertNull(Message::first()->translated_text);
    }

    public function test_translation_failure_never_blocks_or_loses_a_message(): void
    {
        $this->app->singleton(Translator::class, fn () => new class implements Translator
        {
            public function translate(string $text, string $from, string $to): string
            {
                throw new \RuntimeException('provider down');
            }
        });

        $this->say($this->tourist, 'Bonjour')->assertRedirect();

        $message = Message::firstOrFail();
        $this->assertSame('Bonjour', $message->original_text);
        $this->assertNull($message->translated_text);

        // Fresh: "translating"; after 30 s: "unavailable - showing the original".
        $this->actingAs($this->host)->get("/bookings/{$this->booking->id}/messages")->assertSee('Translating')->assertSee('Bonjour');
        Carbon::setTestNow(now()->addMinute());
        $this->get("/bookings/{$this->booking->id}/messages")->assertSee('Translation unavailable')->assertSee('Bonjour');
    }

    public function test_only_the_two_participants_can_read_or_write(): void
    {
        $this->say($this->tourist, 'private');
        $stranger = User::factory()->tourist()->create();
        $otherHost = User::factory()->verifiedHost()->create();
        $admin = User::factory()->admin()->create();
        $url = "/bookings/{$this->booking->id}/messages";

        foreach ([$stranger, $otherHost, $admin] as $user) {
            $this->actingAs($user)->get($url)->assertForbidden();
            $this->actingAs($user)->getJson("$url/poll")->assertForbidden();
            $this->actingAs($user)->postJson($url, ['body' => 'hi'])->assertForbidden();
        }

        $this->assertSame(1, Message::count());
        auth()->logout();
        $this->get($url)->assertRedirect('/login');
    }

    public function test_conversation_exists_from_requested_and_closes_for_writing_when_the_booking_is_closed(): void
    {
        $this->assertSame('requested', $this->booking->status->value);
        $this->say($this->host, 'Hello')->assertRedirect();

        foreach (['declined', 'expired', 'cancelled'] as $status) {
            $this->booking->forceFill(['status' => $status])->save();
            $this->say($this->tourist, 'late')->assertForbidden();
            $this->actingAs($this->tourist)->get("/bookings/{$this->booking->id}/messages")->assertOk()->assertSee('Hello');
        }

        $this->booking->forceFill(['status' => 'confirmed'])->save();
        $this->say($this->tourist, 'See you')->assertRedirect();
        $this->assertSame(2, Message::count());
    }

    public function test_validation_and_xss(): void
    {
        $this->say($this->tourist, '')->assertSessionHasErrors('body');
        $this->say($this->tourist, str_repeat('a', 1001))->assertSessionHasErrors('body');

        $this->say($this->tourist, '<script>alert(1)</script>');
        $this->actingAs($this->host)->get("/bookings/{$this->booking->id}/messages")
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_sending_is_rate_limited(): void
    {
        foreach (range(1, 30) as $i) {
            $this->say($this->tourist, "m$i")->assertRedirect();
        }

        $this->say($this->tourist, 'one too many')->assertStatus(429);
    }

    public function test_message_sent_event_and_polling_with_read_receipts(): void
    {
        Event::fake([MessageSent::class]);
        $this->say($this->tourist, 'one');
        $this->say($this->tourist, 'two');
        Event::assertDispatchedTimes(MessageSent::class, 2);
        Event::assertDispatched(MessageSent::class, fn ($e) => $e->senderId === $this->tourist->id && $e->recipientId === $this->host->id);

        $first = Message::orderBy('id')->first();
        $poll = $this->actingAs($this->host)->getJson("/bookings/{$this->booking->id}/messages/poll?after={$first->id}")->assertOk();
        $this->assertSame(['two'], collect($poll->json('messages'))->pluck('text')->all());
        $this->assertSame(0, Message::whereNull('read_at')->where('recipient_id', $this->host->id)->count(), 'host has now read everything');
    }

    public function test_libretranslate_adapter(): void
    {
        config(['ahla.translation.url' => 'https://translate.test', 'ahla.translation.api_key' => 'k']);
        Http::fake(['translate.test/translate' => Http::sequence()->push(['translatedText' => 'Hello'])->push('boom', 500)]);

        $this->assertSame('Hello', (new LibreTranslateTranslator)->translate('Bonjour', 'fr', 'en'));
        Http::assertSent(fn ($r) => $r['q'] === 'Bonjour' && $r['source'] === 'fr' && $r['target'] === 'en' && ! isset($r['name']) && ! isset($r['email']));

        $this->expectException(\Throwable::class);
        (new LibreTranslateTranslator)->translate('Bonjour', 'fr', 'en');
    }

    public function test_adapter_refuses_unconfigured_unsupported_and_empty_answers(): void
    {
        $adapter = new LibreTranslateTranslator;

        config(['ahla.translation.url' => '']);
        $this->assertThrowsMessage('No translation service', fn () => $adapter->translate('a', 'fr', 'en'));

        config(['ahla.translation.url' => 'https://translate.test']);
        $this->assertThrowsMessage('Unsupported', fn () => $adapter->translate('a', 'fr', 'de'));

        Http::fake(['translate.test/translate' => Http::response(['translatedText' => ''])]);
        $this->assertThrowsMessage('no text', fn () => $adapter->translate('a', 'fr', 'en'));
    }

    private function assertThrowsMessage(string $fragment, callable $call): void
    {
        try {
            $call();
            $this->fail('expected an exception');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString($fragment, $e->getMessage());
        }
    }

    public function test_service_participation_rules(): void
    {
        $service = app(MessagingService::class);

        $this->assertTrue($service->isParticipant($this->tourist, $this->booking));
        $this->assertFalse($service->isParticipant(User::factory()->tourist()->create(), $this->booking));
        $this->expectException(\InvalidArgumentException::class);
        $service->send($this->booking, User::factory()->tourist()->create(), 'hi');
    }
}
