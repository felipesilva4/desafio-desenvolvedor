<?php

namespace Tests\Feature\Routes;

use App\Models\UploadHistoric;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ApiRoutesTest extends TestCase
{
    public function testUploadRouteRequiresAuthentication(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.csv', 100);

        $response = $this->postJson('/api/uploads', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function testHistoryRouteRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/uploads');

        $response->assertStatus(401);
    }

    public function testShowRouteRequiresAuthentication(): void
    {
        $upload = UploadHistoric::factory()->create();

        $response = $this->getJson("/api/uploads/{$upload->id}");

        $response->assertStatus(401);
    }

    public function testRoutesHaveCorrectNames(): void
    {
        $uploadRoute = route('uploads.upload', [], false);
        $historyRoute = route('uploads.history', [], false);

        $this->assertIsString($uploadRoute);
        $this->assertIsString($historyRoute);
    }

    public function testShowRouteHasModelBinding(): void
    {
        $upload = UploadHistoric::factory()->create();

        // Testa se a rota aceita o parâmetro do model
        $route = route('uploads.show', ['uploadHistoric' => $upload->id]);
        $this->assertTrue(str_contains($route, '/api/uploads/'));
    }

    protected function authenticateUser(): User
    {
        $user = User::factory()->create();
        JWTAuth::shouldReceive('attempt')
            ->andReturn('fake-token');

        return $user;
    }
}
