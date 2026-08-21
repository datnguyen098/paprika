@extends('admin.layouts.app')

@section('title', 'Sửa banner')

@section('content')
    <form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.banners.form')
    </form>
@endsection
