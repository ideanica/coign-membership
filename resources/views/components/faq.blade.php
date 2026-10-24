@props(['items'])
<div {{ $attributes->class(['web-faq']) }}>
    @foreach($items as $question => $answer)<details class="card"><summary>{{ __($question) }}</summary><p>{{ __($answer) }}</p></details>@endforeach
</div>
