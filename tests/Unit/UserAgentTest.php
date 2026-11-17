<?php

declare(strict_types=1);

use Rooberthh\Sdk\Connector;

it('sends php-sdk and PHP versions as the default User-Agent', function (): void {
    $connector = new class ('https://api.example.com', http: mockHttp()) extends Connector {
        /** @return array<string, string> */
        public function headers(): array
        {
            return $this->defaultHeaders();
        }
    };

    expect($connector->headers()['User-Agent'])
        ->toBe('php-sdk/' . Connector::VERSION . ' PHP/' . PHP_VERSION);
});

it('lets an SDK Author put their own SDK in front', function (): void {
    $connector = new class ('https://api.example.com', http: mockHttp()) extends Connector {
        /** @return array<string, string> */
        public function headers(): array
        {
            return $this->defaultHeaders();
        }

        protected function userAgentString(): string
        {
            return 'acme-php/2.1.0 ' . parent::userAgentString();
        }
    };

    expect($connector->headers()['User-Agent'])
        ->toBe('acme-php/2.1.0 php-sdk/' . Connector::VERSION . ' PHP/' . PHP_VERSION);
});
