<?php

declare(strict_types=1);

use ModularizeRbac\Core\Application\Module\CreateModule\CreateModule;
use ModularizeRbac\Core\Application\Module\CreateModule\CreateModuleInput;
use ModularizeRbac\Core\Application\Module\ListModules\ListModulesPaginated;
use ModularizeRbac\Core\Application\Module\ModuleFilter;
use ModularizeRbac\Core\Application\Shared\Pagination;
use ModularizeRbac\Core\Exceptions\InvalidInput;
use ModularizeRbac\Core\Tests\Application\Doubles\AllowingAuthorizer;
use ModularizeRbac\Core\Tests\Application\Doubles\FixedClock;
use ModularizeRbac\Core\Tests\Application\Doubles\InMemoryModuleRepository;
use ModularizeRbac\Core\Tests\Application\Doubles\PassthroughUnitOfWork;
use ModularizeRbac\Core\Tests\Application\Doubles\RecordingEventDispatcher;
use ModularizeRbac\Core\Tests\Application\Doubles\SequentialIdGenerator;

function paginatedModuleStack(): array
{
    $modules = new InMemoryModuleRepository();
    $auth = new AllowingAuthorizer();
    $clock = FixedClock::at('2026-01-01T00:00:00Z');
    $ids = new SequentialIdGenerator();
    $create = new CreateModule($modules, $auth, new PassthroughUnitOfWork(), new RecordingEventDispatcher(), $ids, $clock);
    $list = new ListModulesPaginated($modules, $auth);

    return compact('list', 'create', 'modules');
}

it('returns the windowed slice + total count', function (): void {
    ['list' => $list, 'create' => $create] = paginatedModuleStack();
    for ($i = 0; $i < 25; $i++) {
        $create->execute(new CreateModuleInput("mod{$i}", "Mod {$i}", null, null, null, sortOrder: $i));
    }

    $result = $list->execute(new ModuleFilter(), new Pagination(limit: 10, offset: 0));

    expect($result->items)->toHaveCount(10)
        ->and($result->total)->toBe(25)
        ->and($result->pagination->limit)->toBe(10)
        ->and($result->pagination->offset)->toBe(0);
});

it('honors offset within the same filter', function (): void {
    ['list' => $list, 'create' => $create] = paginatedModuleStack();
    for ($i = 0; $i < 25; $i++) {
        $create->execute(new CreateModuleInput("mod{$i}", "Mod {$i}", null, null, null, sortOrder: $i));
    }

    $first = $list->execute(new ModuleFilter(), new Pagination(limit: 10, offset: 0));
    $second = $list->execute(new ModuleFilter(), new Pagination(limit: 10, offset: 10));

    expect($first->items[0]->slug)->not->toBe($second->items[0]->slug)
        ->and(count($second->items))->toBe(10);
});

it('applies the is_active filter', function (): void {
    ['list' => $list, 'create' => $create] = paginatedModuleStack();
    $create->execute(new CreateModuleInput('active1', 'A1', null, null, null, isActive: true));
    $create->execute(new CreateModuleInput('inactive1', 'I1', null, null, null, isActive: false));

    $active = $list->execute(new ModuleFilter(isActive: true), Pagination::default());
    $inactive = $list->execute(new ModuleFilter(isActive: false), Pagination::default());

    expect(count($active->items))->toBe(1)
        ->and($active->items[0]->slug)->toBe('active1')
        ->and(count($inactive->items))->toBe(1)
        ->and($inactive->items[0]->slug)->toBe('inactive1');
});

it('applies the slug_like filter as case-insensitive substring', function (): void {
    ['list' => $list, 'create' => $create] = paginatedModuleStack();
    $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $create->execute(new CreateModuleInput('events.weddings', 'W', null, null, null));
    $create->execute(new CreateModuleInput('billing', 'B', null, null, null));

    $result = $list->execute(new ModuleFilter(slugLike: 'event'), Pagination::default());
    $slugs = array_map(fn ($m) => $m->slug, $result->items);

    expect($slugs)->toContain('events', 'events.weddings')
        ->and($slugs)->not->toContain('billing');
});

it('applies the root_module_id filter to keep only children of a parent', function (): void {
    ['list' => $list, 'create' => $create] = paginatedModuleStack();
    $parent = $create->execute(new CreateModuleInput('events', 'Events', null, null, null));
    $create->execute(new CreateModuleInput('events.weddings', 'W', null, null, $parent->id));
    $create->execute(new CreateModuleInput('billing', 'B', null, null, null));

    $result = $list->execute(new ModuleFilter(rootModuleId: $parent->id), Pagination::default());

    expect(count($result->items))->toBe(1)
        ->and($result->items[0]->slug)->toBe('events.weddings');
});

it('rejects limits beyond the 1000 ceiling', function (): void {
    expect(fn () => new Pagination(limit: 1001))->toThrow(InvalidInput::class);
});

it('rejects negative offsets', function (): void {
    expect(fn () => new Pagination(offset: -1))->toThrow(InvalidInput::class);
});
