<?php

namespace Pumukit\NewAdminBundle\Twig;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\NewAdminBundle\Services\TagCatalogueService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
        return $this->tagCatalogueService->getAllCustomListFields();
    }

    /**
     * @param MultimediaObject $object
     * @param SessionInterface $session
     * @param                  $field
     *
     * @throws \Exception
     *
     * @return string
     */
    public function renderObjectField(MultimediaObject $object, SessionInterface $session, $field)
    {
        return $this->tagCatalogueService->renderField($object, $session, $field);
    }
}
