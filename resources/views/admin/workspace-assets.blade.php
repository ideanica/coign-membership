@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-workspace.css') }}?v={{ filemtime(public_path('css/admin-workspace.css')) }}">@endpush
@push('scripts')
    <script src="{{ asset('js/order-detail.js') }}?v={{ filemtime(public_path('js/order-detail.js')) }}" defer></script>
    <script src="{{ asset('js/admin-workspace.js') }}?v={{ filemtime(public_path('js/admin-workspace.js')) }}" defer></script>
@endpush
