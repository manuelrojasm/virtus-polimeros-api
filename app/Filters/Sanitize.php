<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class Sanitize implements FilterInterface
{
    /**
     * Claves que no deben ser alteradas (además de trim), típicamente credenciales
     * donde strip_tags podría cambiar el valor y romper la verificación de hash.
     */
    private const RAW_KEYS = ['contraseña', 'password', 'contrasena'];

    public function before(RequestInterface $request, $arguments = null)
    {
        $raw = $request->getBody();
        if ($raw === null || $raw === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return;
        }

        $sanitized = $this->sanitizeValue($decoded);
        $request->setBody(json_encode($sanitized));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function sanitizeValue($value, ?string $key = null)
    {
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->sanitizeValue($v, is_string($k) ? $k : null);
            }
            return $result;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Elimina bytes nulos, que no aportan valor y pueden usarse para truncar strings en C/DB drivers.
        $value = str_replace("\0", '', $value);

        if ($key !== null && in_array(mb_strtolower($key), self::RAW_KEYS, true)) {
            return $value;
        }

        return trim(strip_tags($value));
    }
}
