<?php

namespace Tests\Unit\Models;

use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function testImplementsJwtSubject(): void
    {
        $user = new User();
        $this->assertInstanceOf(JWTSubject::class, $user);
    }

    public function testGetJwtIdentifierReturnsPrimaryKey(): void
    {
        $user = new User();
        $user->id = 123;

        $this->assertSame(123, $user->getJWTIdentifier());
    }

    public function testGetJwtCustomClaimsReturnsEmptyArray(): void
    {
        $user = new User();

        $this->assertSame([], $user->getJWTCustomClaims());
    }

    public function testFillableAttributes(): void
    {
        $user = new User();

        $this->assertContains('name', $user->getFillable());
        $this->assertContains('email', $user->getFillable());
        $this->assertContains('password', $user->getFillable());
    }

    public function testHiddenAttributes(): void
    {
        $user = new User();

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    public function testCasts(): void
    {
        $user = new User();

        $casts = $user->getCasts();
        $this->assertEquals('datetime', $casts['email_verified_at']);
        $this->assertEquals('hashed', $casts['password']);
    }
}
