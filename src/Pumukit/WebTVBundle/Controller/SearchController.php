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
	private $limit = 10;

	/**
   	 * @Route("/searchseries")
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     */
  	public function seriesAction(Request $request)
  	{
  		$serie_search = new Series();

        $search_found = $request->query->get('search');
    	$start_found = $request->query->get('start');
    	$end_found = $request->query->get('end');

    	$repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

    	$series = $repository_series->findall();

    	foreach ($series as $serie) {
            if($serie->getTitle() == $search_found){
                $serie_search = $serie;
            }
        }

		$queryBuilder = $repository_series->createQueryBuilder();

		/*------------------Aplicamos los FILTROS y nos quedamos con las series deseadas ----------------------*/

        //Obtenemos todas las series del repositorio que su titulo coincida con <$search_found>
        if($search_found != ""){
            $queryBuilder->field('title.en')->equals($serie_search->getTitle());
        }

		//Obtenemos todos los objetos multimedia con fecha superior o igual a <$start_found>
		if($start_found != "All" && $start_found != ""){
			$start = \DateTime::createFromFormat("d/m/Y", $start_found);
			$queryBuilder->field('public_date')->gt($start);
		}

		//Obtenemos todos los objetos multimedia con fecha inferior o igual a <$end_found>
		if($end_found != "All" && $end_found != ""){
			$end = \DateTime::createFromFormat("d/m/Y", $end_found);
			$queryBuilder->field('public_date')->lt($end);
		}

		$pagerfanta = $this->createPager($queryBuilder, $request->query->get("page", 1));

		return array('type' => 'series',
                     'objects' => $pagerfanta);
  	}

	/**
   	 * @Route("/searchmultimediaobjects")
     * @Template("PumukitWebTVBundle:Search:index.html.twig")
     */
  	public function multimediaObjectsAction(Request $request)
  	{
  		$tag_search = new Tag();
    	$multimediaObject_search = new MultimediaObject();

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
		$multimediaObjects = $repository_multimediaObjects->findall();
		$tags = $repository_tags->findall();

		//Buscamos coincidencia del Tag si se modifica el campo del filtro: <Tags>
		for ($i=0;$i<count($tags);$i++){
			if($tags[$i]->getTitle() == $tag_found){
				$tag_search = $tags[$i];
			}
		}

		//Buscamos coincidencia del Objeto Multimedia si se modifica el campo del filtro: <Search>
		foreach ($multimediaObjects as $multimediaObject) {
			if($multimediaObject->getTitle() == $search_found){
				$multimediaObject_search = $multimediaObject;
			}
		}

		$queryBuilder = $repository_multimediaObjects->createStandardQueryBuilder();

		/*------------------Aplicamos los FILTROS y nos quedamos con los objetos multimedia deseados ----------------------*/

		//Obtenemos todos los objetos multimedia del repositorio que su titulo coincida con <$search_found>
		if($search_found != ""){
			$queryBuilder->field('title.en')->equals($multimediaObject_search->getTitle());
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$tag_found>
		if($tag_found != "All" && $tag_found != ""){	
			$queryBuilder->field('tags._id')->equals(new \MongoId($tag_search->getId()));
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$type_found>
		if($type_found != "All" && $type_found != ""){
			$queryBuilder->field('tracks.only_audio')->equals($type_found == "Audio");
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$duration_found>
		if($duration_found != "All" && $duration_found != ""){
			if($duration_found == "Up to 5 minutes"){
				$queryBuilder->field('tracks.duration')->lte(5);
			}
			if($duration_found == "Up to 10 minutes"){
				$queryBuilder->field('tracks.duration')->lte(10);
			}
			if($duration_found == "Up to 30 minutes"){
				$queryBuilder->field('tracks.duration')->lte(30);
			}
			if($duration_found == "Up to 60 minutes"){
				$queryBuilder->field('tracks.duration')->lte(60);
			}
			if($duration_found == "More than 60 minutes"){
				$queryBuilder->field('tracks.duration')->gt(60);
			}
		}

		//Obtenemos todos los objetos multimedia con fecha superior o igual a <$start_found>
		if($start_found != "All" && $start_found != ""){
			$start = \DateTime::createFromFormat("d/m/Y", $start_found);
			$queryBuilder->field('record_date')->gt($start);
		}

		//Obtenemos todos los objetos multimedia con fecha inferior o igual a <$end_found>
		if($end_found != "All" && $end_found != ""){
			$end = \DateTime::createFromFormat("d/m/Y", $end_found);
			$queryBuilder->field('record_date')->lt($end);
		}

		$pagerfanta = $this->createPager($queryBuilder, $request->query->get("page", 1));
  		
  		return array('type' => 'multimediaObject',
                     'objects' => $pagerfanta, 
                     'tags' => $tags, 
                     'tag_found' => $tag_found, 
                     'type_found' => $type_found,
                     'duration_found' => $duration_found);
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