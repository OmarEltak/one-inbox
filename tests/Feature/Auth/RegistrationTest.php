<?php

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Phase A wires new signups to the Meet-Your-AI onboarding wizard
    // instead of the raw dashboard so the first minute has real payoff.
    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('onboarding.meet-your-ai', absolute: false));

    $this->assertAuthenticated();
});