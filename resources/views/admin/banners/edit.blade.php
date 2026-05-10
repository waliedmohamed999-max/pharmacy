@extends('admin.layouts.app')

@section('page-title', 'تعديل بنر')
@section('page-subtitle', 'تحديث صورة البنر والربط الزمني')

@section('content')
<form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.banners._form')
</form>
@endsection
