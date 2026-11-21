<?php

namespace App\Support;

use App\Enums\PublishStatus;
use App\Models\Event;
use App\Models\PanjikaDay;

/**
 * What the panjika script needs in the browser: the committee's days, the club's published
 * events (shown as dots on the calendar), which panjika the club follows, and a few links.
 * Tithi, nakshatra, sunrise and the Bengali date are worked out by resources/js/panjika.
 */
final class PanjikaData
{
    public function __construct(private readonly SiteSettings $site) {}

    /**
     * @return array{en: string, bn: string}
     */
    public function source(): array
    {
        $saved = $this->site->get('panjika', []);
        $saved = is_array($saved) ? $saved : [];

        return [
            'en' => is_string($saved['source'] ?? null) && $saved['source'] !== '' ? $saved['source'] : 'the club’s panjika',
            'bn' => is_string($saved['source_bn'] ?? null) && $saved['source_bn'] !== '' ? $saved['source_bn'] : 'ক্লাবের পঞ্জিকা',
        ];
    }

    /**
     * @param  'site'|'member'  $surface  where event links point
     * @return array<string, mixed>
     */
    public function forScript(string $surface = 'site'): array
    {
        $days = PanjikaDay::query()->with('event')->orderBy('date')->get()->map(fn (PanjikaDay $day): array => [
            'date' => $day->date->toDateString(),
            'kind' => $day->kind->value,
            'en' => $day->name,
            'bn' => $day->name_bn ?: $day->name,
            'tithi' => $day->tithi,
            'lagna' => $day->lagna ? [$day->lagna, $day->lagna_bn ?: $day->lagna] : null,
            'url' => $day->event !== null ? $this->eventUrl($day->event, $surface) : null,
        ])->values()->all();

        $events = Event::query()
            ->where('status', PublishStatus::Published->value)
            ->whereNull('cancelled_at')
            ->where('starts_at', '>=', now()->subYear())
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Event $event): array => [
                'date' => $event->starts_at->toDateString(),
                'en' => $event->title,
                'bn' => $event->title_bn ?: $event->title,
                'url' => $this->eventUrl($event, $surface),
            ])->values()->all();

        return [
            'locale' => app()->getLocale(),
            'today' => today()->toDateString(),
            'days' => $days,
            'events' => $events,
            'source' => $this->source(),
            'urls' => [
                'panjika' => route('panjika'),
                'catering' => route('catering').'#quote',
            ],
        ];
    }

    private function eventUrl(Event $event, string $surface): string
    {
        return $surface === 'member' ? route('member.events.show', $event) : route('events.show', $event);
    }
}
