@extends('admin.layouts.app')

@section('title', 'Sửa review')

@section('content')
    <form method="POST" action="{{ route('admin.testimonials.update', $testimonial) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.testimonials.form')
    </form>
@endsection
