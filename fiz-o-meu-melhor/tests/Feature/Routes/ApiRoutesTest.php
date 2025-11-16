<?php

namespace Tests\Feature\Routes;

use App\Models\UploadHistoric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function testUploadRouteRequiresAuthentication(): void
    {
        $file = UploadedFile::fake()->create('test.csv', 100);

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

    public function testDataRouteRequiresAuthentication(): void
    {
        $response = $this->getJson("/api/data?TckrSymb=PETR4&RptDt=2024-01-01");

        $response->assertStatus(401);
    }

    public function testRoutesHaveCorrectNames(): void
    {
        $uploadRoute = route('uploads.upload', [], false);
        $historyRoute = route('uploads.history', [], false);
        $dataRoute = route('data.search', [], false);

        $this->assertIsString($uploadRoute);
        $this->assertIsString($historyRoute);
        $this->assertIsString($dataRoute);
        $this->assertTrue(str_contains($dataRoute, '/api/data'));
    }

    public function testShowRouteHasModelBinding(): void
    {
        $upload = UploadHistoric::factory()->create();

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
