<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'title' => 'Contrato',
                'taxonomy' => 'process',
                'status' => 'active',
            ],
            [
                'title' => 'Procuração',
                'taxonomy' => 'process',
                'status' => 'active',
            ],
            [
                'title' => 'Acordo',
                'taxonomy' => 'process',
                'status' => 'active',
            ],
        ];

        foreach ($categories as $category) {
            $exists = Category::query()->where('title', $category['title'])->where('taxonomy', $category['taxonomy'])->exists();

            if ($exists) continue;

            Category::create($category);

            $this->command->info("Categoria {$category['title']} criada com sucesso!");
        }
    }
}
