<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Игра «Калькулятор»');
    }

    public function test_game_can_be_created_through_api(): void
    {
        $response = $this->postJson('/api/games', [
            'player_name' => 'Алиса',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('player_name', 'Алиса')
            ->assertJsonPath('status', 'in_progress');

        $this->assertDatabaseCount('games', 1);
    }

    public function test_step_can_be_submitted_for_existing_game(): void
    {
        $game = Game::query()->create([
            'player_name' => 'Боб',
            'expression' => '2+2',
            'correct_answer' => 4,
            'started_at' => now(),
        ]);

        $response = $this->postJson('/api/step/' . $game->id, [
            'user_answer' => 4,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('game_id', $game->id)
            ->assertJsonPath('step.is_correct', true)
            ->assertJsonPath('correct_answer', 4);

        $this->assertDatabaseCount('steps', 1);
    }
}
