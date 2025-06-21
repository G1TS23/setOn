<?php

namespace App\Provider;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

class JwtProvider implements TokenProviderInterface
{
    /**
     * @var string
     */
    private string $secret;

    public function __construct(string $secret){
        $this->secret = $secret;
    }

    public function __invoke(): string{

        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secret)
        );

        $token = $configuration->builder()
            ->issuedBy('https://localhost/.well-known/mercure') // URL du hub Mercure
            ->permittedFor('https://localhost/.well-known/mercure')
            ->withClaim('mercure', ['publish' => ['*']])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }

    public function getJwt(): string
    {
        $configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secret)
        );

        $token = $configuration->builder()
            ->issuedBy('https://localhost/.well-known/mercure') // URL du hub Mercure
            ->permittedFor('https://localhost/.well-known/mercure')
            ->withClaim('mercure', ['publish' => ['*']])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }
}