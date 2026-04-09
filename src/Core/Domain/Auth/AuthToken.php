<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Auth;

/**
 * Value Object que representa un token de acceso obtenido desde la API externa.
 *
 * @name AuthToken
 * @package WPDM\Core\Domain\Auth
 * @since 1.0.0
 */
final class AuthToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $authorizationToken,
        public readonly string $tokenType,
        public readonly int    $expiresIn,
        public readonly string $usuario,
        public readonly string $idUsuario,
    ) {}

    public function toArray(): array
    {
        return [
            'access_token'        => $this->accessToken,
            'authorization_token' => $this->authorizationToken,
            'token_type'          => $this->tokenType,
            'expires_in'          => $this->expiresIn,
            'usuario'             => $this->usuario,
            'id_usuario'          => $this->idUsuario,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            accessToken:        $data['access_token'] ?? '',
            authorizationToken: $data['authorization_token'] ?? '',
            tokenType:          $data['token_type'] ?? 'Bearer',
            expiresIn:          (int) ($data['expires_in'] ?? 0),
            usuario:            $data['usuario'] ?? '',
            idUsuario:          $data['id_usuario'] ?? '',
        );
    }

    public function authorizationHeader(): string
    {
        return "{$this->tokenType} {$this->accessToken}";
    }
}
