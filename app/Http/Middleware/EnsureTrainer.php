<?php

namespace App\Http\Middleware;

use App\Models\Instructor;
use App\Support\TrainerImpersonation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainer
{
    public function handle(Request $request, Closure $next): Response
    {
        $preview = TrainerImpersonation::previewInstructor();
        if ($preview !== null) {
            $request->attributes->set('trainerInstructor', $preview);

            return $next($request);
        }

        $instructor = Instructor::query()
            ->where('pnedu_user_id', $request->user()?->id)
            ->where('portal_access_enabled', true)
            ->first();

        if ($instructor === null) {
            abort(403, 'Panel trenera jest wyłączony.');
        }

        $request->attributes->set('trainerInstructor', $instructor);

        return $next($request);
    }
}
