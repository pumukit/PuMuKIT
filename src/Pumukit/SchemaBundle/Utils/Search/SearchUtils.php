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
    );

    private static $specialCharacter = array(
        '[aá]',
        '[eé]',
        '[ií]',
        '[oó]',
        '[uú]',
    );

    private static $delimiter = ' ';
    private static $glue = '|';
    private static $maxTokens = 0;

    /**
     * @param $string
     *
     * @return \MongoRegex
     */
    public static function generateRegexExpression($string)
    {
        $elements = str_getcsv($string, self::$delimiter);

        $regex = array();
        foreach ($elements as $key => $element) {
            if (0 === self::$maxTokens || (++$key) <= self::$maxTokens) {
                $replacedElement = self::replaceCharacters($element);
                if ($replacedElement) {
                    $regex[] = $replacedElement;
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
    private static function replaceCharacters($element)
    {
        if (strlen($element) > 2) {
            return str_ireplace(self::$mapping, self::$specialCharacter, $element);
        }

        return null;
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
