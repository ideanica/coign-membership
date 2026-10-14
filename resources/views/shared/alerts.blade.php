@if(session('success'))<div class="portal-alert success" role="status"><x-icon name="check" size="18" /> {{ session('success') }}</div>@endif
@if($errors->any())<div class="portal-alert error" role="alert"><strong>Periksa kembali datamu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
