@props(['placeholder' => 'Cari data', 'label' => 'Cari data'])
<div class="admin-search-controls">
    <div class="admin-search-input">
        <x-icon name="search" size="16" />
        <label class="sr-only" for="q">{{ $label }}</label>
        <input type="search" id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="{{ $placeholder }}">
        @if(request()->filled('q'))<a class="admin-search-clear" href="{{ request()->fullUrlWithoutQuery(['q', 'page']) }}" aria-label="Hapus pencarian" title="Hapus pencarian"><x-icon name="close" size="16" /></a>@endif
    </div>
    <button class="button button-neutral admin-search-submit" type="submit" aria-label="Cari"><x-icon name="search" size="16" /><span>Cari</span></button>
    {{ $slot }}
</div>
