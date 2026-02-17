<?php
declare(strict_types=1);
namespace Pumukit\BasePlayerBundle\Tests\Services;
use PHPUnit\Framework\TestCase;
use Pumukit\BasePlayerBundle\Services\SecureTokenService;
use Symfony\Component\HttpFoundation\Request;
class SecureTokenServiceTest extends TestCase
{
    private SecureTokenService $service;
    protected function setUp(): void
    {
        $this->service = new SecureTokenService('test-secret-key', 3600);
    }
    public function testGenerateToken(): void
    {
        $resourceId = 'test-resource-123';
        $result = $this->service->generateToken($resourceId);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires', $result);
        $this->assertArrayHasKey('salt', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertGreaterThan(time(), $result['expires']);
    }
    public function testValidateToken(): void
    {
        $resourceId = 'test-resource-123';
        $clientIp = '192.168.1.1';
        $tokenData = $this->service->generateToken($resourceId, $clientIp);
        $isValid = $this->service->validateToken(
            $tokenData['token'],
            $resourceId,
            $tokenData['expires'],
            $clientIp
        );
        $this->assertTrue($isValid);
    }
    public function testValidateExpiredToken(): void
    {
        $resourceId = 'test-resource-123';
        $expiredTime = time() - 3600;
        $isValid = $this->service->validateToken(
            'any-token',
            $resourceId,
            $expiredTime,
            '192.168.1.1'
        );
        $this->assertFalse($isValid);
    }
    public function testValidateTokenWithDifferentResource(): void
    {
        $resourceId = 'test-resource-123';
        $tokenData = $this->service->generateToken($resourceId);
        $isValid = $this->service->validateToken(
            $tokenData['token'],
            'different-resource',
            $tokenData['expires'],
            null
        );
        $this->assertFalse($isValid);
    }
    public function testGenerateSecureUrl(): void
    {
        $baseUrl = 'https://example.com/video'\;
        $resourceId = 'test-resource-123';
        $secureUrl = $this->service->generateSecureUrl($baseUrl, $resourceId);
        $this->assertStringContainsString('token=', $secureUrl);
        $this->assertStringContainsString('expires=', $secureUrl);
        $this->assertStringContainsString('resource=', $secureUrl);
    }
    public function testValidateTokenFromRequest(): void
    {
        $resourceId = 'test-resource-123';
        $clientIp = '192.168.1.1';
        $tokenData = $this->service->generateToken($resourceId, $clientIp);
        $request = new Request();
        $request->query->set('token', $tokenData['token']);
        $request->query->set('expires', $tokenData['expires']);
        $request->query->set('resource', $resourceId);
        $request->server->set('REMOTE_ADDR', $clientIp);
        $isValid = $this->service->validateTokenFromRequest($request, $resourceId);
        $this->assertTrue($isValid);
    }
    public function testTokenIsNotPredictable(): void
    {
        $resourceId = 'test-resource-123';
        $token1 = $this->service->generateToken($resourceId);
        $token2 = $this->service->generateToken($resourceId);
        // Tokens should be different even for the same resource due to random salt
        $this->assertNotEquals($token1['token'], $token2['token']);
    }
}
