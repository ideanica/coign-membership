<ol class="timeline">
    @forelse($events as $event)
        <li style="--status-color:{{ $event->status_color }}"><span class="timeline-dot" aria-hidden="true"></span><span class="badge status-badge" style="--status-color:{{ $event->status_color }}">{{ $event->status_label }}</span><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</time>
            @if(filled($event->note))<p>{{ $event->note }}</p>@endif
        </li>
    @empty
        <li><span class="timeline-dot" aria-hidden="true"></span><p>Belum ada update.</p></li>
    @endforelse
</ol>
