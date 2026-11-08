<?php

namespace App\Core\Themes;

use Illuminate\Translation\FileLoader;

/**
 * Preklady šablóny (menný priestor „theme::“) sa skladajú z celého reťazca šablón:
 * odvodená šablóna prepíše iba texty, ktoré zmenila, ostatné prevezme od rodiča.
 */
class ThemeTranslationLoader extends FileLoader
{
    /** @var (\Closure(): list<string>)|null */
    private ?\Closure $themePaths = null;

    /**
     * @param  \Closure(): list<string>  $paths
     */
    public function useThemePaths(\Closure $paths): void
    {
        $this->themePaths = $paths;
    }

    public function load($locale, $group, $namespace = null)
    {
        if ($namespace !== 'theme' || $this->themePaths === null) {
            return parent::load($locale, $group, $namespace);
        }

        $lines = [];

        foreach (($this->themePaths)() as $path) {
            $file = $path.'/'.$locale.'/'.$group.'.php';

            if ($this->files->exists($file)) {
                $lines = array_replace_recursive($lines, (array) $this->files->getRequire($file));
            }
        }

        return $lines;
    }
}
