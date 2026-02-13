<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFechaNacimientoToUsuario extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('Usuario', [
            'FechaNacimiento' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'Celular',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('Usuario', 'FechaNacimiento');
    }
}
