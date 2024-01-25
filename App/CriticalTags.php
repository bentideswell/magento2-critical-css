<?php
/**
 *
 */
namespace FishPig\CriticalCss\App;

class CriticalTags
{
    /**
     *
     */
    const LOCATION_SYMBOL = '@';
    const CRITICAL = '/* @critical */';
    const CRITICAL_WITH_LOCATION = '/* @critical:' . self::LOCATION_SYMBOL . '%s */';

    /**
     *
     */
    const CRITICAL_START = '/* @critical:start */';
    const CRITICAL_STOP = '/* @critical:stop */';
    const CRITICAL_START_WITH_LOCATION = '/* @critical:start:' . self::LOCATION_SYMBOL . '%s */';

    /**
     *
     */
    const CRITICAL_RULE_START_WITH_LOCATION = '/* @critical:rule:start:' . self::LOCATION_SYMBOL . '%s ';
    const CRITICAL_RULE_END   = ' @critical:rule:end */';

    /**
     *
     */
    const GLOBAL_LOCATION = 'global';

    /**
     *
     */
    const DIRECTIVE_PLACEHOLDER = '.__directive-placeholder--%s--end';

    /**
     *
     */
    private $protectedDirectives = [
        'font-face'
    ];

    /**
     *
     */
    private $protectedDirectiveRegexString = null;

    /**
     *
     */
    public function getProtectedDirectives(): array
    {
        return $this->protectedDirectives;
    }

    /**
     *
     */
    public function getProtectedDirectivesRegexString(): string
    {
        if ($this->protectedDirectiveRegexString === null) {
            $this->protectedDirectiveRegexString = implode(
                '|',
                array_map(
                    function ($directive) {
                        return preg_quote($directive, '/');
                    },
                    $this->getProtectedDirectives()
                )
            );
        }

        return $this->protectedDirectiveRegexString;
    }

    /**
     *
     */
    public function resolveLocations($locations = null): array
    {
        $locations = (array)$locations;
        $locations[] = self::GLOBAL_LOCATION;

        return array_unique(
            array_filter(
                (array)$locations
            )
        ) ?: [self::GLOBAL_LOCATION];
    }

    /**
     *
     */
    public static function getEscapedComment(string $comment): string
    {
        return preg_quote($comment, '/');
    }

    /**
     *
     */
    public static function getEscapedLocationComment(string $comment): string
    {
        // Converts the sprintf template into a usable regex pattern
        $marker = 'ABFJDHDBHFHFJF';
        $comment = str_replace('%s', $marker, $comment);
        $comment = self::getEscapedComment($comment);
        $comment = str_replace($marker, '(?P<location>[a-zA-Z0-9\-_]+)', $comment);

        return $comment;
    }

    /**
     *
     */
    public static function getCriticalTag(?string $location = null): string
    {
        if ($location) {
            return sprintf(self::CRITICAL_WITH_LOCATION, $location);
        }

        return self::CRITICAL;
    }

    /**
     *
     */
    public static function getCriticalStartTag(?string $location = null): string
    {
        if ($location) {
            return sprintf(self::CRITICAL_START_WITH_LOCATION, $location);
        }

        return self::CRITICAL_START;
    }

    /**
     *
     */
    public static function getCriticalRuleStartTag(?string $location = null): string
    {
        if ($location) {
            return sprintf(self::CRITICAL_RULE_START_WITH_LOCATION, $location);
        }

        throw new \InvalidArgumentException(
            'The critical rule start tag can only be used with a location but no location was specified.'
        );
    }


    /**
     *
     */
    public static function isLineCssRule(string $cssLine): bool
    {
        return strpos($cssLine, '.lib-css(') !== false
               || (strpos($cssLine, ':') !== false && strpos($cssLine, ';') !== false);
    }
}