<?php

namespace Pumukit\OpencastBundle\Services;

class ClientService
{
  private $url;
  private $user;
  private $passwd;


  public function __construct($url, $user, $passwd)
  {
    $this->url  = $url;
    $this->user  = $user;
    $this->passwd  = $passwd;
  }


  private function request($path){
    $sal = array();

    //var_dump($url); exit;
    $ch = curl_init($this->url . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    dump($this->url . $path);
    //curl_setopt($ch, CURLOPT_HEADER, true);

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

    if(200 != $sal["status"]) {
      throw new \Exception("Error Processing Request", 1);
      
    }

    curl_close($ch);
    return $sal;

  }



  public function getMediaPackages($q, $limit, $offset)
  {
    dump($q);
    dump($limit);
    dump($offset);

    if($limit == 0){
      $sal = $this->request("/search/episode.json?q=" . $q);
    }

    if($limit > 1){
      $sal = $this->request("/search/episode.json?q=" . $q . "&limit=" . $limit);
    }

    //dump($sal);

    if ($sal["status"] !== 200) return false;
    $decode = json_decode($sal["var"], true);

    dump($decode);

    if (!($decode)) {
      throw new sfException("Matterhorn communication error");
    }

    $return = array();

    if ($decode["search-results"]["total"] == 0)
      return $return;
    
    if ($decode["search-results"]["limit"] > 1)
      foreach($decode["search-results"]["result"] as $media)
        $return[] = $media["mediapackage"];
    else
      $return[] = $decode["search-results"]["result"]["mediapackage"];

    return $return;
  }


}