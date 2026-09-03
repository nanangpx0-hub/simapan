<?php

declare(strict_types=1);

use App\Models\User;

test('halaman login dapat dibuka', function (): void {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('user dummy dapat login', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('dashboard mengharuskan autentikasi', function (): void {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('dashboard tampil untuk user login', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('SIMAPAN');
});

test('user dapat logout', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
