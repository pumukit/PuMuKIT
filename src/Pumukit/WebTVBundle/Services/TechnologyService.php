<?php

namespace Pumukit\WebTVBundle\Services;

class TechnologyService
{
    public function isMobileDevice($userAgent=null)
    {
        if (null != $userAgent) {
            if (preg_match('/android|avantgo|bada|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|iris|ipad|iphone|kindle|lge|maemo|midp|mmp|mobile|netfront|opera mini|opera mobi|palm os|phone|plucker|pocket|psp|symbian|treo|vodafone|wap|windows ce|xda|xiino|zte/i',$userAgent)) {
                return true;
            }
        }

        return false;
    }
}