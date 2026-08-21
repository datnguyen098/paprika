@extends('admin.layouts.app')

@section('title', 'Thêm banner')

@section('content')
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.banners.form')
    </form>
@endsection
