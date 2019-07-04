<?php

namespace Pumukit\OpencastBundle\Services;

use Pumukit\SchemaBundle\Document\Series;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Role\Role;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\RoleHierarchy;

/**
 * Class ClientService.
 */
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
     * ClientService constructor.
     *
     * @param string               $url
     * @param string               $user
     * @param string               $passwd
     * @param string               $player
     * @param string               $scheduler
     * @param string               $dashboard
     * @param bool                 $deleteArchiveMediaPackage
     * @param string               $deletionWorkflowName
     * @param bool                 $manageOpencastUsers
     * @param bool                 $insecure
     * @param null                 $adminUrl
     * @param LoggerInterface|null $logger
     * @param RoleHierarchy|null   $roleHierarchy
     */
    public function __construct($url = '', $user = '', $passwd = '', $player = '/engage/ui/watch.html', $scheduler = '/admin/index.html#/recordings', $dashboard = '/dashboard/index.html', $deleteArchiveMediaPackage = false, $deletionWorkflowName = 'delete-archive', $manageOpencastUsers = false, $insecure = false, $adminUrl = null, LoggerInterface $logger = null, RoleHierarchy $roleHierarchy = null)
    {
        $this->logger = $logger;

        if (!function_exists('curl_init')) {
            $this->logger->error(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
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
     * Get Url from Opencast server (Engage node in cluster).
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
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function getAdminUrl()
    {
        if ($this->adminUrl) {
            return $this->adminUrl;
        }

        $output = $this->request('/info/components.json');
        $decode = $this->decodeJson($output['var']);

        if (isset($decode['admin']) &&
            filter_var($decode['admin'], FILTER_VALIDATE_URL)) {
            $this->adminUrl = $decode['admin'];
        }

        return $this->adminUrl;
    }

    /**
     * Get scheduler url from Opencast.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getSchedulerUrl()
    {
        return ('/' === $this->scheduler[0]) ? $this->getAdminUrl().$this->scheduler : $this->scheduler;
    }

    /**
     * Get player url from Opencast.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getDashboardUrl()
    {
        return ('/' === $this->dashboard[0]) ? $this->getAdminUrl().$this->dashboard : $this->dashboard;
    }

    /**
     * Get media packages.
     *
     * Gets all the media packages from the Opencast server accordion to input parameters
     *
     * @param $query
     * @param $limit
     * @param $offset
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function getMediaPackages($query, $limit, $offset)
    {
        $output = $this->request('/search/episode.json?'.($query ? 'q='.urlencode($query).'&' : '').'limit='.$limit.'&offset='.$offset);

        if (200 !== $output['status']) {
            return false;
        }
        $decode = $this->decodeJson($output['var']);

        $return = [0, []];

        if (0 == $decode['search-results']['total']) {
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
     * Get media package from given id.
     *
     * @param $id
     *
     * @return mixed|bool|null
     *
     * @throws \Exception
     */
    public function getMediaPackage($id)
    {
        $output = $this->request('/search/episode.json?id='.$id);

        if (200 !== $output['status']) {
            return null;
        }
        $decode = $this->decodeJson($output['var']);

        if (0 == $decode['search-results']['total']) {
            return null;
        }
        if ($decode['search-results']['limit'] > 1) {
            return $decode['search-results']['result'][0]['mediapackage'];
        } else {
            return $decode['search-results']['result']['mediapackage'];
        }
    }

    /**
     * Get full media package from given id.
     *
     * @param $id
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getFullMediapackage($id)
    {
        $output = $this->request('/search/episode.json?id='.$id);

        if (200 !== $output['status']) {
            return false;
        }
        $decode = $this->decodeJson($output['var']);

        if (0 == $decode['search-results']['total']) {
            return false;
        }
        if ($decode['search-results']['limit'] > 1) {
            return $decode['search-results']['result'][0];
        } else {
            return $decode['search-results']['result'];
        }
    }

    /**
     * @param $id
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function getMasterMediaPackage($id)
    {
        $version = $this->getOpencastVersion();

        if ($version >= '3.0.0') {
            return $this->getMediaPackageFromAssets($id);
        }

        if ($version >= '2.0.0') {
            return $this->getMediaPackageFromArchive($id);
        }

        if ($version >= '1.4.0' && $version < '1.7.0') {
            return $this->getMediaPackageFromArchive($id);
        }

        if (0 == strpos($version, '1.2')) {
            return $this->getMediaPackageFromWorkflow($id);
        }

        throw new \Exception('There is no case for this version of Opencast ('.$version.')');
    }

    /**
     * @param $id
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function getMediaPackageFromWorkflow($id)
    {
        $output = $this->request('/workflow/instances.json?state=SUCCEEDED&mp='.$id, [], 'GET', true);
        if (200 == $output['status']) {
            $decode = $this->decodeJson($output['var']);

            if (isset($decode['workflows']['workflow']['mediapackage'])) {
                return $decode['workflows']['workflow']['mediapackage'];
            }

            if (isset($decode['workflows']['workflow'][0]['mediapackage'])) {
                return $decode['workflows']['workflow'][0]['mediapackage'];
            }
        }

        return null;
    }

    /**
     * @param $id
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function getMediaPackageFromAssets($id)
    {
        $output = $this->request('/assets/episode/'.$id, [], 'GET', true);
        if (200 == $output['status']) {
            $decode = $this->decodeXML($output);

            return $decode;
        }

        return null;
    }

    /**
     * Get media package from archive with given id.
     *
     * @param $id
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getMediaPackageFromArchive($id)
    {
        // NOTE: BC for OC 1.4 to 1.6
        $output = $this->request('/episode/episode.json?id='.$id, [], 'GET', true);
        if (200 !== $output['status']) {
            // NOTE: BC for OC 2.x
            $output = $this->request('/archive/episode.json?id='.$id, [], 'GET', true);
            if (200 !== $output['status']) {
                return false;
            }
        }

        $decode = $this->decodeJson($output['var']);

        if (0 == $decode['search-results']['total']) {
            return false;
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
     * @return bool
     *
     * @throws \Exception
     */
    public function applyWorkflowToMediaPackages(array $mediaPackagesIds = [], $workflowName = '')
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

        $request = '/admin-ng/tasks/new';
        $opencastVersion = $this->getOpencastVersion();
        $configurationParameters = [
            'retractFromEngage' => 'true',
            'retractFromAws' => 'false',
            'retractFromApi' => 'true',
            'retractPreview' => 'true',
            'retractFromOaiPmh' => 'true',
            'retractFromYouTube' => 'false',
        ];
        $parameters = [];

        // SUPPORT FOR OPENCAST < 2.0
        if ($opencastVersion < '2.0.0') {
            $request = '/episode/apply/'.$workflowName;

            $mediaPackageIdsParameter = '';
            foreach ($mediaPackagesIds as $index => $id) {
                $mediaPackageIdsParameter = $mediaPackageIdsParameter.$id;
                if ($index < (count($mediaPackagesIds) - 1)) {
                    $mediaPackageIdsParameter = $mediaPackageIdsParameter.',+';
                }
            }
            $parameters = [
                'mediaPackageIds' => $mediaPackageIdsParameter,
                'engage' => 'Matterhorn+Engage+Player',
            ];
        // SUPPORT FOR OPENCAST < 6.0
        } elseif ($opencastVersion < '6.0.0') {
            $parameters = [
                'metadata' => json_encode(
                    [
                        'workflow' => $workflowName,
                        'configuration' => $configurationParameters,
                        'eventIds' => $mediaPackagesIds,
                    ]
                ),
            ];
        // DEFAULT
        } else {
            $configurationsById = [];
            foreach ($mediaPackagesIds as $mediaPackageId) {
                $configurationsById[$mediaPackageId] = $configurationParameters;
            }
            $parameters = [
                'metadata' => json_encode(
                    [
                        'workflow' => $workflowName,
                        'configuration' => $configurationsById,
                    ]
                ),
            ];
        }

        $output = $this->request($request, $parameters, 'POST', true);

        if (!in_array($output['status'], [204, 201])) {
            $this->logger->addError(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                    .') Opencast error. Status != 204. - error: '.$output['error'].' - var: '.$output['var'].' - status: '.$output['status'].' - params:'.json_encode($parameters));

            return false;
        }

        return true;
    }

    /**
     * Get workflow statistics.
     *
     * Used to get the total number of workflows
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function getWorkflowStatistics()
    {
        $request = '/workflow/statistics.json';

        $output = $this->request($request, [], 'GET', true);

        if (200 !== $output['status']) {
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
     * @param string $workflowName
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function getCountedWorkflowInstances($id = '', $count = '', $workflowName = '')
    {
        $request = '/workflow/instances.json?state=SUCCEEDED'.($workflowName ? '&workflowdefinition='.$workflowName : '').($id ? '&mp='.$id : '').($count ? '&count='.$count : '');

        $output = $this->request($request, [], 'GET', true);

        if (200 !== $output['status']) {
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
     *
     * @throws \Exception
     */
    public function stopWorkflow(array $workflow = [])
    {
        if ($this->deleteArchiveMediaPackage) {
            if (isset($workflow['id'])) {
                $request = '/workflow/stop';
                $params = ['id' => $workflow['id']];
                $output = $this->request($request, $params, 'POST', true);
                if (200 !== $output['status']) {
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
     *
     * @throws \Exception
     */
    public function createUser(User $user)
    {
        if ($this->manageOpencastUsers) {
            $request = '/user-utils/';
            $roles = $this->getUserRoles($user);
            $params = [
                            'username' => $user->getUsername(),
                            'password' => 'pumukit',
                            'roles' => $roles,
                            ];
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
     *
     * @throws \Exception
     */
    public function updateUser(User $user)
    {
        if ($this->manageOpencastUsers) {
            $request = '/user-utils/'.$user->getUsername().'.json';
            $roles = $this->getUserRoles($user);
            $params = [
                            'username' => $user->getUsername(),
                            'password' => 'pumukit',
                            'roles' => $roles,
                            ];
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
     *
     * @throws \Exception
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
     * Updates the Opencast series metadata.
     *
     * Updates the Opencast series metadata based on the associated PuMuKIT series. If
     * the Opencast series does not exist, it creates a new Opencast series and updates
     * the Opencast id on the PuMuKIT series.
     *
     * @param $series
     *
     * @return array
     *
     * @throws \Exception
     */
    public function updateOpencastSeries(Series $series)
    {
        $seriesOpencastId = $series->getProperty('opencast');
        if (null === $seriesOpencastId) {
            throw new \Exception('Error trying to update an Opencast series. Error: No opencast ID', 404);
        }
        $metadata = [
            [
                'id' => 'title',
                'value' => $series->getTitle(),
            ],
            [
                'id' => 'description',
                'value' => $series->getDescription(),
            ],
        ];
        //There is an Opencast API error. The 'type' parameter should be taken from the form,
        //  but it is taken from the query. Added 'type' in both ways for good measure.
        $type = 'dublincore/series';
        $params = [
            'metadata' => json_encode($metadata),
            'type' => $type,
        ];
        $requestUrl = "/api/series/$seriesOpencastId/metadata";
        $requestUrl .= "?type=$type";
        $output = $this->request($requestUrl, $params, 'PUT', true);
        if (200 !== $output['status']) {
            throw new \Exception('Error trying to update an Opencast series metadata. Error '.$output['status'].':  '.$output['error'].' : '.$output['var'], $output['status']);
        }

        return $output;
    }

    /**
     * Creates an Opencast series.
     *
     * Creates an Opencast series and associates it to the PuMuKIT series.
     * The Opencast series metadata is taken from the PuMuKIT series.
     *
     * @param $series
     *
     * @return array
     *
     * @throws \Exception
     */
    public function createOpencastSeries(Series $series)
    {
        $metadata = [
            [
                'flavor' => 'dublincore/series',
                'fields' => [
                    [
                        'id' => 'title',
                        'value' => $series->getTitle(),
                    ],
                    [
                        'id' => 'description',
                        'value' => $series->getDescription(),
                    ],
                ],
            ],
        ];
        $acl = [];
        $params = [
            'metadata' => json_encode($metadata),
            'acl' => json_encode($acl),
        ];
        $requestUrl = '/api/series';
        $output = $this->request($requestUrl, $params, 'POST', true);
        if (201 !== $output['status']) {
            throw new \Exception('Error trying to create an Opencast series. Error '.$output['status'].':  "'.$output['error'].' : '.$output['var'], $output['status']);
        }

        return $output;
    }

    /**
     * Deletes an Opencast series.
     *
     * Deletes the Opencast series metadata associated to the PuMuKIT series.
     *
     * @param $series
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function deleteOpencastSeries(Series $series)
    {
        $seriesOpencastId = $series->getProperty('opencast');
        if (null === $seriesOpencastId) {
            return null;
        }
        $requestUrl = "/api/series/$seriesOpencastId";
        $output = $this->request($requestUrl, [], 'DELETE', true);
        if (204 !== $output['status']) {
            throw new \Exception('Error trying to delete an Opencast series. Error '.$output['status'].':  "'.$output['error'].' : '.$output['var'], $output['status']);
        }

        return $output;
    }

    /**
     * Request.
     *
     * Makes a given request (path) GET or POST  to the Opencast server using or not the admin url
     *
     * @param        $path
     * @param array  $params
     * @param string $method
     * @param bool   $useAdminUrl
     *
     * @return array
     *
     * @throws \Exception
     */
    private function request($path, $params = [], $method = 'GET', $useAdminUrl = false)
    {
        if ($useAdminUrl) {
            $requestUrl = $this->getAdminUrl().$path;
        } else {
            $requestUrl = $this->url.$path;
        }

        $fields = (is_array($params)) ? http_build_query($params) : $params;

        $header = ['X-Requested-Auth: Digest',
                        'X-Opencast-Matterhorn-Authorization: true', ];

        $this->logger->debug(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
                                .') Requested URL "'.$requestUrl.'" '
                                .'with method "'.$method.'" '
                                .'and params: '.$fields);

        if (false === $request = curl_init($requestUrl)) {
            $this->logger->error(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
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

        /*curl_setopt($request, CURLOPT_VERBOSE, true);*/

        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($request, CURLOPT_TIMEOUT, 10);

        if ($this->insecure) {
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ('' != $this->user) {
            curl_setopt($request, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            curl_setopt($request, CURLOPT_USERPWD, $this->user.':'.$this->passwd);
            curl_setopt($request, CURLOPT_HTTPHEADER, $header);
        }

        $output = [];
        $output['var'] = curl_exec($request);
        $output['error'] = curl_error($request);
        $output['status'] = curl_getinfo($request, CURLINFO_HTTP_CODE);

        curl_close($request);

        if ('GET' == $method) {
            if (200 != $output['status']) {
                $this->logger->error(__CLASS__.'['.__FUNCTION__.'](line '.__LINE__
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
     * @return mixed
     *
     * @throws \Exception
     */
    private function decodeJson($jsonString = '')
    {
        $decode = json_decode($jsonString, true);
        if (!($decode)) {
            throw new \Exception('Opencast communication error');
        }

        return $decode;
    }

    /**
     * @param array $xmlString
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    private function decodeXML($xmlString = [])
    {
        $decode = null;
        if (is_array($xmlString)) {
            $xml = simplexml_load_string($xmlString['var'], 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $decode = json_decode($json, true);
        }

        if (!$decode) {
            throw new \Exception('Opencast communication error');
        }

        return $decode;
    }

    /**
     * @param User $user
     *
     * @return string
     */
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

    /**
     * @param $url
     *
     * @return bool|string|null
     *
     * @throws \Exception
     */
    public function getSpatialField($url)
    {
        if (0 === strpos($url, $this->url)) {
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                return null;
            }
            $response = $this->request($path);
        } else {
            if ($this->insecure) {
                $dargs = [
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ];
                $response = ['var' => file_get_contents($url, false, stream_context_create($dargs))];
            } else {
                $response = ['var' => file_get_contents($url)];
            }
        }

        $start = strrpos($response['var'], '<dcterms:spatial>');
        $end = strrpos($response['var'], '</dcterms:spatial>');

        if ((false !== $start) && (false !== $end)) {
            $start += strlen('<dcterms:spatial>');

            return substr($response['var'], $start, $end - $start);
        }

        return null;
    }

    public function removeEvent($id)
    {
        $output = $this->request('/admin-ng/event/'.$id, [], 'DELETE', true);
        if (!$output) {
            throw new \Exception("Can't access to admin-ng/event");
        }

        return null;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public function getOpencastVersion()
    {
        $output = $this->request('/info/components.json');
        if (!$output) {
            throw new \Exception("Can't access to /info/components.json");
        }
        $decode = $this->decodeJson($output['var']);
        if (isset($decode['rest'][0]['version'])) {
            return $decode['rest'][0]['version'];
        }

        throw new \Exception("Cant't recognize ['rest'][0]['version'] from /info/components.json");
    }
}
