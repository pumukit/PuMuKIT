<?php

namespace Pumukit\OpencastBundle\Services;

class ClientService
{
    const RETRACT_WORKFLOW = 'retract';

    private $url;
    private $user;
    private $passwd;
    private $player;
    private $adminUrl = null;
    private $retractMediaPackage;
    private $deletionWorkflowName;

    /**
     * Constructor
     *
     * @param string  $url
     * @param string  $user
     * @param string  $passwd
     * @param string  $player
     * @param boolean $retractMediaPackage
     * @param boolean $deletionWorkflowName
     */
    public function __construct($url="", $user="", $passwd="", $player="/engage/ui/watch.html", $retractMediaPackage = false, $deletionWorkflowName = 'retract')
    {
        $this->url  = $url;
        $this->user  = $user;
        $this->passwd  = $passwd;
        $this->player  = $player;
        $this->retractMediaPackage = $retractMediaPackage;
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
        $output = $this->request("/search/episode.json?". ($query?"q=" . $query ."&":""). "limit=" . $limit . "&offset=" . $offset);

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
        $output = $this->request("/episode/episode.json?id=" . $id, array(), true, true);

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
        if (!$workflowName || ($workflowName == $this->deletionWorkflowName)) {
            $workflowName = $this->deletionWorkflowName;
            if (!$this->retractMediaPackage) {
                throw new \Exception('Not allowed to delete media packages from archive');
            }
        }

        if (!$mediaPackagesIds) {
            throw new \Exception('No media packages given.');
        }

        $request = '/episode/apply/'.$workflowName;

        $mediaPackageIdsParameter = '';
        foreach ($mediaPackagesIds as $index => $id) {
            $mediaPackageIdsParameter = $mediaPackageIdsParameter . $id;
            if ($index < (count($mediaPackagesIds) - 1)) {
                $mediaPackageIdsParameter = $mediaPackageIdsParameter . ',+';
            }
        }
        $parameters = array('mediaPackageIds' => $mediaPackageIdsParameter);
        if ($this->deletionWorkflowName === self::RETRACT_WORKFLOW) {
            $parameters['engage'] = 'Matterhorn+Engage+Player';
            $parameters['youtube'] = 'Youtube';
        }

        $output = $this->request($request, $parameters, false);

        if ($output["status"] !== 204) return false;

        return true;
    }

    /**
     * Check workflow ended
     *
     * @param string $id
     * @return boolean
     */
    public function deleteWorkflowsIfEnded($id='')
    {
        $errors = 0;
        $workflows = $this->getWorkflowInstances($id);
        $deletionWorkflows = $this->getWorkflowsWithTemplate($workflows, $this->deletionWorkflowName);
        foreach ($deletionWorkflows as $deletionWorkflow) {
            $isFinished = $this->isWorkflowFinished($deletionWorkflow);
            if ($isFinished) {
                $output = $this->deleteWorkflow($deletionWorkflow);
                if (!$output) {
                    ++$errors;
                }
            }
        }
        if ($errors > 0) return false;
        
        return true;
    }

    /**
     * Delete workflows
     * with given media package id
     *
     * @param array $workflows
     * @return boolean
     */
    public function deleteWorkflow($workflow = array())
    {
        $output = $this->stopWorkflow($workflow);
        if (!$output) {
            return false;
        }

        return true;
    }

    /**
     * Stop workflow
     *
     * @param array $workflow
     * @return boolean
     */
    public function stopWorkflow(array $workflow = array())
    {
        if (isset($workflow['id'])) {

            $request = '/workflow/stop';
            $params = array('id' => $workflow['id']);

            $output = $this->request($request, $params, false);
            if ($output["status"] !== 200)
                return false;

            return true;
        }

        return false;
    }

    /**
     * Request
     *
     * Makes a given request (path)
     * GET or POST
     * to the Opencast server
     * using or not the admin url
     *
     * @param  string  $path
     * @param  array   $query
     * @param  boolean $get
     * @param  boolean $useAdminUrl
     * @return array
     */
    private function request($path, $query = array(), $get = true, $useAdminUrl = false)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Curl is required to execute remote commands.');
        }

        if ($get) {
            if ($useAdminUrl && $this->adminUrl) {
                $request = curl_init($this->adminUrl . $path);
            } else {
                $request = curl_init($this->url . $path);
            }
            if (false === $request) {
                throw new \RuntimeException('Unable to create a new curl handle.');
            }
        } else {
            if (false === $request = curl_init()) {
                throw new \RuntimeException('Unable to create a new curl handle.');
            }
            if ($useAdminUrl && $this->adminUrl) {
                $requestUrl = $this->adminUrl . $path;
            } else {
                $requestUrl = $this->url . $path;
            }
            curl_setopt($request, CURLOPT_URL, $requestUrl);
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($query));
        }

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, false);

        if ($this->user != "") {
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($request, CURLOPT_USERPWD, $this->user . ':' . $this->passwd);
            curl_setopt($request, CURLOPT_HTTPHEADER, array("X-Requested-Auth: Digest",
                                                            "X-Opencast-Matterhorn-Authorization: true"));
        }

        $output = array();
        $output["var"] = curl_exec($request);
        $output["error"] = curl_error($request);
        $output["status"] = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        if ($get) {
            if (200 != $output["status"]) {
                throw new \Exception("Error Processing Request", 1);
            }
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
        $request = '/workflow/instances.json'. ($id ? '?mp='.$id : '');

        $output = $this->request($request);

        if ($output["status"] !== 200) return false;

        $decode = json_decode($output["var"], true);

        if (!($decode)) {
            throw new \Exception("Opencast Matterhorn communication error");
        }

        if ($decode["workflows"]["totalCount"] == 0)
            return null;
        else
            return $decode["workflows"]["workflow"];
    }

    /**
     * Get workflows template
     *
     * @param array $workflows
     * @param string $template
     * @return array
     */
    private function getWorkflowsWithTemplate(array $workflows = array(), $template='')
    {
        $templateWorkflows = array();
        foreach ($workflows as $workflow) {
            if (isset($workflow['template'])) {
                if ($template == $workflow['template']) {
                    $templateWorkflows[] = $workflow;
                }
            }
        }
  
        return $templateWorkflows;
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
        if ($workflow && isset($workflow['state'])) {
            if ('SUCCEEDED' === $workflow['state']) {
                return true;
            }
        }

        return false;
    }
}