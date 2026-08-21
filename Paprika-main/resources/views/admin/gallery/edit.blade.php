@extends('admin.layouts.app')

@section('title', 'Sửa ảnh không gian')

@section('content')
    <form method="POST" action="{{ route('admin.gallery.update', $galleryImage) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.gallery.form')
    </form>
@endsection
