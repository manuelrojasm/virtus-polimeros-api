<?php

namespace App\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

class ImageUploadService
{
    /** Tamaño máximo foto de perfil (2 MiB). */
    public const MAX_PERFIL_BYTES = 2_097_152;

    /** Tamaño máximo portada de curso (5 MiB). */
    public const MAX_CURSO_PORTADA_BYTES = 5_242_880;

    /** @var array<string, string> */
    private const MIME_TO_EXT = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * Guarda una imagen subida bajo public/uploads/{subdirectory}/ y devuelve la ruta relativa al directorio public (p. ej. uploads/usuarios/abc.jpg).
     *
     * @param string $subdirectory Ruta relativa dentro de uploads, sin barras al inicio (ej. "usuarios" o "cursos/5")
     */
    public function saveFromUpload(UploadedFile $file, string $subdirectory, int $maxBytes): string
    {
        if (! $file->isValid()) {
            $err = $file->getErrorString();
            throw new RuntimeException($err !== '' ? $err : 'Archivo de imagen inválido.');
        }

        $size = (int) $file->getSize();
        if ($size > $maxBytes) {
            $mb = round($maxBytes / 1_048_576, 1);
            throw new RuntimeException("La imagen supera el tamaño máximo permitido ({$mb} MB).");
        }

        $temp = $file->getTempName();
        if ($temp === '' || ! \is_readable($temp)) {
            throw new RuntimeException('No se pudo leer el archivo temporal.');
        }

        $mime = $this->detectImageMime($temp);
        if ($mime === '' || ! isset(self::MIME_TO_EXT[$mime])) {
            throw new RuntimeException('Formato no permitido. Use JPEG, PNG, WebP o GIF.');
        }

        $ext = self::MIME_TO_EXT[$mime];
        $subdir = trim(str_replace(['\\', '..'], ['/', ''], $subdirectory), '/');
        if ($subdir === '') {
            throw new RuntimeException('Subdirectorio inválido.');
        }

        $baseDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $subdir);

        if (! \is_dir($baseDir) && ! @\mkdir($baseDir, 0755, true)) {
            throw new RuntimeException('No se pudo crear el directorio de destino.');
        }

        $basename = bin2hex(\random_bytes(16)) . '.' . $ext;

        if (! $file->move($baseDir, $basename)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        return 'uploads/' . str_replace('\\', '/', $subdir) . '/' . $basename;
    }

    /**
     * Elimina un archivo previamente guardado bajo public/uploads/ (evita path traversal).
     */
    public function removeStoredPublicImage(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        if (strncmp($relativePath, 'uploads/', 8) !== 0) {
            return;
        }

        $full  = \realpath(FCPATH . \str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        $base  = \realpath(FCPATH . 'uploads');

        if ($full === false || $base === false || ! \is_file($full)) {
            return;
        }

        $baseNorm = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base), DIRECTORY_SEPARATOR);
        $fullNorm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full);

        if (! \str_starts_with($fullNorm, $baseNorm . DIRECTORY_SEPARATOR) && $fullNorm !== $baseNorm) {
            return;
        }

        @\unlink($full);
    }

    /**
     * Obtiene el MIME de una imagen sin depender solo de fileinfo (mime_content_type / finfo),
     * por si la extensión está deshabilitada en el servidor.
     */
    private function detectImageMime(string $path): string
    {
        if (\function_exists('mime_content_type')) {
            $m = @\mime_content_type($path);
            if (\is_string($m) && $m !== '') {
                return $m;
            }
        }

        if (\function_exists('finfo_open')) {
            $finfo = @\finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $m = \finfo_file($finfo, $path);
                \finfo_close($finfo);
                if (\is_string($m) && $m !== '') {
                    return $m;
                }
            }
        }

        if (\function_exists('getimagesize')) {
            $info = @\getimagesize($path);
            if ($info !== false && ! empty($info['mime'])) {
                return (string) $info['mime'];
            }
        }

        return '';
    }
}
