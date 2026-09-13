<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Services\FreeCourseEnrollmentService;
use App\Services\StorefrontCourseAccessService;
use App\Support\StorefrontCourseAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreeCourseSignupController extends Controller
{
    public function create(Request $request, Product $product): View
    {
        [$product, $price] = $this->availableComplimentary($product, (int) $request->query('price'));
        $user = auth()->user();

        return view('online-course-storefront.free-signup', [
            'product' => $product,
            'price' => $price,
            'termsVersion' => (string) config('legal.terms.current_version'),
            'viewerAccess' => app(StorefrontCourseAccessService::class)
                ->forCourse($user, (int) $product->resource_id),
            'loggedInUser' => $user,
        ]);
    }

    public function store(
        Request $request,
        Product $product,
        FreeCourseEnrollmentService $enrollments
    ): RedirectResponse {
        [$product, $price] = $this->availableComplimentary($product, (int) $request->input('product_price_id'));

        $validated = $request->validate([
            'product_price_id' => ['required', 'integer'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $enrollments->enroll($product, $price, [
            'first_name' => trim($validated['first_name']),
            'last_name' => trim($validated['last_name']),
            'email' => $validated['email'],
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
        ]);

        $enrollment = $result['enrollment'];
        $access = StorefrontCourseAccess::fromEnrollment($enrollment);
        $user = auth()->user();
        $sameEmail = $user && strtolower(trim((string) $user->email)) === $enrollment->email;

        if ($result['already_had_access']) {
            $redirect = $sameEmail && $access->canEnter()
                ? redirect()->to($access->dashboardUrl())
                : redirect()->route('online-courses.catalog.show', $product->slug);

            return $redirect->with('success', 'Ten adres e-mail ma już dostęp do kursu.');
        }

        if ($sameEmail && $access->canEnter()) {
            return redirect()
                ->to($access->dashboardUrl())
                ->with('success', 'Bezpłatny dostęp został nadany.');
        }

        $message = $access->state === StorefrontCourseAccess::STATE_SCHEDULED
            ? 'Zapis został przyjęty. Dostęp otworzy się '.$access->startsLabel().'. Sprawdź skrzynkę e-mail.'
            : 'Zapis został przyjęty. Sprawdź skrzynkę e-mail, aby wejść do kursu.';

        return redirect()
            ->route('online-courses.catalog.show', $product->slug)
            ->with('success', $message);
    }

    /**
     * @return array{0: Product, 1: ProductPrice}
     */
    private function availableComplimentary(Product $product, int $priceId): array
    {
        abort_unless(
            Product::query()
                ->whereKey($product->id)
                ->catalogOnlineCourses()
                ->whereHas('defaultOffer', fn ($offer) => $offer->where('is_active', true))
                ->exists(),
            404
        );

        $product->load(['onlineCourse', 'defaultOffer']);
        $price = ProductPrice::query()
            ->whereKey($priceId)
            ->where('product_offer_id', $product->defaultOffer?->id)
            ->where('is_active', true)
            ->first();
        abort_unless($price instanceof ProductPrice && $price->isComplimentary(), 404);

        return [$product, $price];
    }
}
