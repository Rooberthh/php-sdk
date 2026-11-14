<?php

declare(strict_types=1);

use Http\Client\Exception\TransferException;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Rooberthh\Sdk\Http\File;
use Rooberthh\Sdk\Tests\Fixtures\Acme\AcmeConnector;
use Rooberthh\Sdk\Tests\Fixtures\Acme\DataObjects\Project;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Enums\ProjectStatus;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Payloads\CreateProjectPayload;
use Rooberthh\Sdk\Tests\Fixtures\Acme\Requests\Projects\GetProject;

/**
 * A JSON response with the body of `tests/Fixtures/Acme/Responses/{name}.json`.
 *
 * @param string $name
 * @param int    $status
 */
function acmeFixture(string $name, int $status = 200): ResponseInterface
{
    return new Response(
        $status,
        ['Content-Type' => 'application/json'],
        (string) file_get_contents(__DIR__ . "/../Fixtures/Acme/Responses/{$name}.json"),
    );
}

beforeEach(function () {
    $this->http = mockHttp();

    $this->acme = new AcmeConnector('secret-token', $this->http);
});

it('lists projects', function () {
    $this->http->addResponse(acmeFixture('projects'));

    $projects = $this->acme->projects()->list();

    $sent = $this->http->getLastRequest();

    expect($sent->getMethod())->toBe('GET')
        ->and((string) $sent->getUri())->toBe('https://api.acme.test/v1/projects')
        ->and($projects)->toHaveCount(2)
        ->and($projects)->each->toBeInstanceOf(Project::class)
        ->and($projects[0]->id)->toBe('prj_1')
        ->and($projects[0]->status)->toBe(ProjectStatus::Active)
        ->and($projects[0]->createdAt->format('Y-m-d'))->toBe('2026-11-01')
        ->and($projects[1]->description)->toBeNull()
        ->and($projects[1]->status)->toBe(ProjectStatus::Archived);
});

it('gets one project', function () {
    $this->http->addResponse(acmeFixture('project'));

    $project = $this->acme->projects()->get('prj_1');

    expect((string) $this->http->getLastRequest()->getUri())->toBe('https://api.acme.test/v1/projects/prj_1')
        ->and($project->name)->toBe('Website relaunch');
});

it('encodes the project id into the path', function () {
    $this->http->addResponse(acmeFixture('project'));

    $this->acme->projects()->get('prj/../admin');

    expect((string) $this->http->getLastRequest()->getUri())
        ->toBe('https://api.acme.test/v1/projects/prj%2F..%2Fadmin');
});

it('creates a project', function () {
    $this->http->addResponse(acmeFixture('project', 201));

    $project = $this->acme->projects()->create(new CreateProjectPayload('Website relaunch', 'New marketing site'));

    $sent = $this->http->getLastRequest();

    expect($sent->getMethod())->toBe('POST')
        ->and((string) $sent->getUri())->toBe('https://api.acme.test/v1/projects')
        ->and($sent->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(json_decode((string) $sent->getBody(), true))->toBe([
            'name' => 'Website relaunch',
            'description' => 'New marketing site',
        ])
        ->and($project->id)->toBe('prj_1');
});

it('uploads a project logo', function () {
    $this->http->addResponse(acmeFixture('project'));

    $this->acme->projects()->uploadLogo('prj_1', File::fromPath(__DIR__ . '/../Fixtures/Acme/files/logo.png'), 'Acme logo');

    $sent = $this->http->getLastRequest();
    $boundary = str_replace('multipart/form-data; boundary=', '', $sent->getHeaderLine('Content-Type'));

    expect($sent->getMethod())->toBe('POST')
        ->and((string) $sent->getUri())->toBe('https://api.acme.test/v1/projects/prj_1/logo')
        ->and($boundary)->not->toBe('')
        ->and((string) $sent->getBody())->toBe(
            "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"logo\"; filename=\"logo.png\"\r\n"
            . "Content-Type: image/png\r\n"
            . "\r\n"
            . "\x89PNG fake logo\r\n"
            . "--{$boundary}\r\n"
            . "Content-Disposition: form-data; name=\"alt\"\r\n"
            . "\r\n"
            . "Acme logo\r\n"
            . "--{$boundary}--\r\n",
        );
});

it('deletes a project', function () {
    $this->http->addResponse(new Response(204));

    $this->acme->projects()->delete('prj_1');

    $sent = $this->http->getLastRequest();

    expect($sent->getMethod())->toBe('DELETE')
        ->and((string) $sent->getUri())->toBe('https://api.acme.test/v1/projects/prj_1');
});

it('authenticates and identifies itself on every request', function () {
    $this->http->addResponse(acmeFixture('projects'));
    $this->http->addResponse(acmeFixture('project'));

    $this->acme->projects()->list();
    $this->acme->projects()->get('prj_1');

    expect($this->http->getRequests())->toHaveCount(2)
        ->each(fn($request) => $request
            ->getHeaderLine('Authorization')->toBe('Bearer secret-token')
            ->getHeaderLine('User-Agent')->toStartWith('acme-php/1.0.0 php-sdk/'));
});

it('fails loudly when a request has no queued response', function () {
    $this->acme->projects()->get('prj_1');
})->throws(TransferException::class, 'No response queued for this request.');

it('sends a Request on its own, without the Resource', function () {
    $this->http->addResponse(acmeFixture('project'));

    $project = $this->acme->send(new GetProject('prj_1'));

    expect($project)->toBeInstanceOf(Project::class)
        ->and((string) $this->http->getLastRequest()->getUri())->toBe('https://api.acme.test/v1/projects/prj_1');
});
