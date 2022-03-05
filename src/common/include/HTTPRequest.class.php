<?php
/**
 * Copyright (c) Enalean, 2012-Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class HTTPRequest extends Codendi_Request
{
    public const HEADER_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';
    public const HEADER_REMOTE_ADDR     = 'REMOTE_ADDR';

    /**
     * @var array
     */
    private $trusted_proxied = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct($_REQUEST);
    }


    /**
     * Get the value of $variable in $this->params (server side values).
     *
     * @param string $variable Name of the parameter to get.
     * @return mixed If the variable exist, the value is returned (string)
     * otherwise return false;
     */
    public function getFromServer($variable)
    {
        return $this->_get($variable, $_SERVER);
    }

    /**
     * Check if current request is send via 'post' method.
     *
     * This method is useful to test if the current request comes from a form.
     *
     * @return bool
     */
    public function isPost()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Hold an instance of the class
     * @var self|null
     */
    protected static $_instance;

    /**
     * The singleton method
     *
     * @return HTTPRequest
     */
    public static function instance()
    {
        if (! isset(self::$_instance)) {
            $c               = self::class;
            self::$_instance = new $c();
        }
        return self::$_instance;
    }

    public static function setInstance($instance)
    {
        self::$_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_instance = null;
    }

    /**
     * Validate file upload.
     *
     * @param  Valid_File Validator for files.
     * @return bool
     */
    public function validFile(&$validator)
    {
        if ($validator instanceof \Valid_File) {
            return $validator->validate($_FILES, $validator->getKey());
        } else {
            return false;
        }
    }

    /**
     * Get the value of $variable in $array. If magic_quotes are enabled, the
     * value is escaped.
     *
     * @access private
     * @param string $variable Name of the parameter to get.
     * @param array $array Name of the parameter to get.
     */
    public function _get($variable, $array)
    {
        if ($this->_exist($variable, $array)) {
            return $array[$variable];
        } else {
            return false;
        }
    }

    /**
     * What are the IP adresses trusted to be a proxy
     *
     * @param array $proxies
     */
    public function setTrustedProxies(array $proxies)
    {
        foreach ($proxies as $proxy) {
            $this->trusted_proxied[$proxy] = true;
        }
    }

    private function isFromTrustedProxy(): bool
    {
        if (isset($_SERVER[self::HEADER_REMOTE_ADDR])) {
            foreach ($this->trusted_proxied as $proxy => $nop) {
                if (self::checkIp4($_SERVER[self::HEADER_REMOTE_ADDR], $proxy)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @see Symfony\Component\HttpFoundation\IpUtils @ 3.2-dev (MIT license)
     *
     * @param string $request_ip IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
     */
    public static function checkIp4($request_ip, $ip)
    {
        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask === '0') {
                // Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
                return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }

            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($request_ip)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

    /**
     * @deprecated
     */
    public function getServerUrl(): string
    {
        return \Tuleap\ServerHostname::HTTPSUrl();
    }

    /**
     * Return request IP address
     *
     * When run behind a reverse proxy, REMOTE_ADDR will be the IP address of the
     * reverse proxy, use this method if you want to get the actual ip address
     * of the request without having to deal with reverse-proxy or not.
     *
     * @return String
     */
    public function getIPAddress()
    {
        if ($this->isFromTrustedProxy() && isset($_SERVER[self::HEADER_X_FORWARDED_FOR])) {
            return $_SERVER[self::HEADER_X_FORWARDED_FOR];
        } else {
            return $_SERVER[self::HEADER_REMOTE_ADDR];
        }
    }
}
