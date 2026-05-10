@extends('admin.layouts.app')

@section('page-title', 'إضافة صفحة')
@section('page-subtitle', 'أنشئ صفحة محتوى جديدة تظهر للعملاء')

@section('content')
<form method="POST" action="{{ route('admin.pages.store') }}">
    @include('admin.pages._form')
</form>
@endsection
