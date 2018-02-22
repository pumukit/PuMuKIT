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
    public static $supportedLanguage = array(
        'da', 'nl', 'en', 'fi', 'fr', 'de', 'hu', 'it',
        'nb', 'pt', 'ro', 'ru', 'es', 'sv', 'tr', 'ara',
        'prs', 'pes', 'urd', 'zhs', 'zht',
    );

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
            return 'es';
        }

        return 'none';
    }
}
