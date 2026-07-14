<?php
/**
 * Calling the gateway with the Laravel AI SDK.
 *
 * Two ways: an agent class (see SchemaAgent.php) or the anonymous agent()
 * function with per-call provider/model overrides.
 */

use App\Ai\Agents\SchemaAgent;

use function Laravel\Ai\agent;

// 1) Agent class — provider/model come from its attributes.
$response = (new SchemaAgent)->prompt('Assess this page markup: <html>...</html>');
echo (string) $response;

// 2) Anonymous agent — choose provider + model per call.
//    provider: 'bifrost'  -> the gateway provider from config/ai.php
//    model:    'anthropic/claude-3-5-sonnet-20241022' -> Bifrost provider/model
$response = agent(instructions: 'You are an expert on Schema.org structured data.')
    ->prompt(
        'Assess this page markup: <html>...</html>',
        provider: 'bifrost',
        model: 'anthropic/claude-3-5-sonnet-20241022',
    );
echo (string) $response;

// 3) Streaming — returnable straight from a route/controller.
// return agent(instructions: '...')
//     ->stream('A long answer...', provider: 'bifrost', model: 'openai/gpt-4o');
