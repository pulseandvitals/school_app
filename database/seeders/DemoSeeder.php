<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\GameAssetType;
use App\Enums\HabitatType;
use App\Models\GameAsset;
use App\Models\Habitat;
use App\Models\User;
use App\Services\Growth\ScoreRecorder;
use Illuminate\Database\Seeder;

/**
 * Optional demo classroom for test@example.com (password: "password").
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function __construct(private ScoreRecorder $recorder) {}

    public function run(): void
    {
        $this->call([GameAssetSeeder::class, GrowthRuleSeeder::class, AchievementSeeder::class]);

        $teacher = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw(['name' => 'Test User', 'email' => 'test@example.com']),
        );

        if ($teacher->habitats()->exists()) {
            $this->command?->warn('Demo data already exists for test@example.com — skipping.');

            return;
        }

        mt_srand(2026);

        $names = [
            'John Reyes', 'Mary Santos', 'Peter Lim', 'Anna Cruz', 'Miguel Torres', 'Sofia Garcia',
            'Lucas Tan', 'Isabella Ramos', 'Gabriel Flores', 'Chloe Mendoza', 'Ethan Navarro', 'Mia Villanueva',
        ];

        $aquarium = $this->habitat($teacher, HabitatType::Aquarium, 'Grade 3 - Section A', 'coral-reef', 'Our happy morning class.');
        $garden = $this->habitat($teacher, HabitatType::Garden, 'Grade 2 - Section B', 'sunny-meadow', 'Growing together every day.');

        $students = collect($names)->map(fn (string $name, int $index) => $teacher->students()->create([
            'name' => $name,
            'student_number' => sprintf('2026-%05d', $index + 1),
        ]));

        $this->populate($aquarium, $students->take(10), $teacher, ['Math Quiz 1', 'Science Quiz 1', 'English Activity', 'Math Quiz 2', 'First Quarter Exam', 'Science Quiz 2', 'Reading Assignment']);
        $this->populate($garden, $students->slice(4), $teacher, ['Spelling Quiz', 'Art Activity', 'Math Quiz 1', 'Reading Quiz', 'Science Activity']);
    }

    private function habitat(User $teacher, HabitatType $type, string $name, string $theme, string $description): Habitat
    {
        $habitat = new Habitat(['name' => $name, 'theme' => $theme, 'description' => $description]);
        $habitat->type = $type;
        $habitat->user()->associate($teacher);
        $habitat->save();

        return $habitat;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Student>  $students
     * @param  array<int, string>  $titles
     */
    private function populate(Habitat $habitat, $students, User $teacher, array $titles): void
    {
        $assetType = $habitat->type === HabitatType::Aquarium ? GameAssetType::Fish : GameAssetType::Plant;
        $assetIds = GameAsset::query()->ofType($assetType)->active()->pluck('id')->all();

        $members = $students->values()->map(fn ($student, int $index) => $habitat->members()->create([
            'student_id' => $student->id,
            'game_asset_id' => $assetIds[($index * 7) % count($assetIds)],
        ]));

        // Each student has a "talent" so the class shows a spread of growth.
        $talent = $members->mapWithKeys(fn ($member) => [$member->id => mt_rand(45, 98)]);

        foreach ($titles as $index => $title) {
            $total = [20, 25, 30, 50][$index % 4];
            $assessment = $habitat->assessments()->create([
                'title' => $title,
                'type' => str_contains($title, 'Exam') ? AssessmentType::Examination : (str_contains($title, 'Quiz') ? AssessmentType::Quiz : AssessmentType::Activity),
                'assessed_on' => now()->subDays((count($titles) - $index) * 6)->toDateString(),
                'total_score' => $total,
            ]);

            $scores = $members->mapWithKeys(function ($member) use ($talent, $total) {
                $percentage = min(100, max(20, $talent[$member->id] + mt_rand(-12, 10)));

                return [$member->id => round($total * $percentage / 100)];
            })->all();

            $this->recorder->record($assessment, $scores, $teacher);
        }
    }
}
