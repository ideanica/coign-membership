            <ol class="journey-timeline">
                @forelse($events as $event)
                <li class="{{ $loop->first ? 'is-latest' : '' }}"><span class="journey-step" style="--status-color:{{ $event->status_color }}">{{ $tracking->events->count() - $loop->index }}</span><div class="journey-event"><div class="journey-event-date"><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->locale(app()->getLocale())->translatedFormat('d F Y') }} <span>{{ $event->created_at->format('H:i') }}</span></time>@if($loop->first)<span class="latest-label">{{ __('TERBARU') }} <x-icon name="spark" size="12" /></span>@endif</div><span class="badge status-badge" style="--status-color:{{ $event->status_color }}">{{ __($event->status_label) }}</span><p>{{ filled($event->note) ? $event->note : __('Status layanan diperbarui oleh tim pengelola.') }}</p></div></li>
                @empty
                <li><span class="journey-step"><x-icon name="calendar" size="18" /></span><div class="journey-event"><h3>{{ __('Menunggu kabar pertama') }}</h3><p>{{ __('Update dari tim akan muncul di sini setelah layanan diproses.') }}</p></div></li>
                @endforelse
            </ol>
