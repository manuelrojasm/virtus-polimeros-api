<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRangoColumnsToPregunta extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->hasColumn('Pregunta', 'RangoMin')) {
            $fields['RangoMin'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'Estado',
            ];
        }

        if (! $this->hasColumn('Pregunta', 'RangoMax')) {
            $fields['RangoMax'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'RangoMin',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('Pregunta', $fields);
        }
    }

    public function down()
    {
        if ($this->hasColumn('Pregunta', 'RangoMin')) {
            $this->forge->dropColumn('Pregunta', 'RangoMin');
        }

        if ($this->hasColumn('Pregunta', 'RangoMax')) {
            $this->forge->dropColumn('Pregunta', 'RangoMax');
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $query = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$column]);
        return $query->getNumRows() > 0;
    }
}
