<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use RuntimeException;
use Throwable;

/**
 * Cliente delgado para la API de Mensajes de Anthropic (Claude). No requiere SDK:
 * usa Guzzle (ya es dependencia del proyecto) contra https://api.anthropic.com/v1/messages.
 */
class AnthropicService
{
    private const API_URL          = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION      = '2023-06-01';
    private const HTTP_TIMEOUT     = 120;
    private const REINTENTOS_RATE_LIMIT = [5, 15];

    protected string $apiKey;
    protected string $model;
    protected Client $httpClient;

    public function __construct()
    {
        $this->apiKey = env('ANTHROPIC_API_KEY') ?: (string) getenv('ANTHROPIC_API_KEY');
        $this->model  = env('ANTHROPIC_MODEL') ?: 'claude-sonnet-5';
        $this->httpClient = $this->crearHttpClient();
    }

    /**
     * Envía un mensaje de usuario a Claude y devuelve el texto de la respuesta.
     *
     * @throws RuntimeException Si la API key no está configurada o la llamada falla.
     */
    public function enviarMensaje(string $prompt, int $maxTokens = 4096): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('ANTHROPIC_API_KEY no está configurada en el entorno.');
        }

        $reintentos = self::REINTENTOS_RATE_LIMIT;
        $ultimaEx   = null;

        for ($intento = 0; $intento <= count($reintentos); $intento++) {
            try {
                $response = $this->httpClient->post(self::API_URL, [
                    'headers' => [
                        'x-api-key'         => $this->apiKey,
                        'anthropic-version' => self::API_VERSION,
                        'content-type'      => 'application/json',
                    ],
                    'json' => [
                        'model'      => $this->model,
                        'max_tokens' => $maxTokens,
                        'messages'   => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ],
                ]);

                $body    = json_decode((string) $response->getBody(), true);
                $bloques = $body['content'] ?? [];
                $texto   = '';
                foreach ($bloques as $bloque) {
                    if (($bloque['type'] ?? '') === 'text') {
                        $texto .= $bloque['text'] ?? '';
                    }
                }

                return $texto;
            } catch (RequestException $e) {
                $status = $e->getResponse()?->getStatusCode();
                if ($status === 429 && $intento < count($reintentos)) {
                    $ultimaEx = $e;
                    sleep($reintentos[$intento]);
                    continue;
                }
                throw new RuntimeException('Error al llamar a la API de Anthropic: ' . $e->getMessage(), 0, $e);
            } catch (Throwable $e) {
                throw new RuntimeException('Error al llamar a la API de Anthropic: ' . $e->getMessage(), 0, $e);
            }
        }

        throw new RuntimeException(
            'Rate limit excedido tras reintentos.',
            0,
            $ultimaEx instanceof Throwable ? $ultimaEx : null
        );
    }

    protected function crearHttpClient(): Client
    {
        $config = ['timeout' => self::HTTP_TIMEOUT];

        $desactivarVerify = (defined('ENVIRONMENT') && ENVIRONMENT === 'development')
            || (env('ANTHROPIC_SSL_VERIFY') !== null && filter_var(env('ANTHROPIC_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN) === false);

        if ($desactivarVerify) {
            $config['verify'] = false;
        }

        return new Client($config);
    }
}
