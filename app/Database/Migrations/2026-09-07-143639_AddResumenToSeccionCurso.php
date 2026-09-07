<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResumenToSeccionCurso extends Migration
{
    public function up()
    {
        if (! $this->hasColumn('seccioncurso', 'Resumen')) {
            $this->forge->addColumn('seccioncurso', [
                'Resumen' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'RutaArchivo',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->hasColumn('seccioncurso', 'Resumen')) {
            $this->forge->dropColumn('seccioncurso', 'Resumen');
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $query = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$column]);

        return $query->getNumRows() > 0;
    }
}
