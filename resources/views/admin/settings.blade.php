@php($canEdit = auth()->user()?->can('create', \App\Models\SiteSetting::class))
<x-layouts.admin :title="__('Club settings')">
    <div class="max-w-[960px]">
        <h1 class="font-display text-[2rem] leading-tight">{{ __('Club settings') }}</h1>
        @unless ($canEdit)
            <p class="mt-3 rounded-lg bg-marigold-wash text-turmeric px-4 py-3 text-[14px]">{{ __('You can look but not change this. Only the Admin and the Website editor edit the website.') }}</p>
        @endunless

        <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-6">
            @csrf
            @method('PUT')
            <fieldset @disabled(! $canEdit) class="space-y-10">
                <section class="card p-6 space-y-4" aria-labelledby="contact-h">
                    <h2 id="contact-h" class="font-display text-[1.5rem]">{{ __('Contact details') }}</h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <x-field name="contact[address]" :label="__('Address (English)')" :value="$club['address']" required />
                        <x-field name="contact[address_bn]" :label="__('Address (Bengali)')" :value="$club['address_bn']" lang="bn" />
                        <x-field name="contact[hours]" :label="__('Opening hours (English)')" :value="$club['hours']" />
                        <x-field name="contact[hours_bn]" :label="__('Opening hours (Bengali)')" :value="$club['hours_bn']" lang="bn" />
                        <x-field name="contact[phone]" type="tel" :label="__('Phone')" :value="$club['phone']" required />
                        <x-field name="contact[whatsapp]" :label="__('WhatsApp number with 91')" :value="$club['whatsapp']" inputmode="numeric" required />
                        <x-field name="contact[email]" type="email" :label="__('Email')" :value="$club['email']" />
                        <x-field name="contact[upi]" :label="__('UPI ID')" :value="$club['upi']" />
                        <x-field name="contact[facebook]" type="url" label="Facebook" :value="$club['social']['facebook'] ?? ''" />
                        <x-field name="contact[instagram]" type="url" label="Instagram" :value="$club['social']['instagram'] ?? ''" />
                        <x-field name="contact[youtube]" type="url" label="YouTube" :value="$club['social']['youtube'] ?? ''" />
                    </div>
                </section>

                <section class="card p-6" aria-labelledby="menu-h">
                    <h2 id="menu-h" class="font-display text-[1.5rem]">{{ __('Website menu') }}</h2>
                    <p class="text-ink-2 text-[14px]">{{ __('Show or hide links in the top menu, rename them and change their order.') }}</p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="khata-table">
                            <thead><tr><th scope="col">{{ __('Show') }}</th><th scope="col">{{ __('Page') }}</th><th scope="col">{{ __('Name (English)') }}</th><th scope="col">{{ __('Name (Bengali)') }}</th><th scope="col">{{ __('Order') }}</th></tr></thead>
                            <tbody>
                                @foreach ($menuItems as $position => $item)
                                    @php($saved = $menu[$item['route']] ?? [])
                                    <tr>
                                        <td><input type="hidden" name="menu[{{ $position }}][route]" value="{{ $item['route'] }}"><input type="hidden" name="menu[{{ $position }}][show]" value="0"><input type="checkbox" name="menu[{{ $position }}][show]" value="1" class="w-4 h-4 accent-sindoor" aria-label="{{ __('Show :page', ['page' => __($item['label'])]) }}" @checked($saved['show'] ?? true)></td>
                                        <td class="font-semibold">{{ __($item['label']) }}</td>
                                        <td><input name="menu[{{ $position }}][label]" class="field h-9" value="{{ $saved['label'] ?? '' }}" placeholder="{{ __($item['label'], [], 'en') }}" aria-label="{{ __('Name (English)') }}" maxlength="40"></td>
                                        <td><input name="menu[{{ $position }}][label_bn]" class="field h-9" value="{{ $saved['label_bn'] ?? '' }}" placeholder="{{ __($item['label'], [], 'bn') }}" aria-label="{{ __('Name (Bengali)') }}" lang="bn" maxlength="40"></td>
                                        <td><input type="number" name="menu[{{ $position }}][sort]" class="field h-9 w-20" value="{{ $saved['sort'] ?? $position }}" min="0" max="99" aria-label="{{ __('Order') }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card p-6 space-y-5" aria-labelledby="seo-h">
                    <h2 id="seo-h" class="font-display text-[1.5rem]">{{ __('Search engines') }}</h2>
                    <p class="text-ink-2 text-[14px]">{{ __('The title and description Google and WhatsApp show for each page. Leave blank to keep the default.') }}</p>
                    @foreach ($seoPages as $page => $defaults)
                        <details class="border-t border-rule pt-4">
                            <summary class="font-semibold cursor-pointer">{{ local_text($defaults['label'], $defaults['label_bn']) }}</summary>
                            <div class="mt-3 grid sm:grid-cols-2 gap-4">
                                <x-field :name="'seo['.$page.'][title]'" :label="__('Title (English)')" :value="$seo[$page]['title'] ?? ''" :placeholder="$defaults['title']" maxlength="70" />
                                <x-field :name="'seo['.$page.'][title_bn]'" :label="__('Title (Bengali)')" :value="$seo[$page]['title_bn'] ?? ''" :placeholder="$defaults['title_bn']" maxlength="70" lang="bn" />
                                <x-field :name="'seo['.$page.'][description]'" :label="__('Description (English)')" :value="$seo[$page]['description'] ?? ''" :placeholder="$defaults['description']" textarea rows="2" maxlength="300" />
                                <x-field :name="'seo['.$page.'][description_bn]'" :label="__('Description (Bengali)')" :value="$seo[$page]['description_bn'] ?? ''" :placeholder="$defaults['description_bn']" textarea rows="2" maxlength="300" lang="bn" />
                            </div>
                        </details>
                    @endforeach
                </section>
            </fieldset>
            @if ($canEdit)
                <div class="mt-8 sticky bottom-20 lg:bottom-4 bg-paper/95 py-3"><button type="submit" class="btn btn-primary">{{ __('Save settings') }}</button></div>
            @endif
        </form>
    </div>
</x-layouts.admin>
