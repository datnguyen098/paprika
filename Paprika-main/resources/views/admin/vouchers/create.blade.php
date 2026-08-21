@extends('admin.layouts.app')

@section('title', 'Thêm voucher')

@section('content')
    <form method="POST" action="{{ route('admin.vouchers.store') }}" class="admin-form-card">
        @csrf
        @include('admin.vouchers.form')
    </form>
@endsection
