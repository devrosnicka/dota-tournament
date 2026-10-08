<?php

use App\Enums\Phase;
use App\Models\Player;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * GET URLs of all app routes, with route parameters pointing at player #1.
 *
 * @return list<string>
 */
function appGetUrls(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => in_array('GET', $route->methods(), true)
            && str_starts_with($route->getActionName(), 'App\\'))
        ->map(fn (RoutingRoute $route) => '/'.ltrim(preg_replace('/\{[^}]+\}/', '1', $route->uri()), '/'))
        ->values()
        ->all();
}

it('shows guests only registration and login', function () {
    Player::factory()->create();

    $public = ['/register', '/login', '/admin/login'];

    // The TV view needs its key: without it there is nothing to see.
    $this->get('/tv?key=tv-key')->assertOk();

    foreach (appGetUrls() as $url) {
        $response = $this->get($url);

        if (in_array($url, $public, true)) {
            $response->assertOk();
        } else {
            expect($response->status())->toBeIn([302, 404], "Guest can see {$url}");
        }
    }
});

it('keeps players out of the admin area', function () {
    $this->actingAs(Player::factory()->create());

    foreach (appGetUrls() as $url) {
        if (str_starts_with($url, '/admin') && $url !== '/admin/login') {
            $this->get($url)->assertRedirect('/admin/login');
        }
    }
});

it('sends guests to registration while it is open, otherwise to login', function () {
    $this->get('/')->assertRedirect('/register');

    setPhase(Phase::Ranking);

    $this->get('/')->assertRedirect('/login');
});
