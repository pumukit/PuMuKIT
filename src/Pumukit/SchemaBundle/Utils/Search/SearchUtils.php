<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Utils\Search;

use MongoDB\BSON\Regex;

/**
 * Class Search.
 */
class SearchUtils
{
    private static $cleanTildes = [
        'á',
        'é',
        'í',
        'ó',
        'ú',
        'ü',
    ];

    private static $cleanTildesReplace = [
        'a',
        'e',
        'i',
        'o',
        'u',
        'u',
    ];

    private static $mapping = [
        'a',
        'e',
        'i',
        'o',
        'u',
    ];

    private static $specialCharacter = [
        '[aá]',
        '[eé]',
        '[ií]',
        '[oó]',
        '[uúü]',
    ];

    private static $delimiter = ' ';
    private static $glue = '|';
    private static $maxTokens = 0;
    private static $filterSizeStopWords = 2;

    /**
     * @param string $string
     *
     * @return Regex
     */
    public static function generateRegexExpression($string)
    {
        $elements = str_getcsv(preg_quote($string), self::$delimiter);

        if (self::$maxTokens > 0) {
            $elements = array_slice($elements, 0, self::$maxTokens);
        }

        $elements = array_filter($elements, 'self::filterStopWords');
        $elements = array_map('self::scapeTildes', $elements);

        $regexString = self::completeRegexExpression($elements);

        return new Regex($regexString, 'i');
    }

    /**
     * @param string $element
     *
     * @return bool
     */
    public static function filterStopWords($element)
    {
        if (strlen($element) > self::$filterSizeStopWords) {
            return true;
        }

        return false;
    }

    /**
     * @param string $element
     *
     * @return string
     */
    public static function scapeTildes($element)
    {
        $element = str_ireplace(self::$cleanTildes, self::$cleanTildesReplace, $element);

        return str_ireplace(self::$mapping, self::$specialCharacter, $element);
    }

    /**
     * @param array $regexString
     *
     * @return string
     */
    private static function completeRegexExpression($regexString)
    {
        return implode($regexString, self::$glue);
    }
}
