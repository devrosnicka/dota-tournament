<?php

namespace App\Http\Middleware;

use App\Http\AdminSession;
use App\Models\Player;
use App\Tournament\TournamentSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly TournamentSettings $settings) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $phase = $this->settings->phase();
        $player = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'phase' => [
                'value' => $phase->value,
                'label' => $phase->label(),
            ],
            'auth' => [
                'player' => $player instanceof Player ? ['id' => $player->id, 'nick' => $player->nick] : null,
                'isAdmin' => AdminSession::check($request),
            ],
        ];
    }
}
