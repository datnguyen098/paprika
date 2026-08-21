@extends('admin.layouts.app')

@section('title', 'Thêm trang tĩnh')

@section('content')
    <form method="POST" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.pages.form')
    </form>
@endsection
