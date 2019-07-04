<?php

namespace Pumukit\NewAdminBundle\Twig;

use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class CatalogueExtension.
 */
class CatalogueExtension extends \Twig_Extension
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TagCatalogueService
     */
    private $tagCatalogueService;

    /**
     * CatalogueExtension constructor.
     *
     * @param DocumentManager     $documentManager
     * @param TranslatorInterface $translator
     * @param TagCatalogueService $tagCatalogueService
     */
    public function __construct(DocumentManager $documentManager, TranslatorInterface $translator, TagCatalogueService $tagCatalogueService)
    {
        $this->dm = $documentManager;
        $this->translator = $translator;
        $this->tagCatalogueService = $tagCatalogueService;
    }

    /**
     * Get functions.
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('all_custom_fields', [$this, 'getAllCustomFields']),
            new \Twig_SimpleFunction('render_object_field', [$this, 'renderObjectField']),
        ];
    }

    /**
     * @return array
     */
    public function getAllCustomFields()
    {
        $allFields = $this->tagCatalogueService->getAllCustomListFields();

        return $allFields;
    }

    /**
     * @param MultimediaObject $object
     * @param SessionInterface $session
     * @param                  $field
     *
     * @return string
     *
     * @throws \Exception
     */
    public function renderObjectField(MultimediaObject $object, SessionInterface $session, $field)
    {
        $render = $this->tagCatalogueService->renderField($object, $session, $field);

        return $render;
    }
}
