<?php

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    // Dashboard route requires auth + verified + team + permission:dashboard.
    // Per CLAUDE.md pin #15, teams with no connections get redirected to
    // /connections — so we also need to attach an active Page via makeEmailPage().
    [$user, $team] = makeUserWithTeam();
    makeEmailPage($team);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});