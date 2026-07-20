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
            ['title' => 'Diretoria', 'status' => 'active'],
            ['title' => 'Administrativo', 'status' => 'active'],
            ['title' => 'Financeiro', 'status' => 'active'],
            ['title' => 'Controladoria', 'status' => 'active'],
            ['title' => 'Contabilidade', 'status' => 'active'],
            ['title' => 'Jurídico', 'status' => 'active'],
            ['title' => 'Recursos Humanos', 'status' => 'active'],
            ['title' => 'Departamento Pessoal', 'status' => 'active'],
            ['title' => 'Comercial', 'status' => 'active'],
            ['title' => 'Vendas', 'status' => 'active'],
            ['title' => 'Marketing', 'status' => 'active'],
            ['title' => 'Atendimento ao Cliente', 'status' => 'active'],
            ['title' => 'Suporte', 'status' => 'active'],
            ['title' => 'Cobrança', 'status' => 'active'],
            ['title' => 'Compras', 'status' => 'active'],
            ['title' => 'Suprimentos', 'status' => 'active'],
            ['title' => 'Estoque', 'status' => 'active'],
            ['title' => 'Logística', 'status' => 'active'],
            ['title' => 'Produção', 'status' => 'active'],
            ['title' => 'Operações', 'status' => 'active'],
            ['title' => 'Qualidade', 'status' => 'active'],
            ['title' => 'Engenharia', 'status' => 'active'],
            ['title' => 'Pesquisa e Desenvolvimento', 'status' => 'active'],
            ['title' => 'Tecnologia da Informação', 'status' => 'active'],
            ['title' => 'Infraestrutura', 'status' => 'active'],
            ['title' => 'Segurança da Informação', 'status' => 'active'],
            ['title' => 'Projetos', 'status' => 'active'],
            ['title' => 'Auditoria', 'status' => 'active'],
            ['title' => 'Compliance', 'status' => 'active']
        ];

        foreach ($departments as $department) {
            $exists = Department::query()->where('title', $department['title'])->exists();

            if ($exists) continue;

            Department::create($department);

            $this->command->info("Departamento {$department['title']} criado com sucesso!");
        }
    }
}
