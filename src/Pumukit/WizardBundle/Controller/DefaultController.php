<?php

namespace Pumukit\WizardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Finder\Finder;

class DefaultController extends Controller
{
  // TODO complete all actions

    /**
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Template()
     */
    public function seriesAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        return array(
                     'form_data' => $formData
                     );
    }

    /**
     * @Template()
     */
    public function typeAction($id, Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        $showPrevious = true;
        $seriesRepo = $this->get('doctrine_mongodb.odm.document_manager')
            ->getRepository('PumukitSchemaBundle:Series');
        $series = $seriesRepo->find($id);

        if ($series){
            $showPrevious = false;
            if (!$formData){
                $formData = array('series' => array(
                                                   'i18n_title' => $series->getI18nTitle(),
                                                   'i18n_description' => $series->getI18nDescription()
                                                   ));
            }
        }

        return array(
                     'series_id' => $id,
                     'form_data' => $formData,
                     'show_previous' => $showPrevious
                     );
    }

    /**
     * Option action
     */
    public function optionAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        if ('multiple' == $formData['type']['option']){
            return $this->redirect($this->generateUrl('pumukitwizard_default_track', array('pumukitwizard_form_data' => $formData)));
        }

        return $this->redirect($this->generateUrl('pumukitwizard_default_multimediaobject', array('pumukitwizard_form_data' => $formData)));
    }

    /**
     * @Template()
     */
    public function multimediaobjectAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        return array(
                     'form_data' => $formData
                     );
    }

    /**
     * @Template()
     */
    public function trackAction(Request $request)
    {
        $formData = $request->get('pumukitwizard_form_data');

        $masterProfiles = $this->get('pumukitencoder.profile')->getMasterProfiles(true);
        $factoryService = $this->get('pumukitschema.factory');
        $pubChannelsTags = $factoryService->getTagsByCod('PUBCHANNELS', true);

        $languages = Intl::getLanguageBundle()->getLanguageNames();

        return array(
                     'form_data' => $formData,
                     'master_profiles' => $masterProfiles,
                     'pub_channels' => $pubChannelsTags,
                     'languages' => $languages
                     );
    }

    /**
     * Upload action
     * @Template()
     */
    public function uploadAction(Request $request)
    {
        $trackService = $this->get('pumukitschema.track');
        $tagService = $this->get('pumukitschema.tag');

        $formData = $request->get('pumukitwizard_form_data');
        if ($formData){
            $seriesData = $this->getKeyData('series', $formData);
            $typeData = $this->getKeyData('type', $formData);
            $trackData = $this->getKeyData('track', $formData);

            $profile = $this->getKeyData('profile', $trackData);
            $priority = $this->getKeyData('priority', $trackData);
            $language = $this->getKeyData('language', $trackData);
            $description = $this->getKeyData('description', $trackData);

            $pubchannel = $this->getKeyData('pubchannel', $trackData);

            // TODO try catch exception
            try{
                if (empty($_FILES) && empty($_POST)){
                    throw new \Exception('PHP ERROR: File exceeds post_max_size ('.ini_get('post_max_size').')');
                }

                $option = $this->getKeyData('option', $typeData);
                if ('single' === $option){
                    $series = $this->getSeries($seriesData);
                    $multimediaObjectData = $this->getKeyData('multimediaobject', $formData);

                    $i18nTitle = $this->getKeyData('i18n_title', $multimediaObjectData);
                    if (empty(array_filter($i18nTitle))) $multimediaObjectData = $this->getDefaultFieldValuesInData($multimediobjectData, 'i18n_title', 'New', true);

                    $multimediaObject = $this->createMultimediaObject($multimediaObjectData, $series);

                    $filetype = $this->getKeyData('filetype', $trackData);
                    if ('file' === $filetype){
                        $selectedPath = $request->get('resource');
                        $multimediaObject = $trackService->createTrackFromLocalHardDrive($multimediaObject, $request->files->get('resource'), $profile, $priority, $language, $description);
                    }elseif ('inbox' === $filetype){
                        $selectedPath = $request->get('file');
                        $multimediaObject = $trackService->createTrackFromInboxOnServer($multimediaObject, $request->get('file'), $profile, $priority, $language, $description);
                    }
                    if ($multimediaObject && $pubchannel){
                        foreach($pubchannel as $tagCode => $valueOn){
                            $addedTags = $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                            // TODO #6465 : Review addition of Publication Channel (create service)
                            // * If MultimediaObject didn't contained PUCHWEBTV tag and now it does:
                            //   - execute job (master_copy to video_h264)
                            // * If MultimediaObject didn't contained ARCA tag and now it does:
                            //   - execute corresponding job
                        }
                    }
                }elseif ('multiple' === $option){
                    $series = $this->getSeries($seriesData);
                    $selectedPath = $request->get('file');
                    $finder = new Finder();
                    $finder->files()->in($selectedPath);
                    foreach ($finder as $f){
                        $filePath = $f->getRealpath();
                        $titleData = $this->getDefaultFieldValuesInData(array(), 'i18n_title', $f->getRelativePathname(), true);
                        $multimediaObject = $this->createMultimediaObject($titleData, $series);
                        if ($multimediaObject){
                            try{
                                $multimediaObject = $trackService->createTrackFromInboxOnServer($multimediaObject, $filePath, $profile, $priority, $language, $description);
                            }catch(\Exception $e){
                                // TODO: filter invalid files another way
                                if (!strpos($e->getMessage(), 'Unknown error')){
                                    $this->removeInvalidMultimediaObject($multimediaObject);
                                    throw $e;
                                }
                            }
                            foreach($pubchannel as $tagCode => $valueOn){
                                $addedTags = $this->addTagToMultimediaObjectByCode($multimediaObject, $tagCode);
                                // TODO #6465 : Review addition of Publication Channel (create service)
                                // * If MultimediaObject didn't contained PUCHWEBTV tag and now it does:
                                //   - execute job (master_copy to video_h264)
                                // * If MultimediaObject didn't contained ARCA tag and now it does:
                                //   - execute corresponding job
                            }
                        }
                    }
                }
            }catch(\Exception $e){
                // TODO filter unknown errors
                $message = preg_replace( "/\r|\n/", "", $e->getMessage());
                return array(
                             'uploaded' => 'failed',
                             'message' => $e->getMessage(),
                             );
            }
        }else{
            // TODO THROW EXCEPTION OR RENDER SPECIFIC TEMPLATE WITH MESSAGE
            return array(
                         'uploaded' => 'failed',
                         'message' => 'No data received'
                         );
        }

        return array(
                     'uploaded' => 'success',
                     'message' => 'Track(s) added'
                     );
    }

    /**
     * @Template()
     */
    public function endAction(Request $request)
    {
        // TODO complete

        return array('message' => 'success it seems');
    }

    /**
     * @Template()
     */
    public function errorAction(Request $request)
    {
        // TODO complete
        $errorMessage = $request->get('errormessage');

        return array('message' => $errorMessage);
    }

    /**
     * Get key data
     */
    private function getKeyData($key='nonexistingkey', $formData=array())
    {
        $keyData = array();
        if(array_key_exists($key, $formData)){
            $keyData = $formData[$key];
        }

        return $keyData;
    }

    /**
     * Get series (new or existing one)
     */
    private function getSeries($seriesData=array())
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $seriesRepo = $dm->getRepository('PumukitSchemaBundle:Series');

        $seriesId = $this->getKeyData('id', $seriesData);
        if ($seriesId && ('null' !== $seriesId)){
            $series = $seriesRepo->find($seriesId);
        }else{
            $series = $this->createSeries($seriesData);
        }

        return $series;
    }

    /**
     * Create Series
     */
    private function createSeries($seriesData=array())
    {
        if ($seriesData){
            $factoryService = $this->get('pumukitschema.factory');
            $series = $factoryService->createSeries();

            $i18nTitle = $this->getKeyData('i18n_title', $seriesData);
            if (empty(array_filter($i18nTitle))) $seriesData = $this->getDefaultFieldValuesInData($seriesData, 'i18n_title', 'New', true);

            $keys = array('i18n_title', 'i18n_description');
            $series = $this->setData($series, $seriesData, $keys);

            return $series;
        }

        return null;
    }

    /**
     * Create Multimedia Object
     */
    private function createMultimediaObject($mmData, $series)
    {
        if ($series){
            $factoryService = $this->get('pumukitschema.factory');
            $multimediaObject = $factoryService->createMultimediaObject($series);

            if ($mmData){
                $keys = array('i18n_title', 'i18n_subtitle', 'i18n_description', 'i18n_line2');
                $multimediaObject = $this->setData($multimediaObject, $mmData, $keys);
            }

            return $multimediaObject;
        }

        return null;
    }

    /**
     * Add Tag to Multimedia Object by Code
     */
    private function addTagToMultimediaObjectByCode(MultimediaObject $multimediaObject, $tagCode)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $tagRepo = $dm->getRepository('PumukitSchema:Tag');

        $addedTags = array();

        $tag = $tagRepo->findOneByCode($tagCode);
        if ($tag) $addedTags = $tagService->addTagToMultimediaObject($multimediaObject, $tag->getId());

        return $addedTags;
    }

    /**
     * Set data
     */
    private function setData($resource, $resourceData, $keys)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');

        foreach ($keys as $key){
            $value = $this->getKeyData($key, $resourceData);
            if ($value){
                $upperField = $this->getUpperFieldName($key);
                $setField = 'set'.$upperField;
                $resource->$setField($value);
            }
        }

        $dm->persist($resource);
        $dm->flush();

        return $resource;
    }

    /**
     * Remove Invalid Multimedia Object
     */
    private function removeInvalidMultimediaObject(MultimediaObject $multimediaObject)
    {
        $dm = $this->get('doctrine_mongodb.odm.document_manager');
        $dm->remove($multimediaObject);
        $dm->flush();
    }

    /**
     * Get default field values in data
     * for those important fields that can not be empty
     */
    private function getDefaultFieldValuesInData($resourceData=array(), $fieldName='', $defaultValue='', $isI18nField=false)
    {
        if ($fieldName && $defaultValue){
            if ($isI18nField){
                $resourceData[$fieldName] = array();
                $locales = $this->container->getParameter('pumukit2.locales');
                foreach($locales as $locale){
                    $resourceData[$fieldName][$locale] = $defaultValue;
                }
            }else{
                $resourceData[$fieldName] = $defaultValue;
            }
        }

        return $resourceData;
    }

    /**
     * Get uppercase field name
     * Converts something like 'i18n_title' into 'I18nTitle'
     */
    private function getUpperFieldName($key='')
    {
        $pattern = "/_[a-z]?/";
        $aux = preg_replace_callback($pattern, function($matches){
            return strtoupper(ltrim($matches[0], "_"));
          }, $key);

        return ucfirst($aux);
    }
}