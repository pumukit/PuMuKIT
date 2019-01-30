<?php

namespace Pumukit\SchemaBundle\Utils\Search;

/**
 * Class Search.
 */
class SearchUtils
{
    private static $mapping = array(
        'a',
        'e',
        'i',
        'o',
        'u',
        'á',
        'é',
        'í',
        'ó',
        'ú',
        'ü',
    );

    private static $specialCharacter = array(
        '[aá]',
        '[eé]',
        '[ií]',
        '[oó]',
        '[uúü]',
        '[aá]',
        '[eé]',
        '[ií]',
        '[oó]',
        '[uúü]',
        '[uúü]',
    );

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

        $regex = array();
        foreach ($elements as $key => $element) {
            if (0 === self::$maxTokens || (++$key) <= self::$maxTokens) {
                $replacedElement = self::filterStopWords($element);
                if ($replacedElement) {
                    $regex[] = self::scapeTildes($replacedElement);
                }
            }
        }

        $regexString = self::completeRegexExpression($regex);

        return new \MongoRegex($regexString);
    }

    /**
     * @param $element
     *
     * @return mixed|null
     */
    private static function filterStopWords($element)
    {
        if (strlen($element) > self::$filterSizeStopWords) {
            return $element;
        }

        return null;
    }

    /**
     * @param $element
     *
     * @return mixed
     */
    public static function scapeTildes($element)
    {
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
