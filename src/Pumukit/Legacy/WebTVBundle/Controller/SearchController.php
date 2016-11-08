<?php

namespace Pumukit\Legacy\WebTVBundle\Controller;

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
    private $limit = 10;

  /**
   * @Route("/searchseries", name="pumukit_webtv_search_series")
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function seriesAction(Request $request)
  {
      $this->get('pumukit_web_tv.breadcrumbs')->addList('Series search', 'pumukit_webtv_search_series');

      $search_found = $request->query->get('search');
      $start_found = $request->query->get('start');
      $end_found = $request->query->get('end');

      $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

      $queryBuilder = $repository_series->createQueryBuilder();

    //Obtenemos todas las series del repositorio que su titulo coincida con <$search_found>
    if ($search_found != '') {
        $queryBuilder->field('$text')->equals(array('$search' => $search_found));
    }

    //Obtenemos todos los objetos multimedia con fecha superior o igual a <$start_found>
    if ($start_found != 'All' && $start_found != '') {
        $start = \DateTime::createFromFormat('d/m/Y', $start_found);
        $queryBuilder->field('public_date')->gt($start);
    }

    //Obtenemos todos los objetos multimedia con fecha inferior o igual a <$end_found>
    if ($end_found != 'All' && $end_found != '') {
        $end = \DateTime::createFromFormat('d/m/Y', $end_found);
        $queryBuilder->field('public_date')->lt($end);
    }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'series',
         'objects' => $pagerfanta, );
  }

  /**
   * @Route("/searchmultimediaobjects", name="pumukit_webtv_search_multimediaobjects")
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function multimediaObjectsAction(Request $request)
  {
      $this->get('pumukit_web_tv.breadcrumbs')->addList('Multimedia object search', 'pumukit_webtv_search_multimediaobjects');

      $tag_search = new Tag();

    //Recogemos los campos de búsqueda de los filtros
    $search_found = $request->query->get('search');
      $tag_found = $request->query->get('tags');
      $type_found = $request->query->get('type');
      $duration_found = $request->query->get('duration');
      $start_found = $request->query->get('start');
      $end_found = $request->query->get('end');

    //Accedemos al repositorio de los objetos multimedia y de los tags
    $repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $repository_tags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

    //Obtenemos del repositorio todos los objetos multimedia y todos los tags
    $tags = $repository_tags->findall();

    //Buscamos coincidencia del Tag si se modifica el campo del filtro: <Tags>
    for ($i = 0;$i < count($tags);++$i) {
        if ($tags[$i]->getTitle() == $tag_found) {
            $tag_search = $tags[$i];
        }
    }

      $queryBuilder = $repository_multimediaObjects->createStandardQueryBuilder();

    //Obtenemos todos los objetos multimedia del repositorio que su titulo coincida con <$search_found>
    if ($search_found != '') {
        $queryBuilder->field('$text')->equals(array('$search' => $search_found));
    }

    //Obtenemos todos los objetos multimedia del repositorio que contengan <$tag_found>
    if ($tag_found != 'All' && $tag_found != '') {
        $queryBuilder->field('tags._id')->equals(new \MongoId($tag_search->getId()));
    }

    //Obtenemos todos los objetos multimedia del repositorio que contengan <$type_found>
    if ($type_found != 'All' && $type_found != '') {
        $queryBuilder->field('tracks.only_audio')->equals($type_found == 'Audio');
    }

    //Obtenemos todos los objetos multimedia del repositorio que contengan <$duration_found>
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

    //Obtenemos todos los objetos multimedia con fecha superior o igual a <$start_found>
    if ($start_found != 'All' && $start_found != '') {
        $start = \DateTime::createFromFormat('d/m/Y', $start_found);
        $queryBuilder->field('record_date')->gt($start);
    }

    //Obtenemos todos los objetos multimedia con fecha inferior o igual a <$end_found>
    if ($end_found != 'All' && $end_found != '') {
        $end = \DateTime::createFromFormat('d/m/Y', $end_found);
        $queryBuilder->field('record_date')->lt($end);
    }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'multimediaObject',
         'objects' => $pagerfanta,
         'tags' => $tags,
         'tag_found' => $tag_found,
         'type_found' => $type_found,
         'duration_found' => $duration_found, );
  }

    private function createPager($objects, $page)
    {
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($this->limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
