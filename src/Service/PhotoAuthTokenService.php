<?php declare(strict_types=1);

namespace InReNa\Service;

/**
 * Service for generating and validating HMAC-SHA256 auth tokens for photo access.
 *
 * Tokens are time-limited (default 24 hours) and tied to a specific photo ID.
 * They allow secure, non-public access to observation photos in export files.
 */
class PhotoAuthTokenService
{
    private const TOKEN_VALIDITY_SECONDS = 604800; // 7 days (1 week)
    private const ALGORITHM = 'sha256';

    public function __construct(
        private readonly string $appSecret,
    ) {
    }

    /**
     * Generate an auth token for a photo.
     *
     * @param string $photoId The photo entity ID
     * @param \DateTimeInterface|null $expiresAt Custom expiration (default: now + 7 days)
     *
     * @return array{token: string, expires_at: string}
     */
    public function generateToken(string $photoId, ?\DateTimeInterface $expiresAt = null): array
    {
        if ($expiresAt === null) {
            $expiresAt = new \DateTimeImmutable('+' . self::TOKEN_VALIDITY_SECONDS . ' seconds');
        }

        $expiresAtStr = $expiresAt->format(\DateTimeInterface::ATOM);
        $payload = $this->buildPayload($photoId, $expiresAtStr);
        $signature = $this->sign($payload);

        // Token format: base64(expires_at):signature
        $token = base64_encode($expiresAtStr) . '.' . $signature;

        return [
            'token' => $token,
            'expires_at' => $expiresAtStr,
        ];
    }

    /**
     * Validate an auth token for a photo.
     *
     * @param string $photoId The photo entity ID
     * @param string $token The token to validate
     *
     * @return bool True if the token is valid and not expired
     */
    public function validateToken(string $photoId, string $token): bool
    {
        $parts = explode('.', $token, 2);
        if (\count($parts) !== 2) {
            return false;
        }

        [$encodedExpiry, $signature] = $parts;

        $expiresAtStr = base64_decode($encodedExpiry, true);
        if ($expiresAtStr === false) {
            return false;
        }

        // Check expiration
        try {
            $expiresAt = new \DateTimeImmutable($expiresAtStr);
        } catch (\Exception) {
            return false;
        }

        if ($expiresAt < new \DateTimeImmutable()) {
            return false;
        }

        // Verify signature
        $payload = $this->buildPayload($photoId, $expiresAtStr);
        $expectedSignature = $this->sign($payload);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate a photo URL with an auth token.
     *
     * @param string $baseUrl The base URL of the application (e.g. https://domain.com)
     * @param string $photoId The photo entity ID
     * @param \DateTimeInterface|null $expiresAt Custom expiration
     *
     * @return array{url: string, expires_at: string}
     */
    public function generatePhotoUrl(string $baseUrl, string $photoId, ?\DateTimeInterface $expiresAt = null): array
    {
        $tokenData = $this->generateToken($photoId, $expiresAt);

        $url = rtrim($baseUrl, '/') . '/api/_action/miniwald/photo/' . $photoId
            . '?token=' . urlencode($tokenData['token']);

        return [
            'url' => $url,
            'expires_at' => $tokenData['expires_at'],
        ];
    }

    private function buildPayload(string $photoId, string $expiresAt): string
    {
        return $photoId . ':' . $expiresAt;
    }

    private function sign(string $payload): string
    {
        return hash_hmac(self::ALGORITHM, $payload, $this->appSecret);
    }
}
