<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StorefrontCourseAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnlineCourseStorefrontController extends Controller
{
    public function index(StorefrontCourseAccessService $access): View
    {
        $products = Product::query()
            ->catalogOnlineCourses()
            ->with([
                'onlineCourse.instructor',
                'defaultOffer.activePrices',
            ])
            ->orderBy('name')
            ->paginate(12);

        $accessByCourseId = $access->mapForUser(
            Auth::user(),
            $products->pluck('resource_id')
        );

        return view('online-course-storefront.index', compact('products', 'accessByCourseId'));
    }

    public function show(Product $product, StorefrontCourseAccessService $access): View
    {
        abort_unless(
            Product::query()
                ->whereKey($product->id)
                ->catalogOnlineCourses()
                ->exists(),
            404
        );

        $product->load([
            'onlineCourse.instructor',
            'onlineCourse.modules.lessons',
            'defaultOffer.activePrices',
        ]);

        $viewerAccess = $access->forCourse(Auth::user(), $product->resource_id);

        return view('online-course-storefront.show', compact('product', 'viewerAccess'));
    }
}
