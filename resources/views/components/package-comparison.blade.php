@props(['rows'])
<div class="card web-comparison-scroll" role="region" aria-label="{{ __('Perbandingan paket, geser untuk melihat semua kolom') }}" tabindex="0">
    <table class="web-comparison">
        <caption class="sr-only">{{ __('Perbandingan fitur paket Web Development') }}</caption>
        <thead><tr><th scope="col">Feature</th><th scope="col">Starter</th><th scope="col" class="web-business-column">Business</th><th scope="col">Business Pro</th><th scope="col">Custom System</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><th scope="row">{{ __($row[0]) }}</th>@foreach(array_slice($row, 1) as $value)<td @class(['web-business-column' => $loop->index === 1])>@if($value === '✓')<span aria-hidden="true">✓</span><span class="sr-only">{{ __('Tersedia') }}</span>@elseif($value === '—')<span aria-hidden="true">—</span><span class="sr-only">{{ __('Tidak termasuk') }}</span>@else{{ __($value) }}@endif</td>@endforeach</tr>@endforeach</tbody>
    </table>
</div>
