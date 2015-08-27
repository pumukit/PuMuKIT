<?php

namespace Pumukit\LDAPBundle\Services;

class LDAPService
{
    private $server;
    private $dnSearchEngine;
    private $passSearchEngine;
    private $dnUser;

    public function __construct($server, $dnSearchEngine, $passSearchEngine, $dnUser)
    {
        $this->server = $server;
        $this->dnSearchEngine = $dnSearchEngine;
        $this->passSearchEngine = $passSearchEngine;
        $this->dnUser = $dnUser;
    }

    /**
     * Is Configured
     *
     * Checks if all the parameters are defined
     */
    public function isConfigured()
    {
        if ($this->server &&
            $this->dnSearchEngine &&
            $this->passSearchEngine &&
            $this->dnUser) return true;
        return false;
    }

    /**
     * Check connection
     *
     * @return boolean true if connects, false otherwise
     */
    public function checkConnection()
    {
        if ($this->isConfigured()) {
            try {
                $linkIdentifier = ldap_connect($this->server);
                ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
                if ($linkIdentifier) {
                    $result = ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                    ldap_close($linkIdentifier);
                    return $result;
                }
            } catch (\Exception $e) {
                // TODO log exception
                return false;
            }
        }
        return false;
    }

    /**
     * Is user
     * Checks if user with given password
     * exists in LDAP Server
     *
     * @param string $user User name
     * @param string $pass Password to verify
     * @return boolean true if user exists, false otherwise
     */
    public function isUser($user=false, $pass='')
    {
        if ($pass === '') return false;
        $ret = false;
        try {
            $linkIdentifier = ldap_connect($this->server);
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                $result = ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                $searchResult = ldap_search($linkIdentifier, $this->dnUser, "uid=" . $user);
                if ($searchResult){
                    $info = ldap_get_entries($linkIdentifier, $searchResult);          
                    if (($info)&&($info["count"] != 0)){
                        $dn = $info[0]["dn"];
                        $ret = @ldap_bind($linkIdentifier, $dn, $pass);
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $ret;
    }

    /**
     * Obtiene el nombre completo de usuario del 
     * servidor ldap.
     *
     * @access public
     * @return string nombre completo del usuario
     * @param string $user nombre del usuario
     */
    public function getName($user)
    {   
        $name = false;
        try {
            $linkIdentifier = ldap_connect( $this->server ); 
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                $result = ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                $searchResult = ldap_search($linkIdentifier, $this->dnUser, "uid=" . $user);
                if ($searchResult){
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info)&&(count($info) != 0)){
                        $name = $info[0]["cn"][0];          
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $name;
    }

    /**
     * Obtiene el correo electronico de usuario del 
     * servidor ldap.
     *
     * @public
     * @access public
     * @return string correo del usuario
     * @param string $user nombre del usuario
     */
    public function getMail($user)
    {   
        $name = false;
        try {
            $linkIdentifier = ldap_connect( $this->server ); 
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                $result = ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                $searchResult = ldap_search($linkIdentifier, $this->dnUser, "uid=" . $user);
                if ($searchResult){
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info)&&(count($info) != 0)){
                      $name = $info[0]["mail"][0];          
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $name;
    }

    /**
     * Get list of users
     *
     * @param string $cn
     * @return array
     */
    public function getListUsers($cn='')
    {
        $out = array();
        try {
            $linkIdentifier = ldap_connect( $this->server ); 
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                $result=ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                $searchResult = ldap_search($linkIdentifier, $this->dnUser, "mail=" . $cn);
                if ($searchResult){
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info)&&(count($info) != 0)){
                        foreach($info as $k=>$i) {
                            if($k === "count") continue;
                            $out[] = array(
                                           'mail' => $i["mail"][0],
                                           'cn' => $i["cn"][0]
                                           );
                        }
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $out;
    }

    /**
     * Get list users by mail
     */
    public function getListUsersByMail($mail='')
    {
        $out = array();
        try {
            $linkIdentifier = ldap_connect( $this->server ); 
            ldap_set_option($linkIdentifier, LDAP_OPT_PROTOCOL_VERSION, 3);
            if ($linkIdentifier) {
                $result = ldap_bind($linkIdentifier, $this->dnSearchEngine, $this->passSearchEngine);
                $searchResult = ldap_search($linkIdentifier, $this->dnUser, "mail=" . $mail);
                if ($searchResult){
                    $info = ldap_get_entries($linkIdentifier, $searchResult);
                    if (($info)&&(count($info) != 0)){
                        foreach($info as $k=>$i) {
                            if($k === "count") continue;
                            $out[] = array(
                                           'mail' => $i["mail"][0],
                                           'cn' => $i["cn"][0]
                                           );
                        }
                    }
                }
                ldap_close($linkIdentifier);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $out;
    }
}