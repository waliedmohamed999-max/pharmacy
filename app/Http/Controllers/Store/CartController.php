<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CartAddRequest;
use App\Http\Requests\Store\CartCouponRequest;
use App\Http\Requests\Store\CartUpdateRequest;
use App\Models\Product;
use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function index()
    {
        return view('store.cart', $this->cart->summary());
    }

    public function add(CartAddRequest $request)
    {
        $product = Product::findOrFail($request->integer('product_id'));
        $qty = max(1, $request->integer('qty', 1));

        if (!$product->is_active) {
            return back()->with('error', 'المنتج غير متاح حاليًا');
        }

        $result = $this->cart->add($product, $qty);
        if (!$result['ok']) {
            return back()->with('error', $result['message'] ?? 'تعذر الإضافة للسلة.');
        }

        return back()->with('success', 'تمت إضافة المنتج إلى السلة');
    }

    public function update(CartUpdateRequest $request, string $rowId)
    {
        $result = $this->cart->update($rowId, max(0, $request->integer('qty', 1)));
        if (!$result['ok']) {
            return back()->with('error', $result['message'] ?? 'تعذر تحديث السلة.');
        }

        return back()->with('success', 'تم تحديث كمية المنتج');
    }

    public function remove(string $rowId)
    {
        $this->cart->remove($rowId);

        return back()->with('success', 'تم حذف المنتج من السلة');
    }

    public function applyCoupon(CartCouponRequest $request)
    {
        $result = $this->cart->applyCoupon($request->string('code')->value());
        if (!$result['ok']) {
            return back()->with('error', $result['message'] ?? 'تعذر تطبيق كود الخصم.');
        }

        return back()->with('success', $result['message'] ?? 'تم تطبيق كود الخصم.');
    }

    public function removeCoupon()
    {
        $this->cart->removeCoupon();

        return back()->with('success', 'تم حذف كود الخصم.');
    }
}

