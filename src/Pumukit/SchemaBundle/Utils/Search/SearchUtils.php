<?php

namespace Pumukit\SchemaBundle\Utils\Search;

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
     * @param $string
     *
     * @return \MongoRegex
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

        return new \MongoRegex($regexString);
    }

    /**
     * @param $element
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
     * @param $element
     *
     * @return mixed
     */
    public static function scapeTildes($element)
    {
        $element = str_ireplace(self::$cleanTildes, self::$cleanTildesReplace, $element);

        return str_ireplace(self::$mapping, self::$specialCharacter, $element);
    }

    /**
     * @param $regexString
     *
     * @return string
     */
    private static function completeRegexExpression($regexString)
    {
        $regexString = implode($regexString, self::$glue);

        $regexString = '/('.$regexString.')/i';

        return $regexString;
    }
}
