<?php

namespace App\Services;

use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extrae el texto de un PDF, valida que sea legible y genera un resumen con Claude.
 * Se usa al subir/reemplazar el PDF de una sección de curso (guardando el resumen
 * en seccioncurso.Resumen) y, por compatibilidad, para "auto-sanar" secciones
 * antiguas que aún no tienen resumen guardado.
 */
class ResumenPdfService
{
    private const MIN_CARACTERES_TEXTO = 30;
    private const MAX_CARACTERES_A_RESUMIR = 100000;

    protected AnthropicService $anthropic;

    public function __construct(?AnthropicService $anthropic = null)
    {
        $this->anthropic = $anthropic ?? \Config\Services::anthropic();
    }

    /**
     * Extrae y resume el texto de un PDF en un solo paso.
     *
     * @throws RuntimeException Si el PDF no es legible o falla la llamada a la IA.
     */
    public function procesarPdf(string $rutaPdf): string
    {
        $texto = $this->extraerTexto($rutaPdf);

        return $this->resumir($texto);
    }

    /**
     * Extrae el texto de un PDF y valida que contenga contenido legible.
     *
     * @throws RuntimeException Si el archivo no se puede leer o no tiene texto extraíble.
     */
    public function extraerTexto(string $rutaPdf): string
    {
        try {
            $parser    = new Parser();
            $documento = $parser->parseFile($rutaPdf);
            $texto     = trim($documento->getText());
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo leer el contenido del PDF: ' . $e->getMessage(), 0, $e);
        }

        if (mb_strlen($texto) < self::MIN_CARACTERES_TEXTO) {
            throw new RuntimeException('El PDF no contiene texto legible (puede ser una imagen escaneada).');
        }

        return $texto;
    }

    /**
     * Genera un resumen del texto usando Claude.
     *
     * @throws RuntimeException Si falla la llamada a la IA.
     */
    public function resumir(string $texto): string
    {
        if (mb_strlen($texto) > self::MAX_CARACTERES_A_RESUMIR) {
            $texto = mb_substr($texto, 0, self::MAX_CARACTERES_A_RESUMIR);
        }

        $prompt = $this->construirPromptResumen($texto);

        $resumen = trim($this->anthropic->enviarMensaje($prompt, 2048));

        if ($resumen === '') {
            throw new RuntimeException('La IA no devolvió un resumen para el PDF.');
        }

        return $resumen;
    }

    protected function construirPromptResumen(string $texto): string
    {
        return <<<PROMPT
Eres un experto en contenido educativo. Resume el siguiente material conservando los conceptos clave,
definiciones, cifras y datos importantes que un estudiante debería aprender. Máximo 800 palabras.
Responde únicamente con el resumen, sin introducciones ni comentarios.

Contenido:
{$texto}
PROMPT;
    }
}
