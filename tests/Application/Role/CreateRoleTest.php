<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Role\CreateRole\CreateRole;
use ModularizeRbac\Core\Application\Role\CreateRole\CreateRoleInput;
use ModularizeRbac\Core\Domain\Shared\Uuid;
use ModularizeRbac\Core\Exceptions\AuthorizationFailed;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryRoleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function makeCreateRole(?InMemoryRoleRepository $roles = null, ?AllowingAuthorizer $auth = null): array
{
    $roles ??= new InMemoryRoleRepository();
    $auth ??= new AllowingAuthorizer();

    $useCase = new CreateRole(
        roles: $roles,
        authorizer: $auth,
        uow: new PassthroughUnitOfWork(),
        ids: new SequentialIdGenerator(),
        clock: FixedClock::at('2026-06-01T00:00:00Z'),
    );

    return ['uc' => $useCase, 'roles' => $roles, 'auth' => $auth];
}

it('creates a role and returns a RoleOutput', function (): void {
    ['uc' => $uc, 'roles' => $roles] = makeCreateRole();

    $out = $uc->execute(new CreateRoleInput(
        name: 'editor',
        displayName: 'Editor',
        guard: 'admin',
        tenantId: null,
        level: 50,
    ));

    expect($out->name)->toBe('editor')
        ->and($out->displayName)->toBe('Editor')
        ->and($out->guard)->toBe('admin')
        ->and($out->level)->toBe(50)
        ->and($out->isSystem)->toBeFalse()
        ->and($roles->find(new Uuid($out->id)))->not->toBeNull();
});

it('rejects malformed role names', function (string $bad): void {
    ['uc' => $uc] = makeCreateRole();
    $uc->execute(new CreateRoleInput($bad, null, 'admin', null));
})->with([
    'empty' => '',
    'whitespace only' => '   ',
    'uppercase' => 'Editor',
    'leading digit' => '1editor',
    'spaces inside' => 'super editor',
])->throws(InvalidInput::class);

it('rejects duplicate name in same (guard, tenant)', function (): void {
    ['uc' => $uc] = makeCreateRole();
    $uc->execute(new CreateRoleInput('editor', null, 'admin', null));

    expect(fn () => $uc->execute(new CreateRoleInput('editor', 'Editor 2', 'admin', null)))
        ->toThrow(InvalidInput::class);
});

it('allows the same name across different guards', function (): void {
    ['uc' => $uc] = makeCreateRole();
    $uc->execute(new CreateRoleInput('editor', null, 'admin', null));
    $uc->execute(new CreateRoleInput('editor', null, 'web', null));

    // No exception means success.
    expect(true)->toBeTrue();
});

it('allows the same name across different tenants', function (): void {
    ['uc' => $uc] = makeCreateRole();
    $uc->execute(new CreateRoleInput('editor', null, 'admin', '11111111-1111-1111-1111-111111111111'));
    $uc->execute(new CreateRoleInput('editor', null, 'admin', '22222222-2222-2222-2222-222222222222'));

    expect(true)->toBeTrue();
});

it('honors authorization', function (): void {
    $auth = new AllowingAuthorizer();
    $auth->denyByDefault();
    ['uc' => $uc] = makeCreateRole(auth: $auth);

    expect(fn () => $uc->execute(new CreateRoleInput('editor', null, 'admin', null)))
        ->toThrow(AuthorizationFailed::class);
});
