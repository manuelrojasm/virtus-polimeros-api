<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Cors implements FilterInterface
{
    /**
     * Orígenes de producción permitidos explícitamente.
     */
    private const ALLOWED_ORIGINS = [
        'https://bioplastic.info',
        'https://www.bioplastic.info',
        'https://api.bioplastic.info',
    ];

    /**
     * Orígenes de desarrollo/pruebas: localhost o 127.0.0.1 en cualquier puerto.
     */
    private const ALLOWED_ORIGIN_PATTERN = '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i';

    public function before(RequestInterface $request, $arguments = null)
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin !== '' && $this->isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
        }

        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        if ($request->getMethod() === 'options') {
            exit(0);
        }
    }

    private function isOriginAllowed(string $origin): bool
    {
        return in_array($origin, self::ALLOWED_ORIGINS, true)
            || preg_match(self::ALLOWED_ORIGIN_PATTERN, $origin) === 1;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}