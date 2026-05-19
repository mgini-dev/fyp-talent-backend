<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Talent;

class TalentSeeder extends Seeder
{
    public function run(): void
    {
        $talents = [
            ['name' => 'Software Development', 'category' => 'Technology'],
            ['name' => 'Graphic Design', 'category' => 'Creative'],
            ['name' => 'Public Speaking', 'category' => 'Leadership'],
            ['name' => 'Data Analysis', 'category' => 'Technology'],
            ['name' => 'Creative Writing', 'category' => 'Creative'],
            ['name' => 'Project Management', 'category' => 'Business'],
            ['name' => 'Digital Marketing', 'category' => 'Business'],
            ['name' => 'Photography', 'category' => 'Creative'],
        ];

        foreach ($talents as $talent) {
            Talent::firstOrCreate(['name' => $talent['name']], $talent);
        }
    }
}
