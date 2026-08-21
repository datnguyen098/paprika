@extends('admin.layouts.app')

@section('title', 'Thêm review')

@section('content')
    <form method="POST" action="{{ route('admin.testimonials.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.testimonials.form')
    </form>
@endsection
