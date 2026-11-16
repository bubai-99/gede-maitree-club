@props(['published'])
<span {{ $attributes->merge(['class' => 'chip '.($published ? 'bg-tulsi-wash text-tulsi' : 'bg-paper-2 text-ink-2')]) }}>
    <span class="w-2 h-2 rounded-full {{ $published ? 'bg-tulsi' : 'bg-ink-3' }}" aria-hidden="true"></span>{{ $published ? __('Published') : __('Draft') }}
</span>
