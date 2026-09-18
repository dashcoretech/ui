<?php

declare(strict_types=1);

namespace Dashcore\Ui;

use Composer\InstalledVersions;
use Throwable;

/**
 * The installed release of this package, as the consuming app sees it.
 *
 * Rendered onto the shell as data-dc-ui so any page says which release drew
 * it, and read by the bridge so the control plane can show which apps are
 * behind. A design change is not finished until every app is on it, and this
 * is how anyone finds out whether it is.
 */
class Version
{
    public const PACKAGE = 'dashcore/ui';

    public static function installed(): ?string
    {
        try {
            if (! InstalledVersions::isInstalled(self::PACKAGE)) {
                return null;
            }

            return InstalledVersions::getPrettyVersion(self::PACKAGE);
        } catch (Throwable) {
            return null;
        }
    }
}
