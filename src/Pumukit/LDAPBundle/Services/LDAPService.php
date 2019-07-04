<?php

namespace Pumukit\LDAPBundle\Services;

use Psr\Log\LoggerInterface;

class LDAPService
{
    private $server;
    private $bindRdn;
    private $bindPassword;
    private $baseDn;
    private $logger;

    public function __construct($server, $bindRdn, $bindPassword, $baseDn, LoggerInterface $logger)
    {
        $this->server = $server;
        $this->bindRdn = $bindRdn;
        $this->bindPassword = $bindPassword;
        $this->baseDn = $baseDn;
        $this->logger = $logger;
    }

    /**
     * Is Configured.
     *
     * Checks if all the parameters are defined
     */
    public function isConfigured()
    {
        return ($this->server) ? true : false;
    }

    /**
     * Check connection.
     *
     * @return bool true if connects, false otherwise
     */
    public function checkConnection()
    {
        if ($this->isConfigured()) {
            try {
                $linkIdentifier = ldap_connect($this->server);
                ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
                if ($linkIdentifier) {
                    $result = ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
                    ldap_close($linkIdentifier);

                    return $result;
                }
            } catch (\Exception $e) {
                $this->logger->debug(__CLASS__.' ['.__FUNCTION__.'] '.$e->getMessage());

                return false;
            }
        }

        return false;
    }

    /**
     * Is user
     * Checks if user with given password
     * exists in LDAP Server.
     *
     * @param string $user User name
     * @param string $pass Password to verify
     *
     * @return bool true if user exists, false otherwise
     */
    public function isUser($user = false, $pass = '')
    {
        if ('' === $pass) {
            return false;
        }
        $ret = false;
        try {
            $linkIdentifier = ldap_connect($this->server);
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
                $searchResult = ldap_search($linkIdentifier, $this->baseDn, 'uid='.$user, [], 0, 1);
                if ($searchResult) {
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info) && (0 != $info['count'])) {
                        $dn = $info[0]['dn'];
                        $ret = @ldap_bind($linkIdentifier, $dn, $pass);
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.$e->getMessage());
            throw $e;
        }

        return $ret;
    }

    /**
     * Obtiene el nombre completo de usuario del
     * servidor ldap.
     *
     * @return string nombre completo del usuario
     *
     * @param string $user nombre del usuario
     */
    public function getName($user)
    {
        $name = false;
        try {
            $linkIdentifier = ldap_connect($this->server);
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
                $searchResult = ldap_search($linkIdentifier, $this->baseDn, 'uid='.$user, [], 0, 1);
                if ($searchResult) {
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info) && (0 != count($info))) {
                        $name = $info[0]['cn'][0];
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.$e->getMessage());
            throw $e;
        }

        return $name;
    }

    /**
     * Obtiene el correo electronico de usuario del
     * servidor ldap.
     *
     * @public
     *
     * @return string correo del usuario
     *
     * @param string $user nombre del usuario
     */
    public function getMail($user)
    {
        $name = false;
        try {
            $linkIdentifier = ldap_connect($this->server);
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
                $searchResult = ldap_search($linkIdentifier, $this->baseDn, 'uid='.$user, [], 0, 1);
                if ($searchResult) {
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info) && (0 != count($info))) {
                        $name = $info[0]['mail'][0];
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.$e->getMessage());
            throw $e;
        }

        return $name;
    }

    /**
     * Get all the LDAP info from the user email.
     *
     * @public
     * @pararm string $email
     *
     * @return array|false
     */
    public function getInfoFromEmail($email)
    {
        return $this->getInfoFrom('mail', $email);
    }

    /**
     * Get all the LDAP info from the user email.
     *
     * @public
     * @pararm string $key
     * @pararm string $value
     *
     * @return array|false
     */
    public function getInfoFrom($key, $value)
    {
        $return = false;

        $linkIdentifier = ldap_connect($this->server);
        ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
        if ($linkIdentifier) {
            ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
            $searchResult = ldap_search($linkIdentifier, $this->baseDn, $key.'='.$value, [], 0, 1);
            if ($searchResult) {
                $info = ldap_get_entries($linkIdentifier, $searchResult);
                if (($info) && (0 != count($info)) && isset($info[0])) {
                    $return = $info[0];
                }
            }
            ldap_close($linkIdentifier);
        }

        return $return;
    }

    /**
     * Get list of users.
     *
     * Searches LDAP users by CN or MAIL
     * If CN is an empty string or null and MAIL a given string:
     * - Returns LDAP users with given MAIL
     * If MAIL is an empty string or null and CN a given string:
     * - Returns LDAP users with given CN
     * If CN and MAIL are strings (equal or different):
     * - Returns LDAP users with given CN and LDAP users with given MAIL
     *
     * @param string $cn
     * @param string $mail
     *
     * @return array
     */
    public function getListUsers($cn = '', $mail = '')
    {
        $limit = 40;
        $out = [];
        try {
            $linkIdentifier = ldap_connect($this->server);
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                ldap_bind($linkIdentifier, $this->bindRdn, $this->bindPassword);
                $filter = $this->getFilter($cn, $mail);
                $searchResult = ldap_search($linkIdentifier, $this->baseDn, $filter, [], 0, $limit);
                if ($searchResult) {
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info) && (0 != count($info))) {
                        foreach ($info as $k => $i) {
                            if ('count' === $k) {
                                continue;
                            }
                            $out[] = [
                                           'mail' => $i['mail'][0],
                                           'cn' => $i['cn'][0],
                                           ];
                        }
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            $this->logger->error(__CLASS__.' ['.__FUNCTION__.'] '.$e->getMessage());
            throw $e;
        }

        return $out;
    }

    /**
     * Get filter.
     *
     * Builds LDAP filter by CN or MAIL
     * If CN is an empty string or null and MAIL a given string:
     * - Returns LDAP query with given MAIL
     * If MAIL is an empty string or null and CN a given string:
     * - Returns LDAP query with given CN
     * If CN and MAIL are strings (equal or different):
     * - Returns LDAP query with given CN and LDAP users with given MAIL
     *
     * @param string $cn
     * @param string $mail
     *
     * @return string
     */
    private function getFilter($cn = '', $mail = '')
    {
        $filter = ($cn ? 'cn='.$cn : '');
        if ($mail) {
            if ($filter) {
                $filter = '(|('.$filter.')(mail='.$mail.'))';
            } else {
                $filter = 'mail='.$mail;
            }
        }

        return $filter;
    }
}
