<?php

declare(strict_types=1);

namespace Dashcore\Ui;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * What the form components need to know about the control they draw: what it
 * is called, and whether validation had anything to say about it.
 *
 * A Livewire field is named by its wire:model, modifiers and all
 * (`wire:model.live.debounce.300ms="form.email"`), and a plain form field by
 * its name. The package does not require Livewire, so this reads the
 * attribute itself rather than through Livewire's wire() macro.
 */
class Field
{
    /**
     * The key validation files this field's errors under.
     */
    public static function key(ComponentAttributeBag $attributes): ?string
    {
        foreach ($attributes->getAttributes() as $name => $value) {
            if (($name === 'wire:model' || str_starts_with($name, 'wire:model.')) && is_string($value)) {
                return $value;
            }
        }

        $name = $attributes->get('name');

        // `tags[]` is filed under `tags`; `address[city]` under `address.city`.
        return is_string($name) && $name !== ''
            ? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.')
            : null;
    }

    /**
     * The id a label points at: the one given, or one made from the key.
     */
    public static function id(ComponentAttributeBag $attributes, ?string $key): string
    {
        return (string) ($attributes->get('id') ?? 'dc-'.($key !== null ? Str::slug(str_replace(['.', '_'], '-', $key)) : Str::random(8)));
    }

    /**
     * The first error for this field. An `error` the view passes wins — a
     * string to say something else, false to say nothing.
     *
     * @param  mixed  $errors  the shared $errors bag, when there is one
     */
    public static function error(mixed $given, mixed $errors, ?string $key): ?string
    {
        if ($given === false) {
            return null;
        }

        if (filled($given)) {
            return (string) $given;
        }

        // Laravel and Livewire both share a ViewErrorBag; its default bag is
        // the one a form's errors are in.
        if ($errors instanceof ViewErrorBag) {
            $errors = $errors->getBag('default');
        }

        if ($key === null || ! $errors instanceof MessageBag) {
            return null;
        }

        return $errors->first($key) ?: null;
    }
}
