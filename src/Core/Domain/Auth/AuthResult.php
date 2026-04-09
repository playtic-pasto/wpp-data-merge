<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Auth;

/**
 * Value Object que representa el resultado de una operación de autenticación.
 *
 * @name AuthResult
 * @package WPDM\Core\Domain\Auth
 * @since 1.0.0
 */
final class AuthResult
{
    private function __construct(
        public readonly bool       $success,
        public readonly string     $message,
        public readonly ?AuthToken $token = null,
    ) {}

    public static function success(string $message, AuthToken $token): self
    {
        return new self(true, $message, $token);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function toArray(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->token !== null) {
            $result['data'] = $this->token->toArray();
        }

        return $result;
    }
}
