<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;

class AuthController extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        $payload = [
            'iss' => 'nutrition-service',
            'iat' => time(),
            'exp' => time() + 3600,
            'role' => 'client'
        ];

        $token = JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');

        return $this->response->setJSON([
            'token' => $token
        ]);
    }
}
