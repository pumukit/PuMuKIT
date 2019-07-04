<?php

namespace Pumukit\SchemaBundle\Utils\Mongo;

/**
 * See https://docs.mongodb.com/manual/reference/text-search-languages/.
 */
class TextIndexUtils
{
    /**
     * Languages supported by the MongoDB text index.
     */
    public static $supportedLanguage = [
        'da', 'nl', 'en', 'fi', 'fr', 'de', 'hu', 'it',
        'nb', 'pt', 'ro', 'ru', 'es', 'sv', 'tr', 'ara',
        'prs', 'pes', 'urd', 'zhs', 'zht',
    ];

    public static function isSupportedLanguage($langCode)
    {
        $langCode = strtolower($langCode);

        return in_array($langCode, self::$supportedLanguage);
    }

    public static function getCloseLanguage($langCode)
    {
        $langCode = strtolower($langCode);

        if (in_array($langCode, self::$supportedLanguage)) {
            return $langCode;
        }

        if ('gl' == $langCode) {
            return 'pt';
        }

        return 'none';
    }

    public static function cleanTextIndex($textIndex)
    {
        $unwanted_array = [
          'Š' => 'S',
          'š' => 's',
          'Ž' => 'Z',
          'ž' => 'z',
          'À' => 'A',
          'Á' => 'A',
          'Â' => 'A',
          'Ã' => 'A',
          'Ä' => 'A',
          'Å' => 'A',
          'Æ' => 'A',
          'Ç' => 'C',
          'È' => 'E',
          'É' => 'E',
          'Ê' => 'E',
          'Ë' => 'E',
          'Ì' => 'I',
          'Í' => 'I',
          'Î' => 'I',
          'Ï' => 'I',
          'Ñ' => 'N',
          'Ò' => 'O',
          'Ó' => 'O',
          'Ô' => 'O',
          'Õ' => 'O',
          'Ö' => 'O',
          'Ø' => 'O',
          'Ù' => 'U',
          'Ú' => 'U',
          'Û' => 'U',
          'Ü' => 'U',
          'Ý' => 'Y',
          'Þ' => 'B',
          'ß' => 'Ss',
          'à' => 'a',
          'á' => 'a',
          'â' => 'a',
          'ã' => 'a',
          'ä' => 'a',
          'å' => 'a',
          'æ' => 'a',
          'ç' => 'c',
          'è' => 'e',
          'é' => 'e',
          'ê' => 'e',
          'ë' => 'e',
          'ì' => 'i',
          'í' => 'i',
          'î' => 'i',
          'ï' => 'i',
          'ð' => 'o',
          'ñ' => 'n',
          'ò' => 'o',
          'ó' => 'o',
          'ô' => 'o',
          'õ' => 'o',
          'ö' => 'o',
          'ø' => 'o',
          'ù' => 'u',
          'ú' => 'u',
          'û' => 'u',
          'ý' => 'y',
          'þ' => 'b',
          'ÿ' => 'y',
        ];

        return strtolower(strtr($textIndex, $unwanted_array));
    }
}
