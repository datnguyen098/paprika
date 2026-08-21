@extends('admin.layouts.app')

@section('title', 'Thêm ảnh không gian')

@section('content')
    <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.gallery.form')
    </form>
@endsection
