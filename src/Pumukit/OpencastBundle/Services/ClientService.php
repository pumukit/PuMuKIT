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
    private $adminUrl = null;
    private $deleteArchiveMediaPackage;
    private $deletionWorkflowName;

    /**
     * Constructor.
     *
     * @param string $url
     * @param string $user
     * @param string $passwd
     * @param string $player
     * @param bool   $deleteArchiveMediaPackage
     * @param string $deletionWorkflowName
     */
    public function __construct($url = '', $user = '', $passwd = '', $player = '/engage/ui/watch.html', $scheduler = '/admin/index.html#/recordings', $dashboard = '/dashboard/index.html',
                                $deleteArchiveMediaPackage = false, $deletionWorkflowName = 'delete-archive')
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('Curl is required to execute remote commands.');
        }

        $this->url = ('/' == substr($url, -1)) ? substr($url, 0, -1) : $url;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->player = $player;
        $this->scheduler = $scheduler;
        $this->dashboard = $dashboard;
        $this->deleteArchiveMediaPackage = $deleteArchiveMediaPackage;
        $this->deletionWorkflowName = $deletionWorkflowName;
    }

    /**
     * Get Url
     * from Opencast server
     * (Engage node in cluster).
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Get player url from Opencast.
     *
     * @return string
     */
    public function getPlayerUrl()
    {
        return ('/' === $this->player[0]) ? $this->url.$this->player : $this->player;
    }

    /**
     * Get admin url.
     *
     * Gets the admin url for Opencast
     */
    public function getAdminUrl()
    {
        if ($this->adminUrl) {
            return $this->adminUrl;
        }

        $output = $this->request('/services/available.json?serviceType=org.opencastproject.episode');
        $decode = json_decode($output['var'], true);
        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }
        if (isset($decode['services'])) {
            if (isset($decode['services']['service'])) {
                if (isset($decode['services']['service']['host'])) {
                    $this->adminUrl = $decode['services']['service']['host'];
                }
            }
        }

        return $this->adminUrl;
    }

    /**
     * Get scheduler url from Opencast.
     *
     * @return string
     */
    public function getSchedulerUrl()
    {
        return ('/' === $this->scheduler[0]) ? $this->getAdminUrl().$this->scheduler : $this->scheduler;
    }

    /**
     * Get player url from Opencast.
     *
     * @return string
     */
    public function getDashboardUrl()
    {
        return ('/' === $this->dashboard[0]) ? $this->getAdminUrl().$this->dashboard : $this->dashboard;
    }

    /**
     * Get media packages.
     *
     * Gets all the media packages from the Opencast server
     * accordin to input parameters
     *
     * @param string $query
     * @param string $limit
     * @param string $offset
     *
     * @return array
     */
    public function getMediaPackages($query, $limit, $offset)
    {
        $output = $this->request('/search/episode.json?'.($query ? 'q='.$query.'&' : '').'limit='.$limit.'&offset='.$offset);

        if ($output['status'] !== 200) {
            return false;
        }
        $decode = json_decode($output['var'], true);

        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        $return = array(0, array());

        if ($decode['search-results']['total'] == 0) {
            return $return;
        }

        $return[0] = $decode['search-results']['total'];
        if ($decode['search-results']['limit'] > 1) {
            foreach ($decode['search-results']['result'] as $media) {
                $return[1][] = $media['mediapackage'];
            }
        } else {
            $return[1][] = $decode['search-results']['result']['mediapackage'];
        }

        return $return;
    }

    /**
     * Get media package
     * from given id.
     *
     * @param string $id
     *
     * @return array
     */
    public function getMediapackage($id)
    {
        $output = $this->request('/search/episode.json?id='.$id);

        if ($output['status'] !== 200) {
            return false;
        }
        $decode = json_decode($output['var'], true);

        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        if ($decode['search-results']['total'] == 0) {
            return;
        }
        if ($decode['search-results']['limit'] > 1) {
            return $decode['search-results']['result'][0]['mediapackage'];
        } else {
            return $decode['search-results']['result']['mediapackage'];
        }
    }

    /**
     * Get media package from archive
     * with given id.
     *
     * @param string $id
     *
     * @return array
     */
    public function getMediapackageFromArchive($id)
    {
        $output = $this->request('/episode/episode.json?id='.$id, array(), 'GET', true);

        if ($output['status'] !== 200) {
            return false;
        }
        $decode = json_decode($output['var'], true);

        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        if ($decode['search-results']['total'] == 0) {
            return;
        }
        if ($decode['search-results']['limit'] > 1) {
            return $decode['search-results']['result'][0]['mediapackage'];
        } else {
            return $decode['search-results']['result']['mediapackage'];
        }
    }

    /**
     * Apply workflow to media packages.
     *
     * @param array  $mediaPackagesIds
     * @param string $workflowName
     *
     * @return string $status
     */
    public function applyWorkflowToMediaPackages(array $mediaPackagesIds = array(), $workflowName = '')
    {
        if (!$workflowName || ($workflowName == $this->deletionWorkflowName)) {
            $workflowName = $this->deletionWorkflowName;
            if (!$this->deleteArchiveMediaPackage) {
                throw new \Exception('Not allowed to delete media packages from archive');
            }
        }

        if (!$mediaPackagesIds) {
            throw new \Exception('No media packages given.');
        }

        $request = '/episode/apply/'.$workflowName;

        $mediaPackageIdsParameter = '';
        foreach ($mediaPackagesIds as $index => $id) {
            $mediaPackageIdsParameter = $mediaPackageIdsParameter.$id;
            if ($index < (count($mediaPackagesIds) - 1)) {
                $mediaPackageIdsParameter = $mediaPackageIdsParameter.',+';
            }
        }
        $parameters = array('mediaPackageIds' => $mediaPackageIdsParameter,
                            'engage' => 'Matterhorn+Engage+Player', );

        $output = $this->request($request, $parameters, 'POST', true);

        if ($output['status'] !== 204) {
            return false;
        }

        return true;
    }

    /**
     * Get workflow statistics.
     *
     * Used to get the total number of workflows
     */
    public function getWorkflowStatistics()
    {
        $request = '/workflow/statistics.json';

        $output = $this->request($request, array(), 'GET', true);

        if ($output['status'] !== 200) {
            return false;
        }

        $decode = json_decode($output['var'], true);

        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        return $decode;
    }

    /**
     * Get counted workflow instances.
     *
     * @param string $id
     * @param string $count
     *
     * @return array
     */
    public function getCountedWorkflowInstances($id = '', $count = '', $workflowName = '')
    {
        $request = '/workflow/instances.json?state=SUCCEEDED'.($workflowName ? '&workflowdefinition='.$workflowName : '').($id ? '&mp='.$id : '').($count ? '&count='.$count : '');

        $output = $this->request($request, array(), 'GET', true);

        if ($output['status'] !== 200) {
            return false;
        }

        $decode = json_decode($output['var'], true);

        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        return $decode;
    }

    /**
     * Stop workflow.
     *
     * @param array $workflow
     *
     * @return bool
     */
    public function stopWorkflow(array $workflow = array())
    {
        if ($this->deleteArchiveMediaPackage) {
            if (isset($workflow['id'])) {
                $request = '/workflow/stop';
                $params = array('id' => $workflow['id']);
                $output = $this->request($request, $params, 'POST', true);
                if ($output['status'] !== 200) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Request.
     *
     * Makes a given request (path)
     * GET or POST
     * to the Opencast server
     * using or not the admin url
     *
     * @param string $path
     * @param array  $query
     * @param string $method
     * @param bool   $useAdminUrl
     *
     * @return array
     */
    private function request($path, $query = array(), $method = 'GET', $useAdminUrl = false)
    {
        if ($useAdminUrl) {
            $requestUrl = $this->getAdminUrl() . $path;
        } else {
            $requestUrl = $this->url.$path;
        }
        if (false === $request = curl_init($requestUrl)) {
            throw new \RuntimeException('Unable to create a new curl handle with URL: '.$requestUrl.'.');
        }

        switch ($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($query));
            break;
        case 'PUT':
            break;
        case 'DELETE':
            break;
        default: throw new \Exception('Method "'.$method.'" not allowed.');
        }

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, false);

        if ($this->user != '') {
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($request, CURLOPT_USERPWD, $this->user.':'.$this->passwd);
            curl_setopt($request, CURLOPT_HTTPHEADER, array('X-Requested-Auth: Digest',
                                                            'X-Opencast-Matterhorn-Authorization: true', ));
        }

        $output = array();
        $output['var'] = curl_exec($request);
        $output['error'] = curl_error($request);
        $output['status'] = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        if ($method == 'GET') {
            if (200 != $output['status']) {
                throw new \Exception('Error Processing Request', 1);
            }
        }

        return $output;
    }
}
