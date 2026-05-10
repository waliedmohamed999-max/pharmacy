@extends('admin.layouts.app')

@section('page-title', 'إضافة منتج')
@section('page-subtitle', 'أدخل بيانات المنتج وصوره والأسعار')

@section('content')
<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @include('admin.products._form')
</form>
@endsection
