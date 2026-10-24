@props(['settings' => [], 'package' => null, 'label' => 'Discuss Your Project'])
@php
    $message = $package ? __('Halo, saya ingin diskusi paket Web Development :package.', ['package' => $package]) : __('Halo, saya ingin diskusi proyek Web Development untuk bisnis saya.');
    $whatsapp = preg_replace('/\D/', '', $settings['support_whatsapp'] ?? '');
    $email = $settings['support_email'] ?? '';
    $contactUrl = $whatsapp !== '' ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message) : (filled($email) ? 'mailto:'.$email.'?subject='.rawurlencode($message) : route('home').'#kontak');
@endphp
<a {{ $attributes->class(['button'])->merge(['href' => $contactUrl]) }} @if($whatsapp !== '') target="_blank" rel="noopener noreferrer" @endif>{{ __($label, ['package' => $package]) }} <x-icon name="arrow-up-right" size="17" />@if($whatsapp !== '')<span class="sr-only">{{ __('Buka di tab baru') }}</span>@endif</a>
