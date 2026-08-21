@extends('admin.layouts.app')

@section('title', 'Thêm khuyến mãi')

@section('content')
    <form method="POST" action="{{ route('admin.promotions.store') }}" enctype="multipart/form-data" class="admin-form-card">
        @csrf
        @include('admin.promotions.form')
    </form>
@endsection
