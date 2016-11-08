<?php

namespace Pumukit\OpencastBundle\Services;

class WorkflowService
{
    private $clientService;
    private $deletionWorkflowName;
    private $deleteArchiveMediaPackage;

    /**
     * Constructor.
     *
     * @param ClientService $clientService
     * @param bool          $deleteArchiveMediaPackage
     * @param string        $deletionWorkflowName
     */
    public function __construct(ClientService $clientService, $deleteArchiveMediaPackage = false, $deletionWorkflowName = 'delete-archive')
    {
        $this->clientService = $clientService;
        $this->deleteArchiveMediaPackage = $deleteArchiveMediaPackage;
        $this->deletionWorkflowName = $deletionWorkflowName;
    }

    /**
     * Check workflow ended.
     *
     * @param string $mediaPackageId
     *
     * @return bool
     */
    public function stopSucceededWorkflows($mediaPackageId = '')
    {
        $errors = 0;
        if ($this->deleteArchiveMediaPackage) {
            if ($mediaPackageId) {
                $deletionWorkflow = $this->getAllWorkflowInstances($mediaPackageId, $this->deletionWorkflowName);
                if (null != $deletionWorkflow) {
                    $errors = $this->stopSucceededWorkflow($deletionWorkflow, $errors);
                    $workflows = $this->getAllWorkflowInstances($mediaPackageId);
                    foreach ($workflows as $workflow) {
                        $errors = $this->stopSucceededWorkflow($workflow, $errors);
                    }
                }
            } else {
                $deletionWorkflows = $this->getAllWorkflowInstances('', $this->deletionWorkflowName);
                foreach ($deletionWorkflows as $deletionWorkflow) {
                    $errors = $this->stopSucceededWorkflow($deletionWorkflow, $errors);
                    $mediaPackageId = $this->getMediaPackageIdFromWorkflow($deletionWorkflow);
                    $mediaPackageWorkflows = $this->getAllWorkflowInstances($mediaPackageId, '');
                    foreach ($mediaPackageWorkflows as $mediaPackageWorkflow) {
                        $errors = $this->stopSucceededWorkflow($mediaPackageWorkflow, $errors);
                    }
                }
            }
        }
        if ($errors > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get all workflow instances
     * with given mediapackage id.
     *
     * @param string $id
     *
     * @return array
     */
    private function getAllWorkflowInstances($id = '', $workflowName = '')
    {
        $statistics = $this->clientService->getWorkflowStatistics();

        $total = 0;
        if (isset($statistics['statistics']['total'])) {
            $total = $statistics['statistics']['total'];
        }

        if ($total == 0) {
            return null;
        }

        $decode = $this->clientService->getCountedWorkflowInstances($id, $total, $workflowName);

        $instances = array();
        if (isset($decode['workflows']['workflow'])) {
            $instances = $decode['workflows']['workflow'];
        }
        if (isset($instances['state'])) {
            $instances = array('0' => $instances);
        }

        return $instances;
    }

    /**
     * Get workflows template.
     *
     * @param array  $workflows
     * @param string $template
     *
     * @return array
     */
    private function getWorkflowsWithTemplate(array $workflows = array(), $template = '')
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
     * Is workflow succeeded.
     *
     * @param array $workflow
     *
     * @return bool
     */
    private function isWorkflowSucceeded(array $workflow = array())
    {
        if ($workflow && isset($workflow['state'])) {
            if ('SUCCEEDED' === $workflow['state']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get mediapackage id from workflow.
     *
     * @param array $workflow
     *
     * @return string $mediaPackageId
     */
    private function getMediaPackageIdFromWorkflow(array $workflow = array())
    {
        if ($workflow && isset($workflow['mediapackage']['id'])) {
            return $workflow['mediapackage']['id'];
        }

        return null;
    }

    /**
     * Delete workflow if succeeded
     * and get the errors.
     *
     * @param array $workflow
     * @param int   $errors
     *
     * @return int $errors
     */
    private function stopSucceededWorkflow(array $workflow = array(), $errors = 0)
    {
        if ($this->deleteArchiveMediaPackage) {
            $isSucceeded = $this->isWorkflowSucceeded($workflow);
            if ($isSucceeded) {
                $output = $this->clientService->stopWorkflow($workflow);
                if (!$output) {
                    ++$errors;
                }
            }
        }

        return $errors;
    }

    /**
     * Get workflows with mediapackage id.
     *
     * @param array  $workflows
     * @param string $mediaPackageId
     *
     * @return array
     */
    private function getWorkflowsWithMediaPackageId(array $workflows = array(), $mediaPackageId = '')
    {
        $mediaPackageIdWorkflows = array();
        foreach ($workflows as $workflow) {
            if (isset($workflow['mediapackage']['id'])) {
                if ($mediaPackageId == $workflow['mediapackage']['id']) {
                    $mediaPackageIdWorkflows[] = $workflow;
                }
            }
        }

        return $mediaPackageIdWorkflows;
    }
}
