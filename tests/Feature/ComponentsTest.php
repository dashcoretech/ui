<?php

declare(strict_types=1);

use Dashcore\Ui\Testing\Shell;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Shares an error bag the way ShareErrorsFromSession (or Livewire, during a
 * render) does: a component does not see variables passed to its parent.
 *
 * @param  array<string, string>  $messages
 */
function errors(array $messages): void
{
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag($messages)));
}

describe('the button', function () {
    it('is secondary, and a plain button, unless told otherwise', function () {
        $html = Blade::render('<x-dashcore::button>Cancel</x-dashcore::button>');

        expect(Shell::of($html)->count('//button[@type="button"][contains(@class, "dc-btn ")][contains(@class, "dc-btn-secondary")]'))->toBe(1);
    });

    it('takes each variant, and the names Flux used for them', function (string $variant, string $class) {
        $html = Blade::render('<x-dashcore::button :variant="$variant">Go</x-dashcore::button>', ['variant' => $variant]);

        expect($html)->toContain($class);
    })->with([
        ['primary', 'dc-btn-primary'],
        ['secondary', 'dc-btn-secondary'],
        ['danger', 'dc-btn-danger'],
        ['ghost', 'dc-btn-ghost'],
        ['outline', 'dc-btn-secondary'],
        ['subtle', 'dc-btn-ghost'],
        ['neon', 'dc-btn-secondary'],
    ]);

    it('submits when it says so, and comes small', function () {
        $html = Blade::render('<x-dashcore::button type="submit" variant="primary" size="sm">Save changes</x-dashcore::button>');

        expect(Shell::of($html)->count('//button[@type="submit"][contains(@class, "dc-btn-primary")][contains(@class, "dc-btn-sm")]'))->toBe(1);
    });

    it('is a link when given an href, carrying what it is given', function () {
        $html = Blade::render('<x-dashcore::button href="/invite" variant="primary" wire:navigate>Invite</x-dashcore::button>');

        expect(Shell::of($html)->count('//a[@href="/invite"][@*[name()="wire:navigate"]][contains(@class, "dc-btn-primary")]'))->toBe(1)
            ->and($html)->not->toContain('<button', 'type=');
    });

    it('disables itself while its own Livewire action runs', function () {
        $html = Blade::render('<x-dashcore::button wire:click="archive(7)">Archive</x-dashcore::button>');

        expect($html)->toContain('wire:click="archive(7)"', 'wire:loading.attr="disabled"', 'wire:target="archive(7)"');
    });

    it('leaves loading alone when the app has said how', function () {
        $plain = Blade::render('<x-dashcore::button>Close</x-dashcore::button>');
        $own = Blade::render('<x-dashcore::button wire:click="save" wire:loading.class="opacity-50">Save</x-dashcore::button>');

        expect($plain)->not->toContain('wire:loading')
            ->and($own)->toContain('wire:loading.class="opacity-50"')->not->toContain('wire:loading.attr');
    });
});

describe('the field and its controls', function () {
    it('labels a control, and puts a hint under it', function () {
        $html = Blade::render('<x-dashcore::input label="Email" hint="We never share it." name="email" type="email" />');
        $page = Shell::of($html);

        expect($page->count('//label[@for="dc-email"][contains(@class, "dc-label")][normalize-space(.)="Email"]'))->toBe(1)
            ->and($page->count('//input[@id="dc-email"][@name="email"][@type="email"][contains(@class, "dc-input")][@aria-describedby="dc-email-note"]'))->toBe(1)
            ->and($page->count('//p[@id="dc-email-note"][@data-dc-hint][normalize-space(.)="We never share it."]'))->toBe(1);
    });

    it('passes wire:model and its modifiers straight through', function () {
        $html = Blade::render('<x-dashcore::input wire:model.live.debounce.300ms="form.email" class="w-64" />');

        expect($html)->toContain('wire:model.live.debounce.300ms="form.email"', 'id="dc-form-email"')
            ->and(Shell::of($html)->count('//input[contains(@class, "dc-input") and contains(@class, "w-64")]'))->toBe(1);
    });

    it('finds its error by the wire:model name', function () {
        errors(['form.email' => 'The email is taken.']);

        $html = Blade::render('<x-dashcore::input label="Email" wire:model="form.email" hint="Hidden by the error." />');
        $page = Shell::of($html);

        expect($page->count('//input[@aria-invalid="true"][@aria-describedby="dc-form-email-note"]'))->toBe(1)
            ->and($page->count('//p[@id="dc-form-email-note"][@data-dc-error][contains(@class, "text-dc-danger")][normalize-space(.)="The email is taken."]'))->toBe(1)
            ->and($html)->not->toContain('Hidden by the error.');
    });

    it('finds its error by name, array names included', function () {
        errors(['name' => 'Name is required.', 'address.city' => 'City is required.']);

        expect(Blade::render('<x-dashcore::input name="name" />'))->toContain('Name is required.')
            ->and(Blade::render('<x-dashcore::input name="address[city]" />'))->toContain('City is required.');
    });

    it('says something else, or nothing, when the view says so', function () {
        errors(['email' => 'The email is taken.']);

        expect(Blade::render('<x-dashcore::input name="email" error="Try another." />'))->toContain('Try another.')->not->toContain('taken')
            ->and(Blade::render('<x-dashcore::input name="email" :error="false" />'))->not->toContain('taken', 'aria-invalid');
    });

    it('renders without an error bag at all', function () {
        expect(Blade::render('<x-dashcore::input name="email" />'))->toContain('name="email"')->not->toContain('aria-invalid');
    });

    it('gives a password a Show button when viewable', function () {
        $html = Blade::render('<x-dashcore::input type="password" name="password" viewable />');

        expect(Shell::of($html)->count('//input[@type="password"][@id="dc-password"][contains(@class, "pr-16")]/following-sibling::button[@type="button"][@data-dc-reveal][@aria-controls="dc-password"]'))->toBe(1);
    });

    it('wraps an app\'s own control, finding its error by name', function () {
        errors(['colour' => 'Pick a colour.']);

        $html = Blade::render('<x-dashcore::field label="Colour" name="colour" for="colour"><input id="colour" name="colour"></x-dashcore::field>');

        expect($html)->toContain('data-dc-field', 'for="colour"', 'Pick a colour.');
    });

    it('draws a textarea with its content', function () {
        errors(['bio' => 'Too long.']);

        $html = Blade::render('<x-dashcore::textarea label="Bio" wire:model="bio" rows="6">Hello</x-dashcore::textarea>');

        expect(Shell::of($html)->count('//textarea[@id="dc-bio"][@rows="6"][@*[name()="wire:model"]="bio"][@aria-invalid="true"][contains(@class, "dc-input")]'))->toBe(1)
            ->and($html)->toMatch('#<textarea[^>]*>Hello</textarea>#')
            ->and($html)->toContain('Too long.');
    });

    it('draws a select with an empty first choice', function () {
        $html = Blade::render('<x-dashcore::select label="Role" wire:model.live="role" placeholder="Choose a role"><option value="admin">Admin</option></x-dashcore::select>');
        $page = Shell::of($html);

        expect($page->count('//select[@id="dc-role"][@*[name()="wire:model.live"]="role"][contains(@class, "dc-input")]/option'))->toBe(2)
            ->and($page->count('//select/option[1][@value=""][normalize-space(.)="Choose a role"]'))->toBe(1);
    });

    it('draws a checkbox with its label beside it, in ink', function () {
        errors(['terms' => 'Accept the terms.']);

        $html = Blade::render('<x-dashcore::checkbox label="I accept" wire:model="terms" :checked="true" />');

        expect(Shell::of($html)->count('//label[normalize-space(.)="I accept"]/input[@type="checkbox"][@*[name()="wire:model"]="terms"][@checked][contains(@class, "accent-dc-ink")][@aria-invalid="true"]'))->toBe(1)
            ->and($html)->toContain('Accept the terms.');
    });

    it('takes a checkbox label from the slot when it needs a link', function () {
        $html = Blade::render('<x-dashcore::checkbox name="terms">I accept the <a href="/terms">terms</a></x-dashcore::checkbox>');

        expect(Shell::of($html)->count('//label/span/a[@href="/terms"]'))->toBe(1);
    });
});

describe('the switch', function () {
    it('is a checkbox with the switch role, drawn by .dc-switch', function () {
        $html = Blade::render('<x-dashcore::switch label="Email me a digest" wire:model.live="digest" />');

        expect(Shell::of($html)->count('//label[normalize-space(.)="Email me a digest"]/input[@type="checkbox"][@role="switch"][@*[name()="wire:model.live"]="digest"][contains(@class, "dc-switch")]'))->toBe(1);
    });

    it('keeps its pill: the clamp exempts it, and it never asks for rounded-full', function () {
        $css = file_get_contents(__DIR__.'/../../resources/css/ui.css');

        expect(Blade::render('<x-dashcore::switch name="on" />'))->not->toContain('rounded-full')
            ->and($css)->toContain("[role='switch'], [role='switch'] *", "[type='radio']")
            ->and($css)->toMatch('/\.dc-switch \{[^}]*border-radius: 9999px/');
    });
});

describe('the one-time code', function () {
    it('is one field the OS can fill', function () {
        $html = Blade::render('<x-dashcore::otp wire:model="code" autofocus />');

        expect(Shell::of($html)->count('//input[@type="text"][@inputmode="numeric"][@maxlength="6"][@autocomplete="one-time-code"][@*[name()="wire:model"]="code"][@autofocus]'))->toBe(1)
            ->and(Shell::of($html)->count('//label[@for="dc-code"][contains(@class, "sr-only")]'))->toBe(1);
    });

    it('takes a length, and finds its error', function () {
        errors(['code' => 'That code has expired.']);

        $html = Blade::render('<x-dashcore::otp name="code" length="8" />');

        expect($html)->toContain('maxlength="8"', 'aria-invalid="true"', 'That code has expired.');
    });
});

describe('the modal', function () {
    it('is a native dialog, opened and closed by name', function () {
        $html = Blade::render('<x-dashcore::modal name="confirm-delete" title="Delete this lead?">Gone for good.</x-dashcore::modal>');
        $page = Shell::of($html);

        expect($page->count('//dialog[@id="confirm-delete"][@data-dc-modal="confirm-delete"][@closedby="any"][@aria-labelledby="confirm-delete-title"][@*[name()="wire:ignore.self"]]'))->toBe(1)
            ->and($page->count('//dialog/h2[@id="confirm-delete-title"][normalize-space(.)="Delete this lead?"]'))->toBe(1)
            ->and($page->count('//dialog//button[@type="button"][@commandfor="confirm-delete"][@command="close"][@aria-label="Close"]'))->toBe(1)
            ->and($html)->toContain('Gone for good.', 'backdrop:bg-dc-scrim', 'max-w-md')
            ->not->toContain('data-dc-open', 'x-data', ' open');
    });

    it('opens as the page arrives when told to', function () {
        $html = Blade::render('<x-dashcore::modal name="password" :open="true" width="lg" :dismissible="false" />');

        expect(Shell::of($html)->count('//dialog[@data-dc-open][@closedby="closerequest"]'))->toBe(1)
            ->and($html)->toContain('max-w-lg');
    });

    it('binds to a Livewire property through wire:model', function () {
        $html = Blade::render('<x-dashcore::modal name="remove" wire:model="showRemoveModal" />');

        expect($html)->toContain('x-data', 'x-effect="$wire.$get(\'showRemoveModal\')', 'x-on:close="$wire.$get(\'showRemoveModal\') && $wire.$set(\'showRemoveModal\', false, false)"')
            ->not->toContain('wire:model');
    });

    it('closes a live-bound property at once', function () {
        expect(Blade::render('<x-dashcore::modal name="remove" wire:model.live="showing" />'))->toContain("\$wire.\$set('showing', false, true)");
    });

    it('puts its actions in a footer', function () {
        $html = Blade::render('<x-dashcore::modal name="m">Body<x-slot:footer><x-dashcore::button variant="danger">Delete</x-dashcore::button></x-slot:footer></x-dashcore::modal>');

        expect(Shell::of($html)->count('//dialog/div[contains(@class, "justify-end")]/button[contains(@class, "dc-btn-danger")]'))->toBe(1);
    });
});

describe('tabs', function () {
    it('is a nav with its own name, the current tab in ink', function () {
        $this->get('/services');

        $html = Blade::render('<x-dashcore::tabs label="Settings"><x-dashcore::tab href="/services">Services</x-dashcore::tab><x-dashcore::tab href="/audit">Audit</x-dashcore::tab></x-dashcore::tabs>');
        $page = Shell::of($html);

        expect($page->count('//nav[@aria-label="Settings"][@data-dc-tabs]/a'))->toBe(2)
            ->and($page->count('//a[@aria-current="page"][@href="/services"]'))->toBe(1)
            ->and($html)->toContain('aria-[current=page]:text-dc-ink', 'aria-[current=page]:border-dc-ink')
            ->not->toContain('dc-accent');
    });

    it('takes current from the view when it says', function () {
        $html = Blade::render('<x-dashcore::tabs><x-dashcore::tab href="/a" :current="true">A</x-dashcore::tab><x-dashcore::tab href="/" :current="false">B</x-dashcore::tab></x-dashcore::tabs>');

        expect(Shell::of($html)->count('//nav[@aria-label="Sections"]//a[@aria-current="page"][@href="/a"]'))->toBe(1);
    });
});

describe('the small things', function () {
    it('draws a text link in ink, not the accent', function () {
        $html = Blade::render('<x-dashcore::link href="/forgot">Forgot your password?</x-dashcore::link>');

        expect($html)->toContain('href="/forgot"', 'text-dc-ink', 'underline')->not->toContain('dc-accent');
    });

    it('draws a callout in its tone, and info as neutral', function (string $tone, string $class) {
        $html = Blade::render('<x-dashcore::callout :tone="$tone" heading="Heads up">Body</x-dashcore::callout>', ['tone' => $tone]);

        expect($html)->toContain($class, 'Heads up', 'Body')->not->toContain('role=');
    })->with([
        ['success', 'border-dc-success'],
        ['warning', 'border-dc-warning'],
        ['danger', 'border-dc-danger'],
        ['neutral', 'border-dc-metal'],
        ['info', 'border-dc-metal'],
    ]);

    it('draws a spinner as a real circle', function () {
        $html = Blade::render('<x-dashcore::spinner />');

        expect($html)->toContain('dc-circle', 'animate-spin', 'role="status"', 'aria-label="Loading"')->not->toContain('rounded-full');
    });

    it('draws a status tag in its tone, neutral by default', function (string $blade, string $tone) {
        $html = Blade::render($blade);

        expect($html)->toContain('dc-tag', "data-dc-tag=\"{$tone}\"")->not->toContain('rounded-full');
    })->with([
        ['<x-dashcore::tag>Draft</x-dashcore::tag>', 'neutral'],
        ['<x-dashcore::tag tone="success">Paid</x-dashcore::tag>', 'success'],
        ['<x-dashcore::tag tone="info">New</x-dashcore::tag>', 'neutral'],
        ['<x-dashcore::tag color="red">Overdue</x-dashcore::tag>', 'danger'],
        ['<x-dashcore::tag color="amber">Pending</x-dashcore::tag>', 'warning'],
        ['<x-dashcore::tag color="blue">Info</x-dashcore::tag>', 'neutral'],
    ]);

    it('draws an avatar as initials, square', function () {
        $html = Blade::render('<x-dashcore::avatar name="ada lovelace byron" size="lg" />');

        expect($html)->toContain('>AL<', 'rounded-sm', 'h-10 w-10', 'sr-only">ada lovelace byron')->not->toContain('rounded-full')
            ->and(Blade::render('<x-dashcore::avatar name="Ada" src="/ada.jpg" />'))->toContain('<img src="/ada.jpg" alt="Ada"');
    });
});

describe('the theme choice', function () {
    it('offers light, dark and system as buttons ui.js can find', function () {
        $page = Shell::of(Blade::render('<x-dashcore::theme-choice class="w-full" />'));

        expect($page->count('//div[@role="group"][@aria-label="Theme"][@data-dc-theme-choice][contains(@class, "dc-theme-choice") and contains(@class, "w-full")]/button[@type="button"][@aria-pressed="false"]'))->toBe(3)
            ->and($page->count('//button[@data-dc-theme-set="light"][normalize-space(.)="Light"]'))->toBe(1)
            ->and($page->count('//button[@data-dc-theme-set="dark"][normalize-space(.)="Dark"]'))->toBe(1)
            ->and($page->count('//button[@data-dc-theme-set="system"][normalize-space(.)="System"]'))->toBe(1);
    });
});

describe('the guest frame', function () {
    it('draws the wordmark and one panel, with no menu', function () {
        $html = Blade::render('<x-dashcore::guest product="HR" home="/" title="Sign in" subtitle="With your work email." navigate>form<x-slot:footer>No account?</x-slot:footer></x-dashcore::guest>');
        $page = Shell::of($html);

        expect($page->count('//div[@data-dc-guest]/main'))->toBe(1)
            ->and($page->count('//nav'))->toBe(0)
            ->and($page->count('//a[@href="/"][@*[name()="wire:navigate"]][contains(., "Dashcore")]/span[normalize-space(.)="HR"]'))->toBe(1)
            ->and($page->count('//main/div/h1[normalize-space(.)="Sign in"]'))->toBe(1)
            ->and($html)->toContain('With your work email.', 'No account?');
    });

    it('is not the shell, so the authenticated check does not pass it', function () {
        // Signed-out pages have no menu to fork. The conformance check is for
        // authenticated pages, and says so if pointed at one of these.
        $html = Blade::render('<x-dashcore::guest>form</x-dashcore::guest>');

        expect(fn () => Shell::assertConforms($html))->toThrow(AssertionFailedError::class, 'not drawn by <x-dashcore::shell>');
    });
});

describe('conformance with the components on the page', function () {
    it('holds with tabs, a modal, a form and a status tag inside main', function () {
        errors(['name' => 'Name is required.']);

        $html = Blade::render(<<<'BLADE'
            <x-dashcore::shell :menu="$menu" product="Test">
                <x-dashcore::page-header title="Settings" />
                <x-dashcore::tabs label="Settings"><x-dashcore::tab href="/a">Profile</x-dashcore::tab></x-dashcore::tabs>
                <x-dashcore::input label="Name" name="name" />
                <x-dashcore::switch label="Digest" name="digest" />
                <x-dashcore::tag tone="success">Active</x-dashcore::tag>
                <x-dashcore::button variant="danger" commandfor="confirm" command="show-modal">Delete</x-dashcore::button>
                <x-dashcore::modal name="confirm" title="Delete?" wire:model="confirming">Sure?</x-dashcore::modal>
                <x-slot:footer>
                    <x-dashcore::account name="Ada"><x-dashcore::theme-choice /></x-dashcore::account>
                </x-slot:footer>
            </x-dashcore::shell>
            BLADE, ['menu' => app('test.menu')]);

        Shell::assertConforms($html);

        expect(Shell::of($html)->count('//main//nav[@aria-label="Settings"]'))->toBe(1)
            ->and(Shell::of($html)->count('//main//dialog'))->toBe(1);
    });
});
