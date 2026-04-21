<?php

namespace App\Services;

use App\Models\CursoDesarrolloEstudianteModel;
use App\Models\CursoModel;
use App\Models\UserModel;
use RuntimeException;

class CursoDesarrolloService
{
    protected CursoDesarrolloEstudianteModel $desarrolloModel;
    protected CursoModel $cursoModel;
    protected UserModel $userModel;

    public function __construct(
        ?CursoDesarrolloEstudianteModel $desarrolloModel = null,
        ?CursoModel $cursoModel = null,
        ?UserModel $userModel = null
    ) {
        $this->desarrolloModel = $desarrolloModel ?? new CursoDesarrolloEstudianteModel();
        $this->cursoModel      = $cursoModel ?? new CursoModel();
        $this->userModel       = $userModel ?? new UserModel();
    }

    public function inicioCurso(int $idCurso, int $idUsuario): array
    {
        $curso = $this->cursoModel->find($idCurso);
        if (! $curso) {
            throw new RuntimeException('Curso no encontrado.');
        }

        $usuario = $this->userModel->find($idUsuario);
        if (! $usuario) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $registro = $this->desarrolloModel
            ->where('idCurso', $idCurso)
            ->where('idUsuario', $idUsuario)
            ->first();

        $now = date('Y-m-d H:i:s');

        if ($registro) {
            $this->desarrolloModel->update($registro['idCursoDesarrolloEstudiante'], [
                'FechaInicio'       => $now,
                'FechaFinalizacion' => null,
                'UltimaCalificacion'=> null,
                'Aprobo'            => 0,
                'FechaModificacion' => $now,
            ]);
        } else {
            $this->desarrolloModel->insert([
                'idCurso'              => $idCurso,
                'idUsuario'            => $idUsuario,
                'FechaInicio'          => $now,
                'FechaFinalizacion'    => null,
                'UltimaCalificacion'   => null,
                'Aprobo'               => 0,
                'VariablesSeguimiento' => null,
                'FechaCreacion'        => $now,
                'FechaModificacion'    => $now,
            ]);
        }

        $actualizado = $this->desarrolloModel
            ->where('idCurso', $idCurso)
            ->where('idUsuario', $idUsuario)
            ->first();

        if (! $actualizado) {
            throw new RuntimeException('No se pudo iniciar el curso.');
        }

        return $actualizado;
    }

    public function finalizacionCurso(int $idCurso, int $idUsuario, array $data): array
    {
        $ultimaCalificacion = filter_var($data['UltimaCalificacion'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($ultimaCalificacion === false || $ultimaCalificacion < 0 || $ultimaCalificacion > 100) {
            throw new RuntimeException('UltimaCalificacion debe estar entre 0 y 100.');
        }

        if (! array_key_exists('Aprobo', $data)) {
            throw new RuntimeException('El campo Aprobo es obligatorio (0 o 1).');
        }

        $aprobo = filter_var($data['Aprobo'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
        if ($aprobo === false) {
            throw new RuntimeException('Aprobo debe ser 0 o 1.');
        }

        $registro = $this->desarrolloModel
            ->where('idCurso', $idCurso)
            ->where('idUsuario', $idUsuario)
            ->first();

        $now = date('Y-m-d H:i:s');
        $variablesSeguimiento = null;

        if (array_key_exists('VariablesSeguimiento', $data)) {
            $variables = $data['VariablesSeguimiento'];
            if (is_array($variables) || is_object($variables)) {
                $variablesSeguimiento = json_encode($variables, JSON_UNESCAPED_UNICODE);
            } elseif ($variables !== null) {
                $variablesSeguimiento = (string) $variables;
            }
        }

        if (! $registro) {
            $this->inicioCurso($idCurso, $idUsuario);
            $registro = $this->desarrolloModel
                ->where('idCurso', $idCurso)
                ->where('idUsuario', $idUsuario)
                ->first();
        }

        if (! $registro) {
            throw new RuntimeException('No existe un inicio de curso para este estudiante.');
        }

        $payload = [
            'UltimaCalificacion' => $ultimaCalificacion,
            'FechaFinalizacion'  => $now,
            'Aprobo'             => $aprobo,
            'FechaModificacion'  => $now,
        ];

        if (array_key_exists('VariablesSeguimiento', $data)) {
            $payload['VariablesSeguimiento'] = $variablesSeguimiento;
        }

        $this->desarrolloModel->update($registro['idCursoDesarrolloEstudiante'], $payload);

        $actualizado = $this->desarrolloModel->find($registro['idCursoDesarrolloEstudiante']);
        if (! $actualizado) {
            throw new RuntimeException('No se pudo finalizar el curso.');
        }

        return $actualizado;
    }
}
