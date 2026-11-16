<x-layouts.admin :title="__('Audit log')">
    <h1 class="font-display text-[2rem] leading-tight">{{ __('Audit log') }}</h1>
    <p class="mt-1 text-ink-2">{{ __('Every change to the website, who made it and when.') }}</p>
    <div class="mt-6 overflow-x-auto rounded-xl border border-rule bg-card">
        <table class="khata-table">
            <thead><tr><th scope="col">{{ __('When') }}</th><th scope="col">{{ __('Who') }}</th><th scope="col">{{ __('What') }}</th><th scope="col">{{ __('Changes') }}</th></tr></thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td class="num whitespace-nowrap align-top">{{ local_date($activity->created_at, 'j M Y, g:i a') }}</td>
                        <td class="align-top">{{ $activity->causer instanceof \App\Models\User ? $activity->causer->name : __('System') }}</td>
                        <td class="align-top">
                            <span class="font-semibold">{{ $activity->description }}</span>
                            @if ($activity->subject_type)
                                <span class="block text-[13px] text-ink-3">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                            @endif
                        </td>
                        <td class="align-top text-[13px] text-ink-2">
                            @foreach (array_keys((array) ($activity->attribute_changes['attributes'] ?? $activity->properties['attributes'] ?? [])) as $field)
                                <span class="chip h-6 bg-paper-2 mr-1 mb-1">{{ $field }}</span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-ink-2">{{ __('Nothing here yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $activities->links() }}</div>
</x-layouts.admin>
