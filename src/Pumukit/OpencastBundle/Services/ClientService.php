<?php

namespace Pumukit\OpencastBundle\Services;

class ClientService
{
    private $url;
    private $user;
    private $passwd;
    private $player;
    private $adminUrl;
    private $deleteArchiveMediaPackage;
    private $deletionWorkflowName;

    /**
     * Constructor
     *
     * @param string  $url
     * @param string  $user
     * @param string  $passwd
     * @param string  $player
     * @param boolean $deleteArchiveMediaPackage
     * @param boolean $deletionWorkflowName
     */
    public function __construct($url, $user="", $passwd="", $player="/engage/ui/watch.html", $deleteArchiveMediaPackage = false, $deletionWorkflowName = 'delete-archive')
    {
        $this->url  = $url;
        $this->user  = $user;
        $this->passwd  = $passwd;
        $this->player  = $player;
        $this->deleteArchiveMediaPackage = $deleteArchiveMediaPackage;
        $this->deletionWorkflowName = $deletionWorkflowName;
    }

    /**
     * Get Url
     * from Opencast server
     * (Engage node in cluster)
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get player url
     * from Opencast
     *
     * @return string
     */
    public function getPlayerUrl()
    {
        return ('/' === $this->player[0]) ? $this->url . $this->player : $this->player;
    }

    /**
     * Get media packages
     *
     * Gets all the media packages from the Opencast server
     * accordin to input parameters
     *
     * @param  string $query
     * @param  string $limit
     * @param  string $offset
     * @return array
     */
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

    /**
     * Get media package
     * from given id
     *
     * @param  string $id
     * @return array
     */
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

    /**
     * Get media package from archive
     * with given id
     *
     * @param  string $id
     * @return array
     */
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

    /**
     * Apply workflow to media packages
     *
     * @param  array  $mediaPackagesIds
     * @param  string $workflowName
     * @return string $status
     */
    public function applyWorkflowToMediaPackages(array $mediaPackagesIds = array(), $workflowName = '')
    {
        if (!$workflowName) {
            $workflowName = $this->deletionWorkflowName;
            if (!$this->deleteArchiveMediaPackage) {
                throw new \Exception('Not allowed to delete media packages form archive');
            }
        }

        if (!$mediaPackagesIds) {
            throw new \Exception('No media packages given.');
        }

        $request = '/episode/apply/'.$workflowName.'?mediaPackageIds=';
        foreach ($mediaPackagesIds as $index => $id) {
            $request = $request . $id;
            if ($index < (count($mediaPackagesIds) - 1)) {
                $request = $request . ',+';
            }
        }

        if (!$this->adminUrl) {
            $this->adminUrl = $this->getAdminUrl();
        }

        $output = $this->request($request, true);

        if ($output["status"] !== 204) return false;

        return true;
    }

    /**
     * Check workflow ended
     *
     * @param string $id
     * @return boolean
     */
    public function checkWorkflowEnded($id='')
    {
        $workflows = $this->getWorkflowInstances($id);
        $deletionWorkflow = $this->getWorkflowWithTemplate($workflows, $this->deletionWorkflowName);
        $isFinished = $this->isWorkflowFinished($deletionWorkflow);

        if ($isFinished) {
            // TODO: Create function
            //$output = $this->deleteWorkflows($id);
            return true;
        }

        return false;
    }

    /**
     * Request
     *
     * Makes a given request (path)
     * to the Opencast server
     * using or not the admin url
     *
     * @param  string  $path
     * @param  boolean $useAdminUrl
     * @return array
     */
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

    /**
     * Get admin url
     *
     * Gets the admin url for Opencast
     */
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

    /**
     * Get workflow instances
     * with given mediapackage id
     *
     * @param string $id
     * @return array
     */
    private function getWorkflowInstances($id = '')
    {
        $request = '/workflow/instances.json?mp='.$id;

        $output = $this->request($request);

        if ($output["status"] !== 200) return false;

        $decode = json_decode($output["var"], true);

        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }

        if ($decode["workflows"]["totalCounts"] == 0)
            return null;
        else
            return $decode["workflows"]["workflow"];
    }

    /**
     * Get workflow template
     *
     * @param array $workflows
     * @param string $template
     * @return array
     */
    private function getWorkflowWithTemplate(array $workflows = array(), $template='')
    {
        foreach ($workflows as $workflow) {
            if (isset($workflow['template'])) {
                if ($template == $workflow['template']) {
                    return $workflow;
                }
            }
        }
  
        return null;
    }

    /**
     * Is workflow finished
     *
     * @param array $workflow
     * @return boolean
     */
    private function isWorkflowFinished(array $workflow = array())
    {
        // TODO: review
        if ($workflow && isset($workflow['status'])) {
            if ('SUCCEED' === $workflow['status']) {
                return true;
            }
        }

        return false;
    }
}