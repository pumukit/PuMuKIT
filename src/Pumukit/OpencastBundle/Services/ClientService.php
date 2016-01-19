<?php

namespace Pumukit\OpencastBundle\Services;

class ClientService
{
  private $url;
  private $user;
  private $passwd;
  private $player;
  private $scheduler;
  private $dashboard;
  private $adminUrl;

  public function __construct($url, $user='', $passwd='', $player='/engage/ui/watch.html', $scheduler = '/admin/index.html#/recordings', $dashboard = '/dashboard/index.html')
  {
      $this->url  = ('/' == substr($url, -1)) ? substr($url, 0, -1) : $url;
      $this->user  = $user;
      $this->passwd  = $passwd;
      $this->player  = $player;
      $this->scheduler  = $scheduler;
      $this->dashboard  = $dashboard;
  }

  public function getUrl()
  {
      return $this->url;
  }

  public function getPlayerUrl()
  {
      return ('/' === $this->player[0]) ? $this->url . $this->player : $this->player;
  }

  public function getSchedulerUrl()
  {
      if (!$this->adminUrl) {
          $this->adminUrl = $this->getAdminUrl();
      }

      return ('/' === $this->scheduler[0]) ? $this->adminUrl . $this->scheduler : $this->scheduler;
  }

  public function getDashboardUrl()
  {
      if (!$this->adminUrl) {
          $this->adminUrl = $this->getAdminUrl();
      }

      return ('/' === $this->dashboard[0]) ? $this->adminUrl . $this->dashboard : $this->dashboard;
  }

  private function request($path, $useAdminUrl=false)
  {
    $sal = array();

    if ($useAdminUrl && $this->adminUrl) {
        $ch = curl_init($this->adminUrl . $path);
    } else {
        $ch = curl_init($this->url . $path);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if ($this->user != "") {
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
      curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->passwd);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest",
       "X-Opencast-Matterhorn-Authorization: true"));
    }

    $sal["var"] = curl_exec($ch);
    $sal["error"] = curl_error($ch);
    $sal["status"] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if(200 != $sal["status"]) {
      throw new \Exception(sprintf("Error Processing Request '%s'", $this->url . $path), 1);
    }
   
    return $sal;
  }


  public function getMediaPackages($q, $limit, $offset)
  {
    $sal = $this->request("/search/episode.json?q=" . $q . "&limit=" . $limit . "&offset=" . $offset);


    if ($sal["status"] !== 200) return false;
    $decode = json_decode($sal["var"], true);

    if (!($decode)) {
      throw new \Exception("Matterhorn communication error");
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
    $sal = $this->request("/search/episode.json?id=" . $id);


    if ($sal["status"] !== 200) return false;
    $decode = json_decode($sal["var"], true);

    if (!($decode)) {
      throw new \Exception("Matterhorn communication error");
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
      $sal = $this->request("/episode/episode.json?id=" . $id, true);

      if ($sal["status"] !== 200) return false;
      $decode = json_decode($sal["var"], true);

      if (!($decode)) {
          throw new \Exception("Matterhorn communication error");
      }

      if ($decode["search-results"]["total"] == 0)
          return null;
      if ($decode["search-results"]["limit"] > 1)
          return $decode["search-results"]["result"][0]["mediapackage"];
      else
          return $decode["search-results"]["result"]["mediapackage"];
  }

  public function getAdminUrl()
  {
      $output = $this->request('/services/available.json?serviceType=org.opencastproject.episode');
      if ($output["status"] !== 200) return false;
      $decode = json_decode($output["var"], true);
      if (!($decode)) {
          throw new \Exception("Matterhorn communication error");
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