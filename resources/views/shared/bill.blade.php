@extends('layouts.portal')
@section('title', __('Detail bill'))
@section('description', __('Bill diterbitkan dari pesanan. Harga dan rincian tagihan tersimpan saat penerbitan.'))
@push('styles')<link rel="stylesheet" href="{{ asset('css/bill.css') }}?v={{ filemtime(public_path('css/bill.css')) }}">@endpush
@section('actions')<button class="button button-neutral" type="button" data-print><x-icon name="download" size="16" /> {{ __('Cetak / simpan PDF') }}</button>@endsection
@section('content')
@include('shared.bill-content')
@endsection
