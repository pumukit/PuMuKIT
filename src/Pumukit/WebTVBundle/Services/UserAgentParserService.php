<?php

namespace Pumukit\WebTVBundle\Services;

/**
 * Parses the user agent and retrieves readable information.
 * DISCLAIMER: DO NOT USE THE USER AGENT STRING. (Use javascript and feature detection, please)
 */
class UserAgentParserService
{
    /**
     * Returns true if the userAgent belongs to an 'old' browser.
     * This function is used in PuMuKIT ONLY for the player_matterhorn template (to show a warning if it's 'old'. This can be better solved using a script to check for feature support)
     * @param String
     * @return boolean
     */
    public function isOldBrowser($userAgent)
    {
        $isOldBrowser = false;
        $webExplorer = $this->getWebExplorer($userAgent);
        $version = $this->getVersion($userAgent, $webExplorer);
        if (($webExplorer == 'IE') || ($webExplorer == 'MSIE') || $webExplorer == 'Firefox' || $webExplorer == 'Opera' || ($webExplorer == 'Safari' && $version < 4)) {
            $isOldBrowser = true;
        }

        return $isOldBrowser;
    }

    /**
     * Returns a string with the browser name.
     *
     * Only works for MSIE, Opera, Firefox, Safari and Chrome. Add more strings if needed.
     *
     * @param String
     * @return String
     */
    public function getWebExplorer($userAgent)
    {
        $webExplorer = 'unknown';
        if (preg_match('/MSIE/i', $userAgent)) {
            $webExplorer = 'MSIE';
        }
        if (preg_match('/Opera/i', $userAgent)) {
            $webExplorer = 'Opera';
        }
        if (preg_match('/Firefox/i', $userAgent)) {
            $webExplorer = 'Firefox';
        }
        if (preg_match('/Safari/i', $userAgent)) {
            $webExplorer = 'Safari';
        }
        if (preg_match('/Chrome/i', $userAgent)) {
            $webExplorer = 'Chrome';
        }

        return $webExplorer;
    }

    /**
     * Returns a string with the browser's version.
     * It needs a string from the 'getWebExplorer' function to work propertly.
     * @param String
     * @param String
     * @return String
     */
    public function getVersion($userAgent, $webExplorer)
    {
        $version = null;

        if ($webExplorer !== 'Opera' && preg_match('#('.$webExplorer.')[/ ]?([0-9.]*)#', $userAgent, $match)) {
            $version = floor($match[2]);
        }
        if (($webExplorer == 'Opera' || $webExplorer == 'Safari') && preg_match('#(Version)[/ ]?([0-9.]*)#', $userAgent, $match)) {
            $version = floor($match[2]);
        }

        return $version;
    }
}
