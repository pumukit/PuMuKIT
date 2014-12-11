<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class PersonController extends SortableAdminController
{
    /**
   * Create new person
   */
  public function listAutocompleteAction(Request $request)
  {
      $config = $this->getConfiguration();
      $pluralName = $config->getPluralResourceName();

      $role = $this->getResourceFromId('Role', $request->get('roleId'));
      $multimediaObject = $this->getResourceFromId('MultimediaObject', $request->get('mmId'));

      // TODO complete functionally


      $criteria = $this->getCriteria($config);
      $resources = $this->getResources($request, $config, $criteria);

      $view = $this
      ->view()
      ->setTemplate($config->getTemplate('listautocomplete.html'))
      ->setData(array(
              'persons' => $resources,
              'mm' => $multimediaObject,
              'role' => $role,
              ));

      return $this->handleView($view);
  }

  /**
   * Get resource from id
   */
  private function getResourceFromId($className, $id)
  {
      $dm = $this->get('doctrine_mongodb.odm.document_manager');
      $repository = $dm->getRepository('PumukitSchemaBundle:'.$className);

      $resource = $repository->find($id);

      return $resource;
  }
}
