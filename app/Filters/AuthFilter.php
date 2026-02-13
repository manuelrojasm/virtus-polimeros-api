<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorized('Token no proporcionado');
        }

        $token = trim($matches[1]);
        $key = getenv('JWT_SECRET');
        if (empty($key)) {
            log_message('error', 'AuthFilter: JWT_SECRET no está configurado en .env');
            return $this->unauthorized('Configuración de autenticación inválida');
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $request->authUser = (array) $decoded;
            return $request;
        } catch (ExpiredException $e) {
            return $this->unauthorized('Token expirado');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Token inválido');
        } catch (\Exception $e) {
            log_message('error', 'AuthFilter: ' . $e->getMessage());
            return $this->unauthorized('Token inválido');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function unauthorized(string $message)
    {
        return \Config\Services::response()
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'message' => $message,
            ])
            ->setHeader('Content-Type', 'application/json');
    }
}
