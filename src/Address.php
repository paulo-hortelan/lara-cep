<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep;

final class Address
{
    public function __construct(
        public readonly string $zipCode,
        public readonly string $state,
        public readonly string $city,
        public readonly ?string $district,
        public readonly ?string $street,
        public readonly string $provider,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            zipCode: (string) $data['zipCode'],
            state: (string) $data['state'],
            city: (string) $data['city'],
            district: isset($data['district']) ? (string) $data['district'] : null,
            street: isset($data['street']) ? (string) $data['street'] : null,
            provider: (string) $data['provider'],
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'zipCode' => $this->zipCode,
            'state' => $this->state,
            'city' => $this->city,
            'district' => $this->district,
            'street' => $this->street,
            'provider' => $this->provider,
        ];
    }
}
