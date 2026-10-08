<?php

it('logs the admin in with the right password', function () {
    $this->post('/admin/login', ['password' => 'secret-admin'])
        ->assertRedirect('/admin');

    $this->get('/admin')->assertOk();
});

it('rejects a wrong password', function () {
    $this->post('/admin/login', ['password' => 'nope'])
        ->assertSessionHasErrors('password');

    $this->get('/admin')->assertRedirect('/admin/login');
});

it('cannot log in when no admin password is configured', function () {
    config(['tournament.admin_password' => '']);

    $this->post('/admin/login', ['password' => ''])->assertSessionHasErrors('password');
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('rate limits admin login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post('/admin/login', ['password' => 'nope']);
    }

    $this->post('/admin/login', ['password' => 'secret-admin'])
        ->assertSessionHasErrors('password');
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('logs the admin out', function () {
    asAdmin()->post('/admin/logout')->assertRedirect('/admin/login');

    $this->get('/admin')->assertRedirect('/admin/login');
});
