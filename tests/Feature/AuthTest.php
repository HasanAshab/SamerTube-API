<?php

test('Guest can register manually', function () {
  $data = [
    'name' => 'xyz',
    'email' => 'hostilarysten@gmail.com',
    'password' => 'password'
  ];
  $response = $this->postJson('/api/auth/register', $data);
  $response->assertStatus(200);
});
