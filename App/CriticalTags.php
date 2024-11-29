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
    const TAG = '@critical';
    const PREFIX = '/*! ' . self::TAG;

    /**
     *
     */
    const LOCATION_SYMBOL = '@';
    const CRITICAL = self::PREFIX . ' */';
    const CRITICAL_WITH_LOCATION = self::PREFIX . ':' . self::LOCATION_SYMBOL . '%s */';

    /**
     *
     */
    const CRITICAL_START = self::PREFIX . ':start */';
    const CRITICAL_STOP = self::PREFIX . ':stop */';
    const CRITICAL_START_WITH_LOCATION = self::PREFIX . ':start:' . self::LOCATION_SYMBOL . '%s */';

    /**
     *
     */
    const CRITICAL_RULE_START_WITH_LOCATION = self::PREFIX . ':rule:start:' . self::LOCATION_SYMBOL . '%s: ';
    const CRITICAL_RULE_END   = ' :' . self::TAG . ':rule:end */';

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
        if (!$locations) {
            return [self::GLOBAL_LOCATION];
        }

        $locations = (array)$locations;

        foreach ([null, ''] as $location) {
            if (in_array($location, $locations)) {
                array_unshift($locations, self::GLOBAL_LOCATION);
                break;
            }
        }

        return array_values(
            array_unique(
                array_filter($locations)
            )
        );
    }

    /**
     *
     */
    public static function getEscapedComment(string $comment = self::CRITICAL): string
    {
        return preg_quote($comment, '/');
    }

    /**
     *
     */
    public static function getEscapedLocationComment(string $comment = self::CRITICAL_WITH_LOCATION): string
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
    public static function isLineCssRule(string $input): bool
    {
        if (strpos($input, '.lib-css(') !== false) {
            // Match a .lib-css line. This is a rule, although var may not be
            // set and this end up as a blank line that we remove later. It is
            // very difficult to know for sure at this point so assume it is.
            return true;
        }

        if (strpos($input, '@') !== false && preg_match('/[ ]*@[a-zA-Z0-9_\-]+:/', $input)) {
            // This looks like a LESS variable setter so we don't want to mark it is critical.
            return false;
        }

        return strpos($input, ':') !== false && strpos($input, ';') !== false;
    }
}