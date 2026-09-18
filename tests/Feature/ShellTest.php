<?php

declare(strict_types=1);

use Dashcore\Ui\Testing\Shell;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\AssertionFailedError;

describe('the menu', function () {
    it('renders headings and links in the order the app gave them', function () {
        $shell = Shell::of($this->get('/')->assertOk()->getContent());

        expect($shell->groups())->toBe(['Fleet', 'Activity'])
            ->and($shell->links())->toBe(['Overview', 'Services', 'Audit', 'Help'])
            ->and($shell->linksUnder('Fleet'))->toBe(['Services']);
    });

    it('drops an entry whose route does not exist rather than throwing', function () {
        // A menu shared across environments should not 500 because one
        // feature is switched off in this one.
        $shell = Shell::of($this->get('/')->getContent());

        expect($shell->links())->not->toContain('Switched off here');
    });

    it('drops a heading left with nothing under it', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'Empty', 'items' => ['missing.route' => 'Gone']],
            ['label' => 'Overview', 'route' => 'home'],
        ]]);

        expect(Shell::of($html)->groups())->toBe([]);
    });
});

describe('where you are', function () {
    it('marks the current page, and only it', function (string $path, string $label) {
        expect(Shell::of($this->get($path)->getContent())->active())->toBe([$label]);
    })->with([
        ['/', 'Overview'],
        ['/services', 'Services'],
        ['/audit', 'Audit'],
        ['/help', 'Help'],
    ]);

    it('keeps an index lit on the pages beneath it', function () {
        // By route-name prefix: services.show lights services.index.
        expect(Shell::of($this->get('/services/7')->getContent())->active())->toBe(['Services']);
    });

    it('lets an app say outright which entry is current', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'Overview', 'route' => 'home', 'active' => false],
            ['label' => 'Elsewhere', 'href' => '/x', 'active' => true],
        ]]);

        expect(Shell::of($html)->active())->toBe(['Elsewhere']);
    });

    it('marks it with aria-current, which is what the style keys off', function () {
        // The accent rule is drawn by [aria-current=page], so a screen reader
        // and a sighted user are told the same thing by the same attribute.
        $shell = Shell::of($this->get('/audit')->getContent());

        expect($shell->count('//a[@aria-current="page"][contains(@class, "dc-nav-item")]'))->toBe(1);
    });
});

describe('the shell', function () {
    it('conforms by its own definition', function () {
        Shell::assertConforms($this->get('/')->getContent());
    });

    it('sits on the left and pins from lg', function () {
        $shell = Shell::of($this->get('/')->getContent());

        expect($shell->count('//aside[@data-dc-sidebar][contains(@class, "fixed") and contains(@class, "left-0") and contains(@class, "w-60") and contains(@class, "lg:translate-x-0")]'))->toBe(1)
            ->and($shell->count('//div[contains(@class, "lg:pl-60")]/main'))->toBe(1);
    });

    it('opens as a drawer below lg without needing script', function () {
        $shell = Shell::of($this->get('/')->getContent());

        // The menu button, the backdrop and the close button are all labels
        // for one checkbox the sidebar is a peer of.
        expect($shell->count('//input[@id="dc-drawer"][@data-dc-drawer]'))->toBe(1)
            ->and($shell->count('//label[@for="dc-drawer"]'))->toBe(3)
            ->and($shell->count('//aside[contains(@class, "peer-checked:translate-x-0")]'))->toBe(1);
    });

    it('puts the page inside main', function () {
        expect($this->get('/')->getContent())->toMatch('#<main[^>]*>\s*page body\s*</main>#');
    });

    it('renders the footer slot at the foot of the sidebar only when given', function () {
        $without = Shell::of(Blade::render('<x-dashcore::shell />'));
        $with = Shell::of(Blade::render('<x-dashcore::shell><x-slot:footer><form action="/logout"></form></x-slot:footer></x-dashcore::shell>'));

        expect($without->count('//aside//form'))->toBe(0)
            ->and($with->count('//aside//form[@action="/logout"]'))->toBe(1)
            ->and($with->count('//nav//form'))->toBe(0);
    });

    it('adds wire:navigate to every link when asked', function () {
        $plain = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => app('test.menu')]);
        $wired = Blade::render('<x-dashcore::shell :menu="$menu" navigate />', ['menu' => app('test.menu')]);

        expect(substr_count($plain, 'wire:navigate'))->toBe(0)
            ->and(substr_count($wired, 'wire:navigate'))->toBe(4 + 2); // four links, two wordmarks
    });

    it('leaves main uncontained for a full-bleed page', function () {
        $html = Blade::render('<x-dashcore::shell :contained="false">x</x-dashcore::shell>');

        expect($html)->not->toContain('max-w-7xl');
    });
});

describe('conformance', function () {
    it('fails a page that draws its own menu beside the shell', function () {
        $html = Blade::render('<x-dashcore::shell /><nav><a href="/">fork</a></nav>');

        expect(fn () => Shell::assertConforms($html))->toThrow(AssertionFailedError::class);
    });

    it('fails a page with no shell at all', function () {
        expect(fn () => Shell::assertConforms('<html><body><nav></nav></body></html>'))
            ->toThrow(AssertionFailedError::class);
    });
});

describe('the other components', function () {
    it('titles a page in the display face, with actions beside it', function () {
        $html = Blade::render('<x-dashcore::page-header title="Audit" subtitle="What happened"><x-slot:actions><a href="/x">Export</a></x-slot:actions></x-dashcore::page-header>');

        expect($html)->toContain('dc-page-title', 'Audit', 'What happened', 'Export');
    });

    it('shows a status message and validation errors', function () {
        session()->flash('status', 'Saved.');
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['name' => 'Name is required.']));

        // Shared, the way ShareErrorsFromSession hands it to every view — a
        // component does not see variables passed to its parent's render.
        view()->share('errors', $errors);

        $html = Blade::render('<x-dashcore::flash />');

        expect($html)->toContain('Saved.', 'Name is required.', 'role="alert"');
    });

    it('shows nothing when there is nothing to say', function () {
        expect(trim(Blade::render('<x-dashcore::flash />')))->toBe('');
    });
});
