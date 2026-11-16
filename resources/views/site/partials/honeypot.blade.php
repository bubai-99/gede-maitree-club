{{-- Spam trap: hidden from people and screen readers; bots that fill it in are turned away. --}}
<div class="hidden" aria-hidden="true">
    <label for="website-{{ $form ?? 'form' }}">{{ __('Leave this empty') }}</label>
    <input type="text" id="website-{{ $form ?? 'form' }}" name="website" value="" tabindex="-1" autocomplete="off">
</div>
