<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('password can be updated', function () {
    $user = User::factory()->create(['password_set_at' => now()]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    $this->assertNotNull($user->refresh()->password_set_at);
});

test('password can be set without current password when no local password exists', function () {
    $user = User::factory()->create(['password_set_at' => null]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $freshUser = $user->refresh();

    $this->assertTrue(Hash::check('new-password', $freshUser->password));
    $this->assertNotNull($freshUser->password_set_at);
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create(['password_set_at' => now()]);

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $component
        ->assertHasErrors(['current_password'])
        ->assertNoRedirect();
});
