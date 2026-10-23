<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Stubs;

use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Rooberthh\Sdk\Client;

/**
 * Declares a default plugin, to show where the SDK Author's plugins sit in
 * the chain relative to the Consumer's.
 */
class PluginClient extends Client
{
    protected function defaultPlugins(): array
    {
        return [new HeaderAppendPlugin(['X-Order' => 'author'])];
    }
}
