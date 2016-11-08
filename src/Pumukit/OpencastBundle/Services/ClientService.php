<?php

namespace Pumukit\OpencastBundle\Services;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\RoleHierarchy;

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
    private $manageOpencastUsers;
    private $insecure = false;
    private $logger;
    private $roleHierarchy;

    /**
     * Constructor.
     *
     * @param string          $url
     * @param string          $user
     * @param string          $passwd
     * @param string          $player
     * @param bool            $deleteArchiveMediaPackage
     * @param string          $deletionWorkflowName
     * @param bool            $manageOpencastUsers
     * @param LoggerInterface $logger
     */
    public function __construct($url = '', $user = '', $passwd = '', $player = '/engage/ui/watch.html', $scheduler = '/admin/index.html#/recordings', $dashboard = '/dashboard/index.html',
                                $deleteArchiveMediaPackage = false, $deletionWorkflowName = 'delete-archive', $manageOpencastUsers = false, $insecure = false, $adminUrl = null, LoggerInterface $logger,
                                RoleHierarchy $roleHierarchy = null)
    {
        $this->logger = $logger;

        if (!function_exists('curl_init')) {
            $this->logger->addError(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                    .') The function "curl_init" does not exist. '
                                    .'Curl is required to execute remote commands.');
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
        $this->manageOpencastUsers = $manageOpencastUsers;
        $this->insecure = $insecure;
        $this->adminUrl = $adminUrl;
        $this->roleHierarchy = $roleHierarchy;
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
        $decode = $this->decodeJson($output['var']);
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
        $output = $this->request('/search/episode.json?'.($query ? 'q='.urlencode($query).'&' : '').'limit='.$limit.'&offset='.$offset);

        if ($output['status'] !== 200) {
            return false;
        }
        $decode = $this->decodeJson($output['var']);

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
        $decode = $this->decodeJson($output['var']);

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
        $decode = $this->decodeJson($output['var']);

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

        $decode = $this->decodeJson($output['var']);

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

        $decode = $this->decodeJson($output['var']);

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
     * Create User.
     *
     * @param User $user
     *
     * @return bool
     */
    public function createUser(User $user)
    {
        if ($this->manageOpencastUsers) {
            $request = '/user-utils/';
            $roles = $this->getUserRoles($user);
            $params = array(
                            'username' => $user->getUsername(),
                            'password' => 'pumukit',
                            'roles' => $roles,
                            );
            $output = $this->request($request, $params, 'POST', true);
            if (201 != $output['status']) {
                if (409 == $output['status']) {
                    throw new \Exception('Conflict '.$output['status'].'. An user with this username "'.$user->getUsername().'" already exist.', 1);
                } else {
                    throw new \Exception('Error '.$output['status'].' Processing Request on Creating User "'.$user->getUsername().'"', 1);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Update User.
     *
     * @param User $user
     *
     * @return bool
     */
    public function updateUser(User $user)
    {
        if ($this->manageOpencastUsers) {
            $request = '/user-utils/'.$user->getUsername().'.json';
            $roles = $this->getUserRoles($user);
            $params = array(
                            'username' => $user->getUsername(),
                            'password' => 'pumukit',
                            'roles' => $roles,
                            );
            $output = $this->request($request, $params, 'PUT', true);
            if (200 != $output['status']) {
                if (404 == $output['status']) {
                    throw new \Exception('Error '.$output['status'].'. User with this username "'.$user->getUsername().'" not found.', 1);
                } else {
                    throw new \Exception('Error '.$output['status'].' Processing Request on Updating User "'.$user->getUsername().'"', 1);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Delete User.
     *
     * @param User $user
     *
     * @return bool
     */
    public function deleteUser(User $user)
    {
        if ($this->manageOpencastUsers) {
            $request = '/user-utils/'.$user->getUsername().'.json';
            $output = $this->request($request, '', 'DELETE', true);
            if (200 != $output['status']) {
                if (404 == $output['status']) {
                    throw new \Exception('Error '.$output['status'].'. User with this username "'.$user->getUsername().'" not found.', 1);
                } else {
                    throw new \Exception('Error '.$output['status'].' Processing Request on Deleting User "'.$user->getUsername().'"', 1);
                }
            }

            return true;
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
     * @param string       $path
     * @param array|string $params
     * @param string       $method
     * @param bool         $useAdminUrl
     *
     * @return array
     */
    private function request($path, $params = array(), $method = 'GET', $useAdminUrl = false)
    {
        if ($useAdminUrl) {
            $requestUrl = $this->getAdminUrl().$path;
        } else {
            $requestUrl = $this->url.$path;
        }

        $fields = (is_array($params)) ? http_build_query($params) : $params;

        $header = array('X-Requested-Auth: Digest',
                        'X-Opencast-Matterhorn-Authorization: true', );

        $this->logger->addDebug(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                .') Requested URL "'.$requestUrl.'" '
                                .'with method "'.$method.'" '
                                .'and params: '.$fields);

        if (false === $request = curl_init($requestUrl)) {
            $this->logger->addError(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                    .') Unable to create a new curl handle with URL: '.$requestUrl.'.');
            throw new \RuntimeException('Unable to create a new curl handle with URL: '.$requestUrl.'.');
        }

        switch ($method) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, $fields);
            break;
        case 'PUT':
            $header[] = 'Content-Length: '.strlen($fields);
            curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($request, CURLOPT_POSTFIELDS, $fields);
            break;
        case 'DELETE':
            curl_setopt($request, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        default:
            throw new \Exception('Method "'.$method.'" not allowed.');
        }

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($request, CURLOPT_TIMEOUT, 1);

        if ($this->insecure) {
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($this->user != '') {
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($request, CURLOPT_USERPWD, $this->user.':'.$this->passwd);
            curl_setopt($request, CURLOPT_HTTPHEADER, $header);
        }

        $output = array();
        $output['var'] = curl_exec($request);
        $output['error'] = curl_error($request);
        $output['status'] = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        if ($method == 'GET') {
            if (200 != $output['status']) {
                $this->logger->addError(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                      .') Error ('.$output['status'].') Processing Request : '.$requestUrl.'.');
                throw new \Exception(sprintf('Error %s Processing Request (%s)', $output['status'], $requestUrl), 1);
            }
        }

        return $output;
    }

    /**
     * Decode json string.
     *
     * @param string $jsonString
     *
     * @return array $decode
     */
    private function decodeJson($jsonString = '')
    {
        $decode = json_decode($jsonString, true);
        if (!($decode)) {
            throw new \Exception('Opencast Matterhorn communication error');
        }

        return $decode;
    }

    private function getUserRoles(User $user)
    {
        if ($this->roleHierarchy) {
            $userRoles = array_map(function ($r) {
                return new Role($r);
            }, $user->getRoles());
            $allRoles = $this->roleHierarchy->getReachableRoles($userRoles);
            $roles = array_map(function ($r) {
                return $r->getRole();
            }, $allRoles);
        } else {
            $roles = $user->getRoles();
        }

        return '["'.implode('","', $roles).'"]';
    }
}
