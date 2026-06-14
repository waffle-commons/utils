<?php

declare(strict_types=1);

namespace Waffle\Commons\Utils\Service;

/**
 * Resolves the fully-qualified class/interface/trait/enum name from a PHP file
 * using the native PHP tokenizer (`token_get_all`).
 *
 * Extracted from {@see \Waffle\Commons\Utils\Trait\ReflectionTrait} as part of
 * the Beta-1 Strategy refactor (audit: "Eradicate ReflectionTrait", Roadmap §1.2).
 * Token-based parsing avoids the ReDoS surface of regex-based scanners and
 * correctly handles PHP 8.x features (readonly classes, enums, bracketed
 * namespaces).
 */
final readonly class ClassParser
{
    /**
     * @return string FQCN, or `''` when no class/interface/trait/enum is found.
     */
    public function className(string $path): string
    {
        if (!file_exists($path) || !is_readable($path)) {
            return '';
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return '';
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $class = '';
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                while (++$i < $count) {
                    if ($tokens[$i] === ';' || $tokens[$i] === '{') {
                        $namespace = mb_trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                continue;
            }

            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], strict: true)) {
                $j = $i;
                while (++$j < $count) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break 2;
                    }
                    if ($tokens[$j] === '{' || is_array($tokens[$j]) && $tokens[$j][0] === T_CURLY_OPEN) {
                        break;
                    }
                }
            }
        }

        if ($class === '') {
            return '';
        }

        return $namespace !== '' ? $namespace . '\\' . $class : $class;
    }
}
