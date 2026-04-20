<?php

namespace App\Services;

use App\Models\SeccionCursoModel;
use Http\Adapter\Guzzle7\Client as Guzzle7Adapter;
use OpenAI;
use OpenAI\Exceptions\RateLimitException;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Throwable;

/**
 * Servicio que genera preguntas sugeridas por PDF usando OpenAI.
 * Flujo: 1) Validación PDF → 2) Subida temporal a Files API → 3) Llamada Responses con file_id → 4) Parser JSON robusto.
 */
class PreguntasSugeridasService
{
    private const MIN_PREGUNTAS_POR_PDF   = 10;
    /** Tamaño máximo del PDF en bytes (50 MB límite OpenAI; usamos 20 MB por seguridad). */
    private const MAX_BYTES_PDF           = 20 * 1024 * 1024;
    /** Extensiones y MIME aceptados para PDF. */
    private const MIME_PDF                = 'application/pdf';
    private const DELAY_ENTRE_SECCIONES   = 2;
    private const REINTENTOS_RATE_LIMIT  = [5, 15];
    private const OPENAI_HTTP_TIMEOUT    = 120;
    private const MAX_EXECUTION_TIME     = 600;

    private const TIPOS_VALIDOS = ['multiple', 'única', 'falso_verdadero', 'autocompletar'];

    protected SeccionCursoModel $seccionModel;
    protected SeccionCursoService $seccionCursoService;
    protected string $openaiApiKey;

    public function __construct(
        ?SeccionCursoModel $seccionModel = null,
        ?SeccionCursoService $seccionCursoService = null
    ) {
        $this->seccionModel        = $seccionModel ?? new SeccionCursoModel();
        $this->seccionCursoService = $seccionCursoService ?? \Config\Services::seccionCurso();
        $this->openaiApiKey        = env('OPENAI_API_KEY') ?: (string) getenv('OPENAI_API_KEY');
    }

    /**
     * Obtiene las preguntas sugeridas por cada PDF (sección) del curso.
     *
     * @return array{idCurso: int, secciones: array<int, array{idSeccionCurso: int, Nombre: string, rutaArchivo: string, preguntas: array, error?: string}>}
     */
    public function getPreguntasSugeridasPorCurso(int $idCurso): array
    {
        set_time_limit(self::MAX_EXECUTION_TIME);

        log_message('info', '[PreguntasSugeridas] Inicio getPreguntasSugeridasPorCurso idCurso=' . $idCurso);

        if ($this->openaiApiKey === '') {
            log_message('error', '[PreguntasSugeridas] OPENAI_API_KEY no configurada.');
            throw new RuntimeException('OPENAI_API_KEY no está configurada en el entorno.');
        }

        $secciones = $this->seccionModel->seccionesPorCurso($idCurso);
        if (empty($secciones)) {
            log_message('info', '[PreguntasSugeridas] Curso sin secciones.');
            return ['idCurso' => $idCurso, 'secciones' => []];
        }

        $rutaCarpeta = $this->seccionCursoService->rutaCarpetaCursoPorId($idCurso);
        $resultadoSecciones = [];
        $primeraSeccionConPdf = true;

        foreach ($secciones as $seccion) {
            $idSeccionCurso = (int) $seccion['idSeccionCurso'];
            $nombreArchivo  = $seccion['RutaArchivo'] ?? '';
            $rutaPdf        = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreArchivo;

            $item = [
                'idSeccionCurso' => $idSeccionCurso,
                'Nombre'         => $seccion['Nombre'] ?? '',
                'rutaArchivo'    => $nombreArchivo,
                'preguntas'      => [],
            ];

            try {
                // Paso 1: Validación del PDF
                $this->validarPdf($rutaPdf, $nombreArchivo, $idSeccionCurso);

                if (! $primeraSeccionConPdf && self::DELAY_ENTRE_SECCIONES > 0) {
                    sleep(self::DELAY_ENTRE_SECCIONES);
                }
                $primeraSeccionConPdf = false;

                // Pasos 2–4: Subir a OpenAI, llamar Responses, parsear JSON
                $preguntas = $this->generarPreguntasDesdePdf($rutaPdf, $nombreArchivo, $idSeccionCurso);
                $item['preguntas'] = $this->normalizarPreguntas($preguntas);
                log_message('info', '[PreguntasSugeridas] Sección ' . $idSeccionCurso . ' OK: ' . count($item['preguntas']) . ' preguntas.');
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $item['error'] = 'Error al generar preguntas: ' . $msg;
                log_message('error', '[PreguntasSugeridas] Sección ' . $idSeccionCurso . ' archivo=' . $nombreArchivo . ' error=' . $msg . ' trace=' . $e->getTraceAsString());
            }

            $resultadoSecciones[] = $item;
        }

        log_message('info', '[PreguntasSugeridas] Fin getPreguntasSugeridasPorCurso idCurso=' . $idCurso);
        return ['idCurso' => $idCurso, 'secciones' => $resultadoSecciones];
    }

    /**
     * Paso 1: Validación del PDF (existencia, tamaño, tipo).
     *
     * @throws RuntimeException Si la validación falla
     */
    protected function validarPdf(string $rutaPdf, string $nombreArchivo, int $idSeccionCurso): void
    {
        log_message('info', '[PreguntasSugeridas] Paso 1 validarPdf idSeccion=' . $idSeccionCurso . ' archivo=' . $nombreArchivo);

        if ($nombreArchivo === '') {
            log_message('warning', '[PreguntasSugeridas] Validación fallida: RutaArchivo vacío.');
            throw new RuntimeException('El nombre del archivo de la sección está vacío.');
        }

        if (! is_file($rutaPdf)) {
            log_message('warning', '[PreguntasSugeridas] Validación fallida: archivo no existe ruta=' . $rutaPdf);
            throw new RuntimeException('Archivo PDF no encontrado en el servidor.');
        }

        $tamano = filesize($rutaPdf);
        if ($tamano === false) {
            log_message('warning', '[PreguntasSugeridas] Validación fallida: no se pudo leer tamaño.');
            throw new RuntimeException('No se pudo leer el tamaño del archivo.');
        }
        if ($tamano > self::MAX_BYTES_PDF) {
            log_message('warning', '[PreguntasSugeridas] Validación fallida: PDF supera ' . (self::MAX_BYTES_PDF / 1024 / 1024) . ' MB.');
            throw new RuntimeException('El PDF supera el tamaño máximo permitido (' . (self::MAX_BYTES_PDF / 1024 / 1024) . ' MB).');
        }

        $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            log_message('warning', '[PreguntasSugeridas] Validación fallida: extensión no es pdf.');
            throw new RuntimeException('El archivo no tiene extensión PDF.');
        }

        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($rutaPdf);
            if ($mime !== false && $mime !== self::MIME_PDF) {
                log_message('warning', '[PreguntasSugeridas] Validación fallida: MIME=' . $mime);
                throw new RuntimeException('El archivo no parece ser un PDF válido (tipo MIME incorrecto).');
            }
        }

        log_message('info', '[PreguntasSugeridas] Paso 1 validarPdf OK tamaño=' . $tamano);
    }

    /**
     * Pasos 2–4: Intentar subir a Files API; si falla, enviar PDF en base64 en Responses. Luego parsear JSON.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generarPreguntasDesdePdf(string $rutaPdf, string $nombreArchivo, int $idSeccionCurso): array
    {
        $client = $this->crearClienteOpenAI();
        $content = '';

        // Paso 2: Intentar guardado temporal en OpenAI Files API
        try {
            $fileId = $this->subirPdfTemporalOpenAI($client, $rutaPdf, $nombreArchivo, $idSeccionCurso);
            try {
                // Paso 3a: Llamada Responses con file_id
                $content = $this->llamarResponsesConArchivo($client, $fileId, $nombreArchivo, $idSeccionCurso);
            } finally {
                $this->eliminarArchivoTemporalOpenAI($client, $fileId, $idSeccionCurso);
            }
        } catch (Throwable $e) {
            // Fallback: enviar PDF en base64 en la misma petición (evita multipart que puede fallar en algunos entornos)
            log_message('warning', '[PreguntasSugeridas] Paso 2 upload falló, usando fallback base64: ' . $e->getMessage());
            $content = $this->llamarResponsesConArchivoBase64($client, $rutaPdf, $nombreArchivo, $idSeccionCurso);
        }

        if ($content === '') {
            log_message('warning', '[PreguntasSugeridas] Paso 3 respuesta vacía.');
            return [];
        }

        // Paso 4: Parser robusto de JSON
        return $this->parsearJsonPreguntasRobusto($content, $idSeccionCurso);
    }

    /**
     * Paso 2: Sube el PDF a la API Files de OpenAI (purpose user_data) y devuelve el file_id.
     */
    protected function subirPdfTemporalOpenAI($client, string $rutaPdf, string $nombreArchivo, int $idSeccionCurso): string
    {
        log_message('info', '[PreguntasSugeridas] Paso 2 subirPdfTemporalOpenAI idSeccion=' . $idSeccionCurso);

        try {
            $response = $client->files()->upload([
                'file'    => $rutaPdf,
                'purpose' => 'user_data',
            ]);

            $fileId = $response->id ?? '';
            if ($fileId === '') {
                throw new RuntimeException('OpenAI no devolvió id de archivo.');
            }
            log_message('info', '[PreguntasSugeridas] Paso 2 subirPdfTemporalOpenAI OK file_id=' . $fileId);
            return $fileId;
        } catch (Throwable $e) {
            log_message('error', '[PreguntasSugeridas] Paso 2 subirPdfTemporalOpenAI error=' . $e->getMessage());
            throw new RuntimeException('Error al subir el PDF a OpenAI: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Paso 3 (fallback): Llamada a Responses enviando el PDF en base64 (sin usar Files API). Reintenta ante rate limit.
     */
    protected function llamarResponsesConArchivoBase64($client, string $rutaPdf, string $nombreArchivo, int $idSeccionCurso): string
    {
        log_message('info', '[PreguntasSugeridas] Paso 3 fallback llamarResponsesConArchivoBase64 idSeccion=' . $idSeccionCurso);

        $bytes  = file_get_contents($rutaPdf);
        $base64 = 'data:application/pdf;base64,' . base64_encode($bytes);
        $prompt = $this->construirPromptParaArchivo();

        $reintentos = self::REINTENTOS_RATE_LIMIT;
        $ultimaEx   = null;

        for ($intento = 0; $intento <= count($reintentos); $intento++) {
            try {
                $response = $client->responses()->create([
                    'model'  => 'gpt-4o-mini',
                    'input'  => [
                        [
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'input_file', 'filename' => $nombreArchivo, 'file_data' => $base64],
                                ['type' => 'input_text', 'text' => $prompt],
                            ],
                        ],
                    ],
                    'temperature' => 0.6,
                ]);
                $content = $response->outputText ?? '';
                log_message('info', '[PreguntasSugeridas] Paso 3 fallback base64 OK longitud=' . strlen($content));
                return $content;
            } catch (RateLimitException $e) {
                $ultimaEx = $e;
                if ($intento < count($reintentos)) {
                    sleep($reintentos[$intento]);
                }
            } catch (Throwable $e) {
                log_message('error', '[PreguntasSugeridas] Paso 3 fallback base64 error=' . $e->getMessage());
                throw new RuntimeException('Error al llamar a OpenAI (base64): ' . $e->getMessage(), 0, $e);
            }
        }
        throw $ultimaEx ?? new RuntimeException('Rate limit excedido tras reintentos.');
    }

    /**
     * Paso 3: Llamada a la API Responses con file_id. Reintenta ante rate limit.
     */
    protected function llamarResponsesConArchivo($client, string $fileId, string $nombreArchivo, int $idSeccionCurso): string
    {
        log_message('info', '[PreguntasSugeridas] Paso 3 llamarResponsesConArchivo idSeccion=' . $idSeccionCurso . ' file_id=' . $fileId);

        $prompt = $this->construirPromptParaArchivo();
        $reintentos = self::REINTENTOS_RATE_LIMIT;
        $ultimaEx = null;

        for ($intento = 0; $intento <= count($reintentos); $intento++) {
            try {
                $response = $client->responses()->create([
                    'model'  => 'gpt-4o-mini',
                    'input'  => [
                        [
                            'role'    => 'user',
                            'content' => [
                                ['type' => 'input_file', 'file_id' => $fileId],
                                ['type' => 'input_text', 'text' => $prompt],
                            ],
                        ],
                    ],
                    'temperature' => 0.6,
                ]);

                $content = $response->outputText ?? '';
                log_message('info', '[PreguntasSugeridas] Paso 3 llamarResponsesConArchivo OK longitud_respuesta=' . strlen($content));
                return $content;
            } catch (RateLimitException $e) {
                $ultimaEx = $e;
                log_message('warning', '[PreguntasSugeridas] Paso 3 rate limit intento=' . ($intento + 1));
                if ($intento < count($reintentos)) {
                    sleep($reintentos[$intento]);
                }
            } catch (Throwable $e) {
                log_message('error', '[PreguntasSugeridas] Paso 3 llamarResponsesConArchivo error=' . $e->getMessage());
                throw new RuntimeException('Error al llamar a OpenAI Responses: ' . $e->getMessage(), 0, $e);
            }
        }

        throw $ultimaEx ?? new RuntimeException('Rate limit excedido tras reintentos.');
    }

    /**
     * Elimina el archivo temporal de OpenAI (mejor esfuerzo).
     */
    protected function eliminarArchivoTemporalOpenAI($client, string $fileId, int $idSeccionCurso): void
    {
        try {
            $client->files()->delete($fileId);
            log_message('info', '[PreguntasSugeridas] Archivo temporal eliminado file_id=' . $fileId);
        } catch (Throwable $e) {
            log_message('warning', '[PreguntasSugeridas] No se pudo eliminar archivo temporal file_id=' . $fileId . ' error=' . $e->getMessage());
        }
    }

    /**
     * Paso 4: Parser robusto del JSON de preguntas. Acepta markdown, texto alrededor y variantes de claves.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parsearJsonPreguntasRobusto(string $content, int $idSeccionCurso): array
    {
        log_message('info', '[PreguntasSugeridas] Paso 4 parsearJsonPreguntasRobusto idSeccion=' . $idSeccionCurso);

        $content = trim($content);
        if ($content === '') {
            return [];
        }

        // Quitar posibles bloques markdown ```json ... ```
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);
        $content = trim($content);

        // Extraer el primer array JSON que aparezca
        if (! preg_match('/\[[\s\S]*\]/', $content, $m)) {
            log_message('warning', '[PreguntasSugeridas] Paso 4 no se encontró array JSON en la respuesta.');
            return [];
        }
        $jsonStr = $m[0];

        $decoded = json_decode($jsonStr, true);
        if (! is_array($decoded)) {
            $err = json_last_error_msg();
            log_message('error', '[PreguntasSugeridas] Paso 4 json_decode falló: ' . $err . ' fragmento=' . substr($jsonStr, 0, 200));
            return [];
        }

        $preguntas = [];
        foreach ($decoded as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }
            $pregunta = $this->extraerUnaPreguntaDelItem($item);
            if ($pregunta !== null) {
                $preguntas[] = $pregunta;
            }
        }

        log_message('info', '[PreguntasSugeridas] Paso 4 parsearJsonPreguntasRobusto OK cantidad=' . count($preguntas));
        return $preguntas;
    }

    /**
     * Extrae un único ítem de pregunta normalizando claves (pregunta/question, opciones/options, etc.).
     *
     * @return array<string, mixed>|null
     */
    protected function extraerUnaPreguntaDelItem(array $item): ?array
    {
        $texto = (string) ($item['pregunta'] ?? $item['question'] ?? $item['text'] ?? '');
        $texto = trim($texto);
        if ($texto === '') {
            return null;
        }

        $tipo = (string) ($item['tipo'] ?? $item['type'] ?? 'única');
        $tipo = strtolower(trim($tipo));
        if (! in_array($tipo, self::TIPOS_VALIDOS, true)) {
            $tipo = 'única';
        }

        $descripcion = (string) ($item['descripcion'] ?? $item['description'] ?? '');

        $opcionesRaw = $item['opciones'] ?? $item['options'] ?? $item['choices'] ?? [];
        if (! is_array($opcionesRaw)) {
            $opcionesRaw = [];
        }

        $opciones = [];
        foreach ($opcionesRaw as $o) {
            if (! is_array($o)) {
                continue;
            }
            $respuesta = (string) ($o['respuesta'] ?? $o['answer'] ?? $o['text'] ?? '');
            $respuesta = trim($respuesta);
            if ($respuesta === '') {
                continue;
            }
            $correcta = isset($o['correcta']) ? (bool) $o['correcta'] : (isset($o['correct']) ? (bool) $o['correct'] : false);
            $opciones[] = ['respuesta' => $respuesta, 'correcta' => $correcta];
        }

        return [
            'pregunta'    => $texto,
            'descripcion' => $descripcion,
            'tipo'        => $tipo,
            'opciones'    => $opciones,
        ];
    }

    protected function crearClienteOpenAI(): \OpenAI\Client
    {
        $httpClient = $this->createOpenAIHttpClient();
        return OpenAI::factory()->withApiKey($this->openaiApiKey)->withHttpClient($httpClient)->make();
    }

    protected function createOpenAIHttpClient(): ClientInterface
    {
        $config = ['timeout' => self::OPENAI_HTTP_TIMEOUT];
        $desactivarVerify = (defined('ENVIRONMENT') && ENVIRONMENT === 'development')
            || (env('OPENAI_SSL_VERIFY') !== null && filter_var(env('OPENAI_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN) === false);
        if ($desactivarVerify) {
            $config['verify'] = false;
        }
        return Guzzle7Adapter::createWithConfig($config);
    }

    protected function construirPromptParaArchivo(): string
    {
        $num = self::MIN_PREGUNTAS_POR_PDF;
        return <<<PROMPT
Eres un experto en crear preguntas de evaluación a partir de contenido formativo.

Se te ha adjuntado un PDF. Genera exactamente {$num} o más preguntas de evaluación sobre el contenido del documento.

Requisitos:
- Cada pregunta debe poder guardarse en una base de datos con estos tipos: "multiple", "única", "falso_verdadero", "autocompletar".
- El texto de la pregunta no debe superar 255 caracteres.
- Para "multiple" y "única" incluye entre 2 y 5 opciones; indica cuál(es) es(son) correcta(s) con "correcta": true o false.
- Para "falso_verdadero" incluye dos opciones: "Verdadero" y "Falso" con la correcta indicada.
- Responde ÚNICAMENTE con un JSON válido, sin texto antes ni después, con esta estructura (array de objetos):

[
  {
    "pregunta": "Texto de la pregunta (máx. 255 caracteres)",
    "descripcion": "Breve contexto (opcional)",
    "tipo": "única",
    "opciones": [
      { "respuesta": "Opción A", "correcta": false },
      { "respuesta": "Opción B", "correcta": true }
    ]
  }
]

Si alguna pregunta es "autocompletar" o no lleva opciones, usa "opciones": [].
PROMPT;
    }

    /**
     * Normaliza cada pregunta al formato esperado por el front y POST /preguntas.
     *
     * @param array<int, array<string, mixed>> $preguntas
     * @return array<int, array<string, mixed>>
     */
    protected function normalizarPreguntas(array $preguntas): array
    {
        $normalizadas = [];
        foreach ($preguntas as $p) {
            $pregunta    = isset($p['pregunta']) ? (string) $p['pregunta'] : '';
            $descripcion = isset($p['descripcion']) ? (string) $p['descripcion'] : '';
            $tipo        = isset($p['tipo']) ? (string) $p['tipo'] : 'única';
            $opciones    = isset($p['opciones']) && is_array($p['opciones']) ? $p['opciones'] : [];

            if ($pregunta === '') {
                continue;
            }
            if (mb_strlen($pregunta) > 255) {
                $pregunta = mb_substr($pregunta, 0, 252) . '…';
            }
            if (! in_array($tipo, self::TIPOS_VALIDOS, true)) {
                $tipo = 'única';
            }

            $opcionesNorm = [];
            foreach ($opciones as $o) {
                if (! is_array($o) || ! isset($o['respuesta'])) {
                    continue;
                }
                $respuesta = (string) $o['respuesta'];
                if (mb_strlen($respuesta) > 255) {
                    $respuesta = mb_substr($respuesta, 0, 252) . '…';
                }
                $opcionesNorm[] = [
                    'respuesta' => $respuesta,
                    'correcta'  => ! empty($o['correcta']),
                ];
            }

            $normalizadas[] = [
                'pregunta'    => $pregunta,
                'descripcion' => $descripcion,
                'tipo'        => $tipo,
                'opciones'    => $opcionesNorm,
            ];
        }
        return $normalizadas;
    }
}
