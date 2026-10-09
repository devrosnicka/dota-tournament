<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tournament\TournamentReset;
use Illuminate\Http\RedirectResponse;

class ResetController extends Controller
{
    public function __invoke(TournamentReset $reset): RedirectResponse
    {
        $reset->run();
        $this->toast('Turnaj je smazaný a začíná znovu registrací.');

        return to_route('admin.dashboard');
    }
}
