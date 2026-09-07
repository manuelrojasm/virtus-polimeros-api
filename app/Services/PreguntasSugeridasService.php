<?php

namespace App\Services;

use App\Models\SeccionCursoModel;
use RuntimeException;
use Throwable;

/**
 * Servicio que genera preguntas sugeridas para un curso a partir de los resúmenes
 * de sus secciones (PDFs), usando Claude.
 *
 * Flujo: 1) Reunir resúmenes de las secciones activas del curso (generando el
 * resumen al vuelo para secciones antiguas que aún no lo tengan guardado) →
 * 2) Unir todos los resúmenes en un solo texto → 3) Pedir a Claude un único
 * lote de preguntas que cubra todo el contenido → 4) Parsear y normalizar al
 * formato esperado por POST /preguntas.
 */
class PreguntasSugeridasService
{
    private const MIN_PREGUNTAS_POR_CURSO = 15;
    private const PREGUNTAS_POR_SECCION   = 5;

    private const TIPOS_VALIDOS = ['multiple', 'única', 'falso_verdadero', 'autocompletar'];

    protected SeccionCursoModel $seccionModel;
    protected SeccionCursoService $seccionCursoService;
    protected ResumenPdfService $resumenPdfService;
    protected AnthropicService $anthropic;

    public function __construct(
        ?SeccionCursoModel $seccionModel = null,
        ?SeccionCursoService $seccionCursoService = null,
        ?ResumenPdfService $resumenPdfService = null,
        ?AnthropicService $anthropic = null
    ) {
        $this->seccionModel        = $seccionModel ?? new SeccionCursoModel();
        $this->seccionCursoService = $seccionCursoService ?? \Config\Services::seccionCurso();
        $this->resumenPdfService   = $resumenPdfService ?? \Config\Services::resumenPdf();
        $this->anthropic           = $anthropic ?? \Config\Services::anthropic();
    }

    /**
     * Genera las preguntas sugeridas para un curso, uniendo el resumen de todas sus secciones.
     *
     * @return array{idCurso: int, preguntas: array}
     */
    public function getPreguntasSugeridasPorCurso(int $idCurso): array
    {
        log_message('info', '[PreguntasSugeridas] Inicio idCurso=' . $idCurso);

        $secciones = array_values(array_filter(
            $this->seccionModel->seccionesPorCurso($idCurso),
            static fn ($s) => (int) ($s['Estado'] ?? 1) === 1
        ));

        if (empty($secciones)) {
            log_message('info', '[PreguntasSugeridas] Curso sin secciones activas.');
            return ['idCurso' => $idCurso, 'preguntas' => []];
        }

        $resumenCombinado = $this->reunirResumenes($idCurso, $secciones);

        if (trim($resumenCombinado) === '') {
            log_message('warning', '[PreguntasSugeridas] No se pudo obtener resumen de ninguna sección.');
            return ['idCurso' => $idCurso, 'preguntas' => []];
        }

        $minimoPreguntas = max(self::MIN_PREGUNTAS_POR_CURSO, count($secciones) * self::PREGUNTAS_POR_SECCION);
        $prompt = $this->construirPromptPreguntas($resumenCombinado, $minimoPreguntas);

        $respuesta = $this->anthropic->enviarMensaje($prompt, 8192);
        $preguntas = $this->normalizarPreguntas($this->parsearJsonPreguntasRobusto($respuesta));

        log_message('info', '[PreguntasSugeridas] Fin idCurso=' . $idCurso . ' cantidad=' . count($preguntas));

        return ['idCurso' => $idCurso, 'preguntas' => $preguntas];
    }

    /**
     * Une el resumen de cada sección activa. Genera y guarda al vuelo el resumen
     * de secciones antiguas que aún no lo tengan (auto-sanación).
     */
    protected function reunirResumenes(int $idCurso, array $secciones): string
    {
        $rutaCarpeta = null;
        $partes = [];

        foreach ($secciones as $seccion) {
            $resumen = trim((string) ($seccion['Resumen'] ?? ''));

            if ($resumen === '') {
                $rutaCarpeta ??= $this->obtenerRutaCarpetaCurso($idCurso);
                $resumen = $this->generarYGuardarResumenLegacy($seccion, $rutaCarpeta);
            }

            if ($resumen !== '') {
                $nombre = $seccion['Nombre'] ?? ('Sección ' . $seccion['idSeccionCurso']);
                $partes[] = "=== {$nombre} ===\n{$resumen}";
            }
        }

        return implode("\n\n", $partes);
    }

    protected function obtenerRutaCarpetaCurso(int $idCurso): ?string
    {
        try {
            return $this->seccionCursoService->rutaCarpetaCursoPorId($idCurso);
        } catch (Throwable $e) {
            log_message('warning', '[PreguntasSugeridas] No se pudo resolver carpeta del curso: ' . $e->getMessage());
            return null;
        }
    }

    protected function generarYGuardarResumenLegacy(array $seccion, ?string $rutaCarpeta): string
    {
        $idSeccionCurso = (int) $seccion['idSeccionCurso'];
        $nombreArchivo  = $seccion['RutaArchivo'] ?? '';

        if ($rutaCarpeta === null || $nombreArchivo === '') {
            return '';
        }

        $rutaPdf = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreArchivo;

        try {
            $resumen = $this->resumenPdfService->procesarPdf($rutaPdf);
            $this->seccionModel->update($idSeccionCurso, ['Resumen' => $resumen]);
            log_message('info', '[PreguntasSugeridas] Resumen generado al vuelo para sección ' . $idSeccionCurso);
            return $resumen;
        } catch (Throwable $e) {
            log_message('error', '[PreguntasSugeridas] Falló resumen al vuelo sección=' . $idSeccionCurso . ' error=' . $e->getMessage());
            return '';
        }
    }

    protected function construirPromptPreguntas(string $resumenCombinado, int $minimoPreguntas): string
    {
        return <<<PROMPT
Eres un experto en crear preguntas de evaluación a partir de contenido formativo.

A continuación tienes el resumen del contenido de un curso, dividido por secciones. Genera exactamente
{$minimoPreguntas} o más preguntas de evaluación que cubran el contenido de todas las secciones.

Requisitos:
- Cada pregunta debe poder guardarse en una base de datos con estos tipos: "multiple", "única", "falso_verdadero", "autocompletar".
- El texto de la pregunta no debe superar 255 caracteres.
- Para "multiple" y "única" incluye entre 2 y 5 opciones; indica cuál(es) es(son) correcta(s) con "correcta": true o false.
- Para "falso_verdadero" incluye dos opciones: "Verdadero" y "Falso" con la correcta indicada.
- Distribuye las preguntas cubriendo todas las secciones, no solo la primera.
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

Resumen del contenido:
{$resumenCombinado}
PROMPT;
    }

    /**
     * Parser robusto del JSON de preguntas. Acepta markdown, texto alrededor y variantes de claves.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parsearJsonPreguntasRobusto(string $content): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```\s*$/', '', $content);
        $content = trim($content);

        if (! preg_match('/\[[\s\S]*\]/', $content, $m)) {
            log_message('warning', '[PreguntasSugeridas] No se encontró array JSON en la respuesta.');
            return [];
        }
        $jsonStr = $m[0];

        $decoded = json_decode($jsonStr, true);
        if (! is_array($decoded)) {
            log_message('error', '[PreguntasSugeridas] json_decode falló: ' . json_last_error_msg());
            return [];
        }

        $preguntas = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $pregunta = $this->extraerUnaPreguntaDelItem($item);
            if ($pregunta !== null) {
                $preguntas[] = $pregunta;
            }
        }

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
