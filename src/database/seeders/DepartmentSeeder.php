<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'title' => 'Diretoria',
                'status' => 'active',
            ],
            [
                'title' => 'Jurídico',
                'status' => 'active',
            ],
            [
                'title' => 'Financeiro',
                'status' => 'active',
            ],
            [
                'title' => 'Recursos Humanos',
                'status' => 'active',
            ],
            [
                'title' => 'Comercial',
                'status' => 'active',
            ],
            [
                'title' => 'Cobrança',
                'status' => 'active',
            ],
            [
                'title' => 'Marketing',
                'status' => 'active',
            ],
            [
                'title' => 'Tecnologia da Informação',
                'status' => 'active',
            ],
            [
                'title' => 'Atendimento',
                'status' => 'active',
            ],
            [
                'title' => 'Supply Chain',
                'status' => 'active',
            ],
            [
                'title' => 'Administrativo',
                'status' => 'active',
            ],
        ];

        foreach ($departments as $department) {
            $exists = Department::query()->where('title', $department['title'])->exists();

            if ($exists) continue;

            Department::create($department);

            $this->command->info("Departamento {$department['title']} criado com sucesso!");
        }
    }
}
