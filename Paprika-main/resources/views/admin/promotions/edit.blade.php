@extends('admin.layouts.app')

@section('title', 'Sửa khuyến mãi')

@section('content')
    <form method="POST" action="{{ route('admin.promotions.update', $promotion) }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @method('PUT')
        @include('admin.promotions.form')
    </form>
@endsection
