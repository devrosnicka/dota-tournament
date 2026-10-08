<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tournament\Exceptions\PhaseTransitionBlocked;
use App\Tournament\PhaseManager;
use Illuminate\Http\RedirectResponse;

class PhaseController extends Controller
{
    public function advance(PhaseManager $phases): RedirectResponse
    {
        try {
            $phase = $phases->advance();
            $this->toast('Turnaj je ve fázi: '.$phase->label());
        } catch (PhaseTransitionBlocked $e) {
            $this->toast($e->getMessage(), 'error');
        }

        return to_route('admin.dashboard');
    }

    public function revert(PhaseManager $phases): RedirectResponse
    {
        try {
            $phase = $phases->revert();
            $this->toast('Turnaj se vrátil do fáze: '.$phase->label());
        } catch (PhaseTransitionBlocked $e) {
            $this->toast($e->getMessage(), 'error');
        }

        return to_route('admin.dashboard');
    }
}
