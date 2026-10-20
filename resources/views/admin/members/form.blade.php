@extends('layouts.portal')
@section('title', $member->exists ? 'Edit akun member' : 'Buat akun member')
@section('description', 'Kelola identitas, akses akun, dan kata sandi member.')
@include('admin.workspace-assets')
@section('content')
    @include('admin.members.member-form')
@endsection
