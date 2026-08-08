<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

function loadDocumentationWorkflow(string $filename): array
{
    $path     = dirname(__DIR__, 2) . '/.github/workflows/' . $filename;
    $contents = file_get_contents($path);
    $workflow = Yaml::parseFile($path);

    if ($contents === false || ! is_array($workflow)) {
        throw new RuntimeException("Unable to load documentation workflow: [$path].");
    }

    return [$workflow, $contents];
}

function documentationActionStep(array $steps, string $action): array
{
    foreach ($steps as $step) {
        if (str_starts_with($step['uses'] ?? '', $action . '@')) {
            return $step;
        }
    }

    throw new RuntimeException("Documentation workflow action not found: [$action].");
}

function documentationRunStep(array $steps, string $command): array
{
    foreach ($steps as $step) {
        if (str_contains($step['run'] ?? '', $command)) {
            return $step;
        }
    }

    throw new RuntimeException("Documentation workflow command not found: [$command].");
}

function expectDocumentationActionsPinned(array $steps): void
{
    foreach ($steps as $step) {
        $action = $step['uses'] ?? null;

        if ($action === null) {
            continue;
        }

        expect($action)->toMatch('/@[0-9a-f]{40}$/');
    }
}

test('builds and deploys Docusaurus through GitHub Pages', function () {
    [$workflow, $contents] = loadDocumentationWorkflow('docs.yml');

    $build         = $workflow['jobs']['build'];
    $deploy        = $workflow['jobs']['deploy'];
    $context7      = $workflow['jobs']['context7'];
    $buildSteps    = $build['steps'];
    $deploySteps   = $deploy['steps'];
    $context7Steps = $context7['steps'];
    $checkout      = documentationActionStep($buildSteps, 'actions/checkout');
    $setupNode     = documentationActionStep($buildSteps, 'actions/setup-node');
    $upload        = documentationActionStep($buildSteps, 'actions/upload-pages-artifact');
    $deployment    = documentationActionStep($deploySteps, 'actions/deploy-pages');
    $refresh       = documentationRunStep($context7Steps, 'https://context7.com/api/v1/refresh');
    $commands      = array_column($buildSteps, 'run');

    expect($workflow['on']['push']['branches'])->toContain('main')
        ->and(array_key_exists('workflow_dispatch', $workflow['on']))->toBeTrue()
        ->and($workflow['permissions'])->toBe([])
        ->and($workflow['concurrency'])->toBe([
            'group'              => 'pages',
            'cancel-in-progress' => false,
        ])
        ->and($build['permissions'])->toBe([
            'contents' => 'read',
            'pages'    => 'write',
        ])
        ->and($build['defaults']['run']['working-directory'])->toBe('docs')
        ->and($checkout['with']['persist-credentials'])->toBeFalse()
        ->and($setupNode['uses'])->toBe('actions/setup-node@820762786026740c76f36085b0efc47a31fe5020')
        ->and($setupNode['with'])->toMatchArray([
            'node-version-file'     => 'docs/.node-version',
            'cache'                 => 'npm',
            'cache-dependency-path' => 'docs/package-lock.json',
        ])
        ->and($commands)->toContain('npm ci', 'npm run typecheck', 'npm run build')
        ->and($upload['with']['path'])->toBe('docs/build')
        ->and($deploy['needs'])->toBe('build')
        ->and($deploy['permissions'])->toBe([
            'id-token' => 'write',
            'pages'    => 'write',
        ])
        ->and($deploy['environment']['name'])->toBe('github-pages')
        ->and($deployment['id'])->toBe('deployment')
        ->and($context7['needs'])->toBe('deploy')
        ->and($context7['permissions'])->toBe(['id-token' => 'write'])
        ->and($refresh['env'])->toBe([
            'CONTEXT7_API_KEY' => '${{ secrets.CONTEXT7_API_KEY }}',
        ])
        ->and($refresh['run'])->toContain(
            'curl --silent --show-error',
            'https://context7.com/api/v1/refresh',
            'Authorization: Bearer ${CONTEXT7_API_KEY}',
            '{"libraryName":"/llmstxt/feeds_dragon-code_pro_llms_txt"}',
            'user-has-active-task',
            'exit 1',
        )
        ->and($refresh['run'])->not->toContain('--fail-with-body');

    foreach (['writerside', 'algolia', 'composer_token', 'ctx7sk-'] as $forbidden) {
        expect(strtolower($contents))->not->toContain($forbidden);
    }

    expectDocumentationActionsPinned([...$buildSteps, ...$deploySteps, ...$context7Steps]);
});

test('validates Docusaurus for documentation pull requests and manual runs', function () {
    [$workflow, $contents] = loadDocumentationWorkflow('test-docs.yml');

    $build      = $workflow['jobs']['build'];
    $steps      = $build['steps'];
    $checkout   = documentationActionStep($steps, 'actions/checkout');
    $setupNode  = documentationActionStep($steps, 'actions/setup-node');
    $commands   = array_column($steps, 'run');
    $eventNames = array_keys($workflow['on']);

    expect($eventNames)->toContain('pull_request', 'workflow_dispatch')
        ->and($eventNames)->not->toContain('push')
        ->and($workflow['permissions'])->toBe(['contents' => 'read'])
        ->and($workflow['concurrency']['cancel-in-progress'])->toBeTrue()
        ->and($workflow['on']['pull_request']['paths'])->toContain(
            'docs/**',
            'src/**',
            '.github/workflows/docs.yml',
            '.github/workflows/test-docs.yml'
        )
        ->and($build['container'])->toBe([
            'image'   => 'mcr.microsoft.com/playwright:v1.62.1-noble',
            'options' => '--ipc=host',
        ])
        ->and($build['defaults']['run']['working-directory'])->toBe('docs')
        ->and($checkout['with']['persist-credentials'])->toBeFalse()
        ->and($setupNode['uses'])->toBe('actions/setup-node@820762786026740c76f36085b0efc47a31fe5020')
        ->and($setupNode['with'])->toMatchArray([
            'node-version-file'     => 'docs/.node-version',
            'cache'                 => 'npm',
            'cache-dependency-path' => 'docs/package-lock.json',
        ])
        ->and($commands)->toContain('npm ci', 'npm run typecheck', 'npm run build');

    foreach (['writerside', 'algolia', 'composer_token', 'secrets.', 'actions/deploy-pages'] as $forbidden) {
        expect(strtolower($contents))->not->toContain($forbidden);
    }

    expectDocumentationActionsPinned($steps);
});
