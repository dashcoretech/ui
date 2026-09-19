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

describe('sections', function () {
    it('keeps consecutive top-level entries together as one list', function () {
        // Three links in a row are one list, not three sections each with a
        // section's gap above it.
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'Home', 'href' => '/'],
            ['label' => 'Inbox', 'href' => '/inbox'],
            ['label' => 'Senders', 'href' => '/senders'],
            ['label' => 'Automate', 'items' => [['label' => 'Rules', 'href' => '/rules']]],
            ['label' => 'Docs', 'href' => '/docs'],
        ]]);

        $shell = Shell::of($html);

        expect($shell->count('//nav[@data-dc-nav]/*'))->toBe(3)
            ->and($shell->count('//nav[@data-dc-nav]/div[1]//a'))->toBe(3)
            ->and($shell->links())->toBe(['Home', 'Inbox', 'Senders', 'Rules', 'Docs']);
    });

    it('folds a collapsed section, and opens it on a page inside it', function () {
        $menu = [['label' => 'More', 'collapsed' => true, 'items' => [
            ['label' => 'Services', 'route' => 'services.index'],
        ]]];

        $elsewhere = Shell::of(Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => $menu]));

        $this->get('/services/7');
        $inside = Shell::of(Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => $menu]));

        expect($elsewhere->count('//nav//details[not(@open)]'))->toBe(1)
            ->and($inside->count('//nav//details[@open]'))->toBe(1)
            ->and($inside->groups())->toBe(['More'])
            ->and($inside->linksUnder('More'))->toBe(['Services']);
    });

    it('puts one quiet link beside a heading', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'Mailboxes', 'link' => ['label' => 'Manage', 'href' => '/mailboxes'], 'items' => [
                ['label' => 'ops@', 'href' => '/m/1'],
            ]],
        ]]);

        $shell = Shell::of($html);

        expect($shell->count('//a[@data-dc-heading-link][@href="/mailboxes"]'))->toBe(1)
            ->and($shell->links())->toBe(['ops@'])
            ->and($shell->linksUnder('Mailboxes'))->toBe(['ops@']);
    });

    it('gives an entry a second line, in a tone when it is a problem', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'ops@', 'href' => '/m/1', 'hint' => 'Purpose not set', 'tone' => 'warning'],
            ['label' => 'me@', 'href' => '/m/2', 'hint' => 'Personal', 'tone' => 'shouting'],
        ]]);

        $shell = Shell::of($html);

        expect($shell->links())->toBe(['ops@', 'me@'])
            ->and($shell->count('//*[@data-dc-nav-hint][contains(@class, "text-dc-warning")][normalize-space(.)="Purpose not set"]'))->toBe(1)
            ->and($shell->count('//*[@data-dc-nav-hint][contains(@class, "text-dc-ink-muted")][normalize-space(.)="Personal"]'))->toBe(1);
    });
});

describe('icons', function () {
    it('draws one path, or one per entry in a list', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'One', 'href' => '/one', 'icon' => 'M3 3v18h18'],
            ['label' => 'Two', 'href' => '/two', 'icon' => ['M15 12a3 3 0 1 1-6 0', 'M2 12s4-7 10-7 10 7 10 7']],
            ['label' => 'None', 'href' => '/none'],
        ]]);

        $shell = Shell::of($html);

        expect($shell->count('//a[@href="/one"]/svg/path'))->toBe(1)
            ->and($shell->count('//a[@href="/two"]/svg/path'))->toBe(2)
            ->and($shell->count('//a[@href="/two"]/svg/path[@d="M2 12s4-7 10-7 10 7 10 7"]'))->toBe(1)
            ->and($shell->count('//a[@href="/none"]/svg'))->toBe(0);
    });
});

describe('counts', function () {
    it('shows a count beside an entry, and nothing for zero', function () {
        $html = Blade::render('<x-dashcore::shell :menu="$menu" />', ['menu' => [
            ['label' => 'Inbox', 'href' => '/inbox', 'badge' => 3],
            ['label' => 'Alerts', 'href' => '/alerts', 'badge' => 0],
            ['label' => 'Archive', 'href' => '/archive'],
        ]]);

        expect(substr_count($html, 'dc-nav-badge'))->toBe(1)
            ->and(Shell::of($html)->count('//a[@href="/inbox"]/span[@class="dc-nav-badge"][normalize-space(.)="3"]'))->toBe(1);
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

    it('lights only the most specific of the entries the prefix rule matches', function (array $menu, string $path, array $lit) {
        app()->instance('test.menu', $menu);

        expect(Shell::of($this->get($path)->getContent())->active())->toBe($lit);
    })->with([
        // `leads` prefixes `leads.board`; the board is its own entry.
        'a child that is its own entry' => [[
            ['label' => 'Leads', 'route' => 'leads'],
            ['label' => 'Pipeline', 'route' => 'leads.board'],
        ], '/leads/board', ['Pipeline']],
        'the parent on a page no entry names' => [[
            ['label' => 'Leads', 'route' => 'leads'],
            ['label' => 'Pipeline', 'route' => 'leads.board'],
        ], '/leads/7', ['Leads']],
        // `pto.index` covers every pto.* page except the ones with their own entry.
        'an index beside a sibling' => [[
            ['label' => 'PTO', 'items' => ['pto.index' => 'Requests', 'pto.my' => 'My time off']],
        ], '/pto/mine', ['My time off']],
        'the index on its own page' => [[
            ['label' => 'PTO', 'items' => ['pto.index' => 'Requests', 'pto.my' => 'My time off']],
        ], '/pto', ['Requests']],
        'the index on a page beneath it' => [[
            ['label' => 'PTO', 'items' => ['pto.index' => 'Requests', 'pto.my' => 'My time off']],
        ], '/pto/3', ['Requests']],
        // The same route in two places is equally specific in both.
        'one route in two sections' => [[
            ['label' => 'Mine', 'items' => ['pto.my' => 'My time off']],
            ['label' => 'PTO', 'items' => ['pto.index' => 'Requests', 'pto.my' => 'Yours']],
        ], '/pto/mine', ['My time off', 'Yours']],
        'across sections, by path as well as by route' => [[
            ['label' => 'Leads', 'route' => 'leads'],
            ['label' => 'Views', 'items' => [['label' => 'Board', 'href' => '/leads/board']]],
        ], '/leads/board', ['Board']],
        // A prefix stops at a segment boundary.
        'not a longer name that merely starts the same' => [[
            ['label' => 'Leads', 'route' => 'leads'],
        ], '/lead-sources', []],
    ]);

    it('leaves an entry lit by match or active alone', function () {
        // The app has spoken: `match` and `active` keep their meaning, and
        // neither dims the other nor is dimmed by a more specific default.
        app()->instance('test.menu', [
            ['label' => 'Everything', 'route' => 'home', 'match' => 'leads*'],
            ['label' => 'Leads', 'route' => 'leads'],
            ['label' => 'Pipeline', 'route' => 'leads.board'],
            ['label' => 'Pinned', 'href' => '/x', 'active' => true],
        ]);

        expect(Shell::of($this->get('/leads/board')->getContent())->active())->toBe(['Everything', 'Pipeline', 'Pinned']);
    });

    it('opens a folded section only when its entry is the one that stays lit', function () {
        app()->instance('test.menu', [
            ['label' => 'Leads', 'route' => 'leads'],
            ['label' => 'More', 'collapsed' => true, 'items' => [['label' => 'All leads', 'route' => 'leads']]],
            ['label' => 'Board', 'items' => [['label' => 'Pipeline', 'route' => 'leads.board']]],
        ]);

        $shell = Shell::of($this->get('/leads/board')->getContent());

        expect($shell->count('//nav//details[not(@open)]'))->toBe(1)
            ->and($shell->active())->toBe(['Pipeline']);
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

    it('allows the page its own navigation inside main, labelled or not', function () {
        // Pagination labels itself; Flux's navlist on a settings page does not.
        // Both are the page's, not a second menu.
        $html = Blade::render('<x-dashcore::shell><nav aria-label="Pagination Navigation"><a href="?page=2">2</a></nav><nav data-flux-navlist><a href="/settings/profile">Profile</a></nav></x-dashcore::shell>');

        Shell::assertConforms($html);

        expect(true)->toBeTrue();
    });

    it('fails a second nav that calls itself the main menu', function () {
        $html = Blade::render('<x-dashcore::shell><nav aria-label="Main"><a href="/">fork</a></nav></x-dashcore::shell>');

        expect(fn () => Shell::assertConforms($html))->toThrow(AssertionFailedError::class);
    });

    it('says so plainly when handed an XPath that does not compile', function () {
        expect(fn () => Shell::of('<p></p>')->count('//a[@href'))
            ->toThrow(InvalidArgumentException::class, 'Not a valid XPath expression');
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

    it('shows a warning and an error in their own colours', function () {
        session()->flash('warning', 'Only admins can do that.');
        session()->flash('error', 'The sync failed.');

        $html = Blade::render('<x-dashcore::flash />');

        expect($html)->toContain('data-dc-flash="warning"', 'border-dc-warning', 'Only admins can do that.')
            ->toContain('data-dc-flash="error"', 'border-dc-danger', 'The sync failed.');
    });

    it('shows nothing when there is nothing to say', function () {
        expect(trim(Blade::render('<x-dashcore::flash />')))->toBe('');
    });
});

describe('the account and theme components', function () {
    it('puts the signed-in person above their session actions', function () {
        $html = Blade::render('<x-dashcore::account name="Ada" email="ada@example.test"><a class="dc-nav-item" href="/profile">Profile</a></x-dashcore::account>');

        expect($html)->toContain('data-dc-account', 'Ada', 'ada@example.test', 'href="/profile"');
    });

    it('leaves the email line out when there is none', function () {
        expect(Blade::render('<x-dashcore::account name="Ada" />'))->not->toContain('text-xs');
    });

    it('renders the toggle as a nav-item button ui.js can find', function () {
        $html = Blade::render('<x-dashcore::theme-toggle />');

        expect($html)->toContain('type="button"', 'dc-nav-item', 'data-dc-theme-toggle', 'data-dc-theme-label');
    });

    it('carries both labels, so CSS can name the right one before any script runs', function () {
        $html = Blade::render('<x-dashcore::theme-toggle />');

        expect($html)->toContain('dc-theme-to-dark', 'Dark mode', 'dc-theme-to-light', 'Light mode');
    });

    it('applies a saved choice under the key the app names, defaulting as told', function () {
        // An app moving onto the shell keeps its old key, so nobody's saved
        // preference is lost in the move.
        $html = Blade::render('<x-dashcore::theme-script storage-key="app-theme" default="dark" />');

        expect($html)->toContain("'app-theme'", "'dark'")
            ->toContain('data-dc-theme-key="app-theme"', 'data-dc-theme-default="dark"');
    });
});
