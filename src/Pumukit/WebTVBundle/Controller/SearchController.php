<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SearchController extends Controller
{

  /**
   * @Route("/searchseries")
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function seriesAction(Request $request)
  {
      $numberCols = 2;
      if( $this->container->hasParameter('columns_objs_search')){
          $numberCols = $this->container->getParameter('columns_objs_search');
      }

      $this->get('pumukit_web_tv.breadcrumbs')->addList('Series search', 'pumukit_webtv_search_series');

      $search_found = $request->query->get('search');
      $start_found = $request->query->get('start');
      $end_found = $request->query->get('end');

      $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

      $queryBuilder = $repository_series->createQueryBuilder();

      if ($search_found != '') {
          $queryBuilder->field('$text')->equals(array('$search' => $search_found));
      }

      if ($start_found != 'All' && $start_found != '') {
          $start = \DateTime::createFromFormat('d/m/Y', $start_found);
          $queryBuilder->field('public_date')->gt($start);
      }

      if ($end_found != 'All' && $end_found != '') {
          $end = \DateTime::createFromFormat('d/m/Y', $end_found);
          $queryBuilder->field('public_date')->lt($end);
      }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'series',
                   'objects' => $pagerfanta,
                   'number_cols' => $numberCols);
  }

  /**
   * @Route("/searchmultimediaobjects")
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function multimediaObjectsAction(Request $request)
  {
      $numberCols = 2;
      if( $this->container->hasParameter('columns_objs_search')){
          $numberCols = $this->container->getParameter('columns_objs_search');
      }
      $this->get('pumukit_web_tv.breadcrumbs')->addList('Multimedia object search', 'pumukit_webtv_search_multimediaobjects');

      $tag_search = new Tag();

      $blocked_tag = $request->query->get('blockedTag');
      $blocked_tag_value = $request->query->get('blockedTagValue');
      $useBlockedTagAsGeneral = $request->query->get('useBlockedTagAsGeneral');
      $search_found = $request->query->get('search');
      $tags_found = $request->query->get('tags');
      $type_found = $request->query->get('type');
      $duration_found = $request->query->get('duration');
      $start_found = $request->query->get('start');
      $end_found = $request->query->get('end');
      $language_found = $request->query->get('language');

      $repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $repository_tags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

      $searchByTagCod = 'ITUNESU';
      if( $this->container->hasParameter('search.parent_tag.cod')) {
          $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');
      }
      $parentTag = $repository_tags->findOneByCod($searchByTagCod);
      if( !isset($parentTag)) {
          throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)',$searchByTagCod));
      }

      $parentTag2 = null;
      if( $this->container->hasParameter('search.parent_tag_2.cod')) {
          $searchByTagCod2 = $this->container->getParameter('search.parent_tag_2.cod');
          $parentTag2 = $repository_tags->findOneByCod($searchByTagCod2);
          if( !isset($parentTag2)) {
              throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)',$searchByTagCod));
          }
      }
      if( $blocked_tag_value !== null ) {
          $tags_found[] = $blocked_tag_value;
      }
      if($tags_found !== null) {
          $tags_found = array_values(array_diff($tags_found, array('All')));
      }

      $searchLanguages = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder()->distinct('tracks.language')->getQuery()->execute();

      $queryBuilder = $repository_multimediaObjects->createStandardQueryBuilder();

      if ($search_found != '') {
          $queryBuilder->field('$text')->equals(array('$search' => $search_found));
      }
      $blockedTag = null;
      if($blocked_tag_value) {
          $blockedTag = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($blocked_tag_value);
          $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag->getTitle(), 'pumukit_webtv_search_multimediaobjects');
      }
      if (count($tags_found) > 0) {
          $queryBuilder->field('tags.cod')->all($tags_found);
          if($useBlockedTagAsGeneral !== null && $blockedTag !== null ) {
              $queryBuilder->field('tags.path')->notIn(array(new \MongoRegex('/'.preg_quote($blockedTag->getPath()). '.*\|/')));
          }
      }

      if ($type_found != 'All' && $type_found != '') {
          $queryBuilder->field('tracks.only_audio')->equals($type_found == 'Audio');
      }

      if ($duration_found != 'All' && $duration_found != '') {
          if ($duration_found == '-5') {
              $queryBuilder->field('tracks.duration')->lte(300);
          }
          if ($duration_found == '-10') {
              $queryBuilder->field('tracks.duration')->lte(600);
          }
          if ($duration_found == '-30') {
              $queryBuilder->field('tracks.duration')->lte(1800);
          }
          if ($duration_found == '-60') {
              $queryBuilder->field('tracks.duration')->lte(3600);
          }
          if ($duration_found == '+60') {
              $queryBuilder->field('tracks.duration')->gt(3600);
          }
      }

      if ($start_found != 'All' && $start_found != '') {
          $start = \DateTime::createFromFormat('d/m/Y', $start_found);
          $queryBuilder->field('record_date')->gt($start);
      }

      if ($end_found != 'All' && $end_found != '') {
          $end = \DateTime::createFromFormat('d/m/Y', $end_found);
          $queryBuilder->field('record_date')->lt($end);
      }

      if($language_found != 'All' && $language_found != '') {
          $queryBuilder->field('tracks.language')->equals($language_found);
      }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'multimediaObject',
         'objects' => $pagerfanta,
         'parent_tag' => $parentTag,
         'parent_tag2' => $parentTag2,
         'tags_found' => $tags_found,
         'type_found' => $type_found,
         'duration_found' => $duration_found,
         'number_cols' => $numberCols,
         'languages' => $searchLanguages,
         'language_found' => $language_found,
         'blocked_tag' => $blocked_tag,
         'blocked_tag_value' => $blocked_tag_value,
         'blockedTag' => $blockedTag,
         'use_blocked_tag_as_general' => $useBlockedTagAsGeneral);
    }

    private function createPager($objects, $page)
    {
        $limit = 10;
        if ($this->container->hasParameter('limit_objs_search')){
            $limit = $this->container->getParameter('limit_objs_search');
        }
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
