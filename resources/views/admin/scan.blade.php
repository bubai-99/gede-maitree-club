@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Event> $events */
    $user = auth()->user();
    $config = [
        'locale' => app()->getLocale(),
        'user' => $user?->id,
        'current' => $event?->slug,
        'events' => $events->map(fn (\App\Models\Event $e) => [
            'slug' => $e->slug,
            'title' => $e->tr('title'),
            'date' => local_date($e->starts_at, 'j M'),
            'place' => $e->tr('place'),
            'urls' => [
                'passes' => route('admin.scan.passes', $e),
                'scans' => route('admin.scan.store', $e),
                'sync' => route('admin.scan.sync', $e),
                'shift' => route('admin.scan.shift', $e),
            ],
        ])->values(),
        'urls' => [
            'scan' => route('admin.scan.update', ['scan' => '__UUID__']),
            'logout' => route('admin.logout'),
            'login' => route('admin.login'),
            'console' => route('admin.dashboard'),
        ],
        'undoSeconds' => \App\Models\PassScan::UNDO_SECONDS,
        't' => [
            'online' => __('Online'),
            'offline' => __('Offline'),
            'chooseGate' => __('Name your gate'),
            'gate' => __('Gate'),
            'synced' => __(':passes passes on this phone · synced :time'),
            'notDownloaded' => __('Pass list not on this phone yet. Connect to download it.'),
            'waiting' => __(':count scans waiting to upload'),
            'allUploaded' => __('All scans uploaded'),
            'letIn' => __('Let them in'),
            'familyPass' => __('Family pass · :count people'),
            'comingIn' => __('Coming in now'),
            'fewer' => __('Fewer'),
            'more' => __('More'),
            'doneNext' => __('Done · next scan'),
            'nextScan' => __('Next scan'),
            'already' => __('Already inside'),
            'enteredAt' => __('Entered :time at :gate, scanned by :name'),
            'enteredEarlier' => __('Entered earlier (checked on this phone)'),
            'reentryNote' => __('If someone stepped out and came back, it is fine to let them in.'),
            'letBackIn' => __('Let back in'),
            'dontAllow' => __('Don’t allow'),
            'cancelled' => __('Pass cancelled'),
            'cancelledDetail' => __('This registration or the event was cancelled.'),
            'deskNote' => __('Send them to the committee desk.'),
            'noPass' => __('No pass for this event'),
            'noPassDetail' => __('This family has not registered for this event.'),
            'wrongEvent' => __('Pass for another event'),
            'wrongEventDetail' => __('This pass is for a different event.'),
            'notFound' => __('Pass not found'),
            'notClubPass' => __('This QR is not a Gede Maitree Club pass'),
            'askCard' => __('Ask for the member card, or type the family number'),
            'checkedHere' => __('Checked on this phone · uploads when there is signal'),
            'duplicateNote' => __('Another gate let this pass in earlier. Both entries are kept for the committee.'),
            'cancelledConflict' => __('The server says this pass was cancelled. The committee will see it.'),
            'noScans' => __('No scans yet on this shift'),
            'turnedAway' => __('turned away'),
            'reentry' => __('re-entry'),
            'familyNumber' => __('Family number'),
            'familyNumberHint' => __('It is printed on the member card, under the name.'),
            'check' => __('Check'),
            'cancel' => __('Cancel'),
            'noFamily' => __('No pass on this phone for this number'),
            'peopleCount' => __(':count people'),
            'eventAndGate' => __('Event and gate'),
            'eventLabel' => __('EVENT'),
            'gateLabel' => __('GATE'),
            'gatePlaceholder' => __('e.g. Gate 1'),
            'downloadNote' => __('Passes for each event are downloaded when you pick it, so the gate keeps working without signal.'),
            'done' => __('Done'),
            'workingOffline' => __('Working offline'),
            'listOnPhone' => __('All :count passes for this event are on this phone'),
            'willUpload' => __(':count scans will upload when there is signal'),
            'nothingWaiting' => __('Nothing waiting to upload'),
            'otherGates' => __('Other gates’ scans appear here after both phones sync'),
            'syncNow' => __('Sync now'),
            'close' => __('Close'),
            'looking' => __('Looking for signal…'),
            'uploaded' => __('Uploaded :count scans.'),
            'duplicatesFound' => __('Let in twice (first scan counts): :list'),
            'syncFailed' => __('No signal. The scans stay on this phone.'),
            'signInAgain' => __('Your sign-in has ended. Sign in again to upload the scans.'),
            'refused' => __(':count changes were refused by the server.'),
            'endShift' => __('End your shift'),
            'scans' => __('scans'),
            'letInCount' => __('let in'),
            'stillOnPhone' => __(':count scans are still on this phone. Upload them before you sign out.'),
            'endAndSignOut' => __('End shift and sign out'),
            'signOutAnyway' => __('Sign out anyway (scans stay on this phone)'),
            'keepScanning' => __('Keep scanning'),
            'undone' => __('Scan undone'),
            'tooLate' => __('Too late to undo: it is more than two minutes old.'),
            'noCamera' => __('Camera not available. Type the family number instead.'),
            'startCamera' => __('Start camera'),
            'noEvent' => __('No event needs a pass right now.'),
            'error' => __('Something went wrong. Try again.'),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#12131f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Maitree Gate">
    <title>{{ __('Gate scanner') }} · {{ __('Gede Maitree Club') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ asset('admin.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/admin-180.png?v=2') }}">
    @vite(['resources/css/app.css', 'resources/js/scan.js'])
</head>
<body class="min-h-dvh bg-night text-paper" data-sw="{{ asset('sw.js') }}">
    <script type="application/json" id="scan-config">@json($config)</script>
    <div class="mx-auto max-w-md min-h-dvh flex flex-col px-5 pt-4 pb-6 relative">
        <header class="flex items-start gap-3">
            <a href="{{ route('admin.dashboard') }}" class="w-10 h-10 -ml-2 grid place-items-center rounded-full hover:bg-white/5 shrink-0" aria-label="{{ __('Console') }}"><x-icon name="arrow-left" class="w-5 h-5" /></a>
            <button type="button" id="evt-btn" class="min-w-0 text-left rounded-lg -m-1 p-1 hover:bg-white/5" aria-label="{{ __('Change event or gate') }}">
                <p class="text-[13px] font-semibold text-marigold flex items-center gap-1"><span id="gate-name"></span><x-icon name="chevron-down" class="w-3.5 h-3.5" /></p>
                <h1 class="font-display text-2xl leading-tight" id="evt-name">{{ $event?->tr('title') ?? __('Gate scanner') }}</h1>
            </button>
            <button type="button" id="sync-btn" class="ml-auto chip bg-white/10 text-paper/90 shrink-0 hover:bg-white/15"></button>
        </header>
        <p class="text-[13px] text-paper/60 mt-1" id="sync-line" role="status"></p>

        @if ($event === null)
            <div class="mt-10 rounded-2xl bg-night-2 p-6 text-center">
                <x-icon name="ticket" class="w-10 h-10 mx-auto text-paper/50" />
                <p class="mt-3 text-lg">{{ __('No event needs a pass right now.') }}</p>
                <a href="{{ route('admin.events.index') }}" class="btn mt-4 bg-white/10 hover:bg-white/15 text-paper">{{ __('Events') }}</a>
            </div>
        @else
            <div class="mt-5 grid grid-cols-3 gap-2.5">
                <div class="rounded-xl bg-night-2 p-3">
                    <p class="text-[12.5px] text-paper/60">{{ __('Inside now') }}</p>
                    <p class="font-display text-[1.7rem] leading-none mt-1 num" id="stat-inside">–</p>
                </div>
                <div class="rounded-xl bg-night-2 p-3">
                    <p class="text-[12.5px] text-paper/60">{{ __('Families') }}</p>
                    <p class="font-display text-[1.7rem] leading-none mt-1 num" id="stat-families">–</p>
                </div>
                <div class="rounded-xl bg-night-2 p-3">
                    <p class="text-[12.5px] text-paper/60">{{ __('Turned away') }}</p>
                    <p class="font-display text-[1.7rem] leading-none mt-1 num" id="stat-denied">–</p>
                </div>
            </div>

            <div class="mt-4 relative aspect-square rounded-3xl overflow-hidden bg-[radial-gradient(ellipse_at_center,#2a2d45_0%,#12131f_75%)]" id="viewfinder" aria-label="{{ __('Camera viewfinder') }}">
                <video id="video" class="absolute inset-0 w-full h-full object-cover" playsinline muted aria-hidden="true"></video>
                <div class="absolute inset-10 pointer-events-none">
                    <span class="absolute top-0 left-0 w-12 h-12 border-t-4 border-l-4 border-marigold rounded-tl-2xl"></span>
                    <span class="absolute top-0 right-0 w-12 h-12 border-t-4 border-r-4 border-marigold rounded-tr-2xl"></span>
                    <span class="absolute bottom-0 left-0 w-12 h-12 border-b-4 border-l-4 border-marigold rounded-bl-2xl"></span>
                    <span class="absolute bottom-0 right-0 w-12 h-12 border-b-4 border-r-4 border-marigold rounded-br-2xl"></span>
                    <span class="scanline absolute inset-x-3 h-0.5 bg-marigold shadow-[0_0_12px_2px_rgba(242,165,22,.6)]"></span>
                </div>
                <div class="absolute top-3 right-3 flex gap-2">
                    <button type="button" id="torch" aria-pressed="false" class="w-11 h-11 rounded-full bg-black/35 grid place-items-center aria-pressed:bg-marigold aria-pressed:text-ink" aria-label="{{ __('Torch') }}" hidden><x-icon name="flashlight" class="w-5 h-5" /></button>
                    <button type="button" id="sound" aria-pressed="true" class="w-11 h-11 rounded-full bg-black/35 grid place-items-center" aria-label="{{ __('Beep on scan') }}"><x-icon name="volume-2" class="w-5 h-5" /></button>
                </div>
                <div class="absolute bottom-4 inset-x-0 text-center px-4">
                    <p class="text-[15px] text-paper/75" id="cam-msg">{{ __('Hold the member card or pass QR in the frame') }}</p>
                    <button type="button" id="cam-start" class="btn mt-2 bg-marigold text-ink" hidden><x-icon name="camera" /><span>{{ __('Start camera') }}</span></button>
                </div>
            </div>

            <button type="button" id="manual" class="btn h-14 mt-4 bg-white/10 hover:bg-white/15 text-paper text-[17px]"><x-icon name="keyboard" /><span>{{ __('Type family number instead') }}</span></button>

            <div class="mt-5">
                <div class="flex items-center mb-2">
                    <p class="text-[12px] font-semibold tracking-wide text-paper/50">{{ __('RECENT SCANS') }}</p>
                    <button type="button" id="undo" class="ml-auto text-[13px] font-semibold text-marigold disabled:opacity-40" disabled>{{ __('Undo last') }}</button>
                </div>
                <ul id="recent" class="rounded-xl bg-night-2 divide-y divide-white/10 text-[14.5px]"></ul>
            </div>

            <div class="mt-6 pt-4 border-t border-white/10">
                <button type="button" id="end-shift" class="btn w-full h-12 bg-transparent border border-white/20 hover:bg-white/10 text-paper"><x-icon name="log-out" /><span>{{ __('End my shift') }}</span></button>
            </div>
        @endif

        <div id="result" class="fixed inset-0 z-50 hidden flex-col items-center justify-center text-center px-8" role="alertdialog" aria-live="assertive" aria-modal="true"></div>
        <div id="sheet" class="fixed inset-0 z-40 hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/60" data-sheet-close></div>
            <div class="absolute inset-x-0 bottom-0 mx-auto max-w-md bg-night-2 rounded-t-3xl p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] max-h-[88dvh] overflow-y-auto rise" id="sheet-body"></div>
        </div>
        <form id="logout-form" method="POST" action="{{ route('admin.logout') }}" hidden>@csrf</form>
    </div>
</body>
</html>
