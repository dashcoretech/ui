<?php

declare(strict_types=1);

namespace Dashcore\Ui\Testing;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Assert;

/**
 * Reads a rendered page the way the fleet's conformance check does.
 *
 * Each consuming app calls Shell::assertConforms() from its own suite against
 * a real authenticated page. A package test can only prove the component
 * renders; it cannot prove an app still uses it, and an app that quietly
 * forks its own sidebar is exactly the drift this package exists to stop.
 */
class Shell
{
    private DOMXPath $xpath;

    public function __construct(string $html)
    {
        $dom = new DOMDocument;

        // A page is HTML5 that libxml grumbles about (<svg>, boolean
        // attributes); the parse is fine, so drop the warnings.
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $this->xpath = new DOMXPath($dom);
    }

    public static function of(string $html): self
    {
        return new self($html);
    }

    /**
     * The page is drawn by the shared shell, and the menu appears exactly once.
     */
    public static function assertConforms(string $html): void
    {
        $shell = self::of($html);

        Assert::assertSame(1, $shell->count('//*[@data-dc-shell]'), 'The page is not drawn by <x-dashcore::shell>.');
        Assert::assertSame(1, $shell->count('//aside[@data-dc-sidebar]//nav[@data-dc-nav]'), 'The shell sidebar is missing its menu.');
        Assert::assertSame(1, $shell->count('//nav'), 'The page renders a second <nav> — a menu outside the shell is a fork of it.');
        Assert::assertSame(1, $shell->count('//input[@type="checkbox"][@data-dc-drawer]'), 'The drawer toggle is missing; below lg the menu would be unreachable.');
    }

    /**
     * The labels of the menu's section headings, in order.
     *
     * @return list<string>
     */
    public function groups(): array
    {
        return $this->texts('//nav[@data-dc-nav]//p');
    }

    /**
     * Every menu link label, in document order.
     *
     * @return list<string>
     */
    public function links(): array
    {
        return $this->texts('//nav[@data-dc-nav]//a');
    }

    /**
     * Every menu link href, in document order.
     *
     * @return list<string>
     */
    public function hrefs(): array
    {
        return array_map(
            fn (DOMElement $a) => $a->getAttribute('href'),
            $this->elements('//nav[@data-dc-nav]//a[@href]'),
        );
    }

    /**
     * The link labels filed under one heading.
     *
     * @return list<string>
     */
    public function linksUnder(string $group): array
    {
        foreach ($this->elements('//nav[@data-dc-nav]//p') as $heading) {
            if (trim($heading->textContent) === $group) {
                return array_map(
                    fn (DOMElement $a) => trim($a->textContent),
                    iterator_to_array($this->xpath->query('../div//a', $heading)),
                );
            }
        }

        return [];
    }

    /**
     * The labels the menu marks as the current page.
     *
     * @return list<string>
     */
    public function active(): array
    {
        return $this->texts('//nav[@data-dc-nav]//a[@aria-current="page"]');
    }

    public function version(): ?string
    {
        $shell = $this->elements('//*[@data-dc-shell]')[0] ?? null;

        return $shell?->hasAttribute('data-dc-ui') ? $shell->getAttribute('data-dc-ui') : null;
    }

    public function count(string $query): int
    {
        return $this->xpath->query($query)->length;
    }

    /**
     * @return list<DOMElement>
     */
    private function elements(string $query): array
    {
        return array_values(array_filter(
            iterator_to_array($this->xpath->query($query)),
            fn ($node) => $node instanceof DOMElement,
        ));
    }

    /**
     * @return list<string>
     */
    private function texts(string $query): array
    {
        return array_map(fn (DOMElement $el) => trim($el->textContent), $this->elements($query));
    }
}
