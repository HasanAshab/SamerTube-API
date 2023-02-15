<?php

test('Register manually', function () {
  $data = [
    'name' => 'xyz',
    'email' => 'hostilarysten@gmail.com',
    'password' => 'password',
    'password_confirmation' => 'password'
  ];
  $response = $this->postJson('/api/auth/register', $data);
  $response->assertStatus(200);
});
