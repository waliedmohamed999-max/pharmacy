@extends('store.layouts.app')

@section('content')
<div class="neo-card p-8 text-center">
    <h1 class="text-2xl font-bold mb-2">تم استلام طلبك بنجاح</h1>
    <p>سيتم التواصل معك قريبًا لتأكيد الطلب.</p>
    <a href="{{ route('store.home') }}" class="neo-btn mt-4 inline-block">العودة للمتجر</a>
</div>
@endsection
