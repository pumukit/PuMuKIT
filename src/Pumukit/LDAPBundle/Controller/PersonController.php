<?php

namespace Pumukit\LDAPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Role;

/**
 * @Route("/person")
 * @Security("is_granted('ROLE_ACCESS_MULTIMEDIA_SERIES')")
 */
class PersonController extends Controller
{
    /**
     * @Route("/button", name="pumukit_ldap_person_button")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitLDAPBundle:Person:button.html.twig")
     */
    public function buttonAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $ldapService = $this->get('pumukit_ldap.ldap');
        $ldapConnected = $ldapService->checkConnection();

        return [
                     'ldap_connected' => $ldapConnected,
                     'mm' => $multimediaObject,
                     'role' => $role,
                     ];
    }

    /**
     * @Route("/listautocomplete/{mmId}/{roleId}", name="pumukit_ldap_person_listautocomplete")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     * @Template("PumukitLDAPBundle:Person:listautocomplete.html.twig")
     */
    public function listautocompleteAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return [
                     'mm' => $multimediaObject,
                     'role' => $role,
                     'template' => $template,
                     ];
    }

    /**
     * Auto complete.
     *
     * @Route("/autocomplete", name="pumukit_ldap_person_autocomplete")
     */
    public function autocompleteAction(Request $request)
    {
        $ldapService = $this->get('pumukit_ldap.ldap');
        $login = $request->get('term');
        $out = [];
        try {
            $people = $ldapService->getListUsers('*'.$login.'*', '*'.$login.'*');
            foreach ($people as $person) {
                $out[] = [
                               'value' => $person['cn'],
                               'label' => $person['cn'],
                               'mail' => $person['mail'],
                               'cn' => $person['cn'],
                               ];
            }
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }

        return new JsonResponse($out);
    }

    /**
     * Link person to multimedia object with role.
     *
     * @Route("/link/{mmId}/{roleId}", name="pumukit_ldap_person_link")
     * @ParamConverter("multimediaObject", class="PumukitSchemaBundle:MultimediaObject", options={"id" = "mmId"})
     * @ParamConverter("role", class="PumukitSchemaBundle:Role", options={"id" = "roleId"})
     */
    public function linkAction(MultimediaObject $multimediaObject, Role $role, Request $request)
    {
        $cn = $request->get('cn');
        $email = $request->get('mail');
        $personService = $this->get('pumukitschema.person');
        $personalScopeRoleCode = $personService->getPersonalScopeRoleCode();
        try {
            $person = $personService->findPersonByEmail($email);
            if (null === $person) {
                $person = $this->createPersonFromLDAP($cn, $email);
            }
            $multimediaObject = $personService->createRelationPerson($person, $role, $multimediaObject);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 400);
        }
        $template = $multimediaObject->isPrototype() ? '_template' : '';

        return $this->render('PumukitNewAdminBundle:Person:listrelation.html.twig',
                             [
                                   'people' => $multimediaObject->getPeopleByRole($role, true),
                                   'role' => $role,
                                   'personal_scope_role_code' => $personalScopeRoleCode,
                                   'mm' => $multimediaObject,
                                   'template' => $template,
                                   ]);
    }

    private function createPersonFromLDAP($cn = '', $mail = '')
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $ldapService = $this->get('pumukit_ldap.ldap');
        try {
            $aux = $ldapService->getListUsers('', $mail);
            if (0 === count($aux)) {
                throw new \InvalidArgumentException('There is no LDAP user with the name "'.$cn.'" and email "'.$mail.'"');
            }
            $person = new Person();
            $person->setName($aux[0]['cn']);
            $person->setEmail($aux[0]['mail']);
            $dm->persist($person);
            $dm->flush();
        } catch (\Exception $e) {
            throw $e;
        }

        return $person;
    }
}
