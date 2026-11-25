<?php

namespace App\Http\Controllers;

use App\Models\Instructor;
use App\Support\TrainerImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrainerImpersonationController extends Controller
{
    public function start(Request $request, int $instructor): RedirectResponse
    {
        abort_unless(TrainerImpersonation::isPanelAdmin($request->user()), 403);

        $row = Instructor::query()
            ->whereKey($instructor)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $request->session()->put(TrainerImpersonation::SESSION_KEY, $row->id);

        Log::info('Trainer panel preview started', [
            'admin_user_id' => $request->user()->id,
            'instructor_id' => $row->id,
        ]);

        return redirect()
            ->route('trainer.index')
            ->with('success', 'Podgląd panelu: '.$row->full_name.'.');
    }

    public function stop(Request $request): RedirectResponse
    {
        abort_unless(TrainerImpersonation::isPanelAdmin($request->user()), 403);

        $request->session()->forget(TrainerImpersonation::SESSION_KEY);

        return redirect()
            ->route('home')
            ->with('success', 'Wróciłeś do swojego konta.');
    }
}
