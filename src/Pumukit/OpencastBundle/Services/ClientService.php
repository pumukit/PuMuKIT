<?php

namespace Pumukit\OpencastBundle\Services;

class ClientService
{
    private $url;
    private $user;
    private $passwd;
    private $player;
    private $adminUrl;

    public function __construct($url, $user="", $passwd="", $player="/engage/ui/watch.html")
    {
        $this->url  = $url;
        $this->user  = $user;
        $this->passwd  = $passwd;
        $this->player  = $player;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getPlayerUrl()
    {
        return ('/' === $this->player[0]) ? $this->url . $this->player : $this->player;
    }

    public function getMediaPackages($query, $limit, $offset)
    {
        $output = $this->request("/search/episode.json?q=" . $query . "&limit=" . $limit . "&offset=" . $offset);


        if ($output["status"] !== 200) return false;
        $decode = json_decode($output["var"], true);

        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }

        $return = array(0, array());

        if ($decode["search-results"]["total"] == 0)
            return $return;
    
        $return[0] = $decode["search-results"]["total"];
        if ($decode["search-results"]["limit"] > 1)
            foreach($decode["search-results"]["result"] as $media)
                $return[1][] = $media["mediapackage"];
        else
            $return[1][] = $decode["search-results"]["result"]["mediapackage"];

        return $return;
    }

    public function getMediapackage($id)
    {
        $output = $this->request("/search/episode.json?id=" . $id);

        if ($output["status"] !== 200) return false;
        $decode = json_decode($output["var"], true);

        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }

        if ($decode["search-results"]["total"] == 0)
            return null;
        if ($decode["search-results"]["limit"] > 1)
            return $decode["search-results"]["result"][0]["mediapackage"];
        else
            return $decode["search-results"]["result"]["mediapackage"];   
    }

    public function getMediapackageFromArchive($id)
    {
        $this->adminUrl = $this->getAdminUrl();
        $output = $this->request("/episode/episode.json?id=" . $id, true);

        if ($output["status"] !== 200) return false;
        $decode = json_decode($output["var"], true);

        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }

        if ($decode["search-results"]["total"] == 0)
            return null;
        if ($decode["search-results"]["limit"] > 1)
            return $decode["search-results"]["result"][0]["mediapackage"];
        else
            return $decode["search-results"]["result"]["mediapackage"];
    }

    private function request ($path, $useAdminUrl=false)
    {
        $output = array();

        if ($useAdminUrl && $this->adminUrl) {
            $request = curl_init($this->adminUrl . $path);
        } else {
            $request = curl_init($this->url . $path);
        }
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, false);

        if ($this->user != "") {
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($request, CURLOPT_USERPWD, $this->user . ':' . $this->passwd);
            curl_setopt($request, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest",
                                                       "X-Opencast-Matterhorn-Authorization: true"));
        }

        $output["var"] = curl_exec($request);
        $output["error"] = curl_error($request);
        $output["status"] = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        if (200 != $output["status"]) {
            throw new \Exception("Error Processing Request", 1);
        }

        return $output;
    }

    private function getAdminUrl()
    {
        $output = $this->request('/services/available.json?serviceType=org.opencastproject.episode');
        if ($output["status"] !== 200) return false;
        $decode = json_decode($output["var"], true);
        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }
        if (isset($decode['services'])) {
            if (isset($decode['services']['service'])) {
                if (isset($decode['services']['service']['host'])) {
                    return $decode['services']['service']['host'];
                }
            }
        }

        return null;
    }
}