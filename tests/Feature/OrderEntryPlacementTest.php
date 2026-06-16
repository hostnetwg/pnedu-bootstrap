<?php

namespace Tests\Feature;

use App\Services\OrderEntryPlacementService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class OrderEntryPlacementTest extends TestCase
{
    public function test_entry_query_stores_placement_bound_to_course(): void
    {
        $service = new OrderEntryPlacementService;
        $request = $this->makeCourseRequest(42, 'dashboard_sidebar');

        $service->captureFromRequest($request);

        $this->assertSame('dashboard_sidebar', $service->resolveForCourse($request, 42));
        $this->assertNull($service->resolveForCourse($request, 99));
    }

    public function test_unknown_entry_is_ignored(): void
    {
        $service = new OrderEntryPlacementService;
        $request = $this->makeCourseRequest(10, 'unknown_placement');

        $service->captureFromRequest($request);

        $this->assertNull($service->resolveForCourse($request, 10));
    }

    public function test_hidden_field_overrides_session_for_matching_course(): void
    {
        $service = new OrderEntryPlacementService;
        $request = $this->makeCourseRequest(5, 'dashboard_sidebar');
        $service->captureFromRequest($request);

        $resolved = $service->resolveForCourse($request, 5, 'dashboard_sidebar');

        $this->assertSame('dashboard_sidebar', $resolved);
    }

    public function test_clear_removes_session_placement(): void
    {
        $service = new OrderEntryPlacementService;
        $request = $this->makeCourseRequest(7, 'dashboard_sidebar');
        $service->captureFromRequest($request);

        $service->clear($request);

        $this->assertNull($service->resolveForCourse($request, 7));
    }

    private function makeCourseRequest(int $courseId, ?string $entry): Request
    {
        $query = $entry !== null ? ['entry' => $entry] : [];
        $request = Request::create("/courses/{$courseId}", 'GET', $query);

        $session = new Store('test', new ArraySessionHandler(60));
        $session->start();
        $request->setLaravelSession($session);

        $route = new Route('GET', '/courses/{id}', []);
        $route->bind($request);
        $route->setParameter('id', (string) $courseId);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
