<?php

namespace Icinga\Module\Oidc;

use Icinga\Application\Config;
use Icinga\Application\Icinga;

class CookieHelper
{
    /**
     * Set a module cookie using configurable security flags.
     *
     * Supported configuration (module "oidc"):
     * [cookies]
     * secure = "auto" | "1" | "0"   (default: "auto")
     * httponly = "1" | "0"          (default: "0")
     * samesite = "" | "Lax" | "Strict" | "None" (default: "")
     */
    public static function set($name, $value, $expires, $path)
    {
        $cfg = Config::module('oidc');

        $secureCfg = (string) $cfg->get('cookies', 'secure', 'auto');
        $httpOnlyCfg = (string) $cfg->get('cookies', 'httponly', '0');
        $sameSiteCfg = (string) $cfg->get('cookies', 'samesite', '');

        $secure = false;
        if ($secureCfg === '1' || strtolower($secureCfg) === 'true' || strtolower($secureCfg) === 'yes') {
            $secure = true;
        } elseif ($secureCfg === '0' || strtolower($secureCfg) === 'false' || strtolower($secureCfg) === 'no') {
            $secure = false;
        } else {
            // auto: enable Secure flag only when the current request is HTTPS
            $scheme = Icinga::app()->getRequest()->getScheme();
            $secure = (strtolower((string) $scheme) === 'https');
        }

        $httpOnly = ($httpOnlyCfg === '1' || strtolower($httpOnlyCfg) === 'true' || strtolower($httpOnlyCfg) === 'yes');

        $sameSite = trim($sameSiteCfg);
        if ($sameSite !== '' && ! in_array($sameSite, ['Lax', 'Strict', 'None'], true)) {
            // Invalid values are ignored to avoid breaking behavior
            $sameSite = '';
        }

        $options = [
            'expires'  => (int) $expires,
            'path'     => $path,
            'secure'   => $secure,
            'httponly' => $httpOnly,
        ];

        // Only attach SameSite when explicitly configured
        if ($sameSite !== '') {
            $options['samesite'] = $sameSite;
        }

        return setcookie($name, $value, $options);
    }

    public static function delete($name, $path)
    {
        // Deleting a cookie should mirror the same options (path + flags) to ensure overwrite
        return self::set($name, '', time() - 3600, $path);
    }
}