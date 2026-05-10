@extends('admin.layouts.app')

@section('page-title', 'إضافة بنر')
@section('page-subtitle', 'أنشئ بنر جديد لواجهة الصفحة الرئيسية')

@section('content')
<form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
    @include('admin.banners._form')
</form>
@endsection
