<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header) {
            return response()->setStatusCode(401)
                ->setJSON(['message' => 'Token required']);
        }

        try {
            $token = explode(' ', $header)[1];
            JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
        } catch (\Exception $e) {
            return response()->setStatusCode(401)
                ->setJSON(['message' => 'Invalid token']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
