<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * An agent whose traffic goes through the Bifrost gateway.
 *
 * #[Provider('bifrost')] selects the 'bifrost' provider from config/ai.php
 * (the gateway). #[Model('provider/model')] is the Bifrost model string — the
 * prefix chooses the upstream provider.
 *
 * Generate a skeleton with:  php artisan make:agent SchemaAgent
 */
#[Provider('bifrost')]
#[Model('openai/gpt-4o-mini')]
class SchemaAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You assess the quality of Schema.org structured-data markup on a web page '
            . 'and return concise, actionable feedback.';
    }
}
