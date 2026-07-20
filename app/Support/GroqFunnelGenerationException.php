<?php

namespace App\Support;

use RuntimeException;
use Throwable;

class GroqFunnelGenerationException extends RuntimeException
{
    public function __construct(
        public readonly string $userMessage,
        ?Throwable $previous = null,
    ) {
        parent::__construct($userMessage, 0, $previous);
    }

    public static function notConfigured(): self
    {
        return new self('A geração por IA ainda não foi configurada. Adicione a chave da Groq nas configurações do sistema.');
    }

    public static function unauthorized(): self
    {
        return new self('A credencial da Groq foi recusada. Verifique a chave configurada.');
    }

    public static function rateLimited(): self
    {
        return new self('O limite de uso da IA foi atingido. Aguarde um momento e tente novamente.');
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self('O serviço de IA está temporariamente indisponível. Tente novamente em instantes.', $previous);
    }

    public static function invalidResponse(?Throwable $previous = null): self
    {
        return new self('A IA não conseguiu montar um funil válido. Ajuste a descrição e tente novamente.', $previous);
    }
}
