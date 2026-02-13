<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    /**
     * Verifica que el usuario autenticado tenga idRol = 2 (admin).
     * Debe ejecutarse después de AuthFilter para que $request->authUser exista.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = $request->authUser ?? null;
        if (!$user || (int) ($user['idRol'] ?? 0) !== 2) {
            return \Config\Services::response()
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'Acceso denegado. Se requiere rol de administrador.',
                ])
                ->setHeader('Content-Type', 'application/json');
        }
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
