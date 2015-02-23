<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;


class SearchMultimediaObjectsController extends Controller
{
    /**
     * @Route("/searchmultimediaobjects")
     * @Template()
     */
    public function indexAction(Request $request)
    {
    	$tag_search = new Tag();

    	//Recogemos los campos de búsqueda de los filtros
    	$tag_found = $request->query->get('tags');
    	$type_found = $request->query->get('type');
    	$duration_found = $request->query->get('duration');
    	$day_found = $request->query->get('day');
    	$month_found = $request->query->get('month');
    	$year_found = $request->query->get('year');

    	//Información proporcionada al paginador
    	$limit = 6;
        $page =  $request->get("page", 1);

        //Accedemos al repositorio de los objetos multimedia y de los tags
    	$repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
    	dump($repository_multimediaObjects);
    	$repository_tags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
    	dump($repository_tags);

    	//Obtenemos del repositorio todos los objetos multimedia y todos los tags
		$multimediaObjects = $repository_multimediaObjects->createBuilder();
		dump($multimediaObjects);
		$tags = $repository_tags->findall();
		dump($tags);

		//Buscamos coincidencia del Tag si se modifica el campo del filtro: <Tags>
		for ($i=0;$i<count($tags);$i++){
			if($tags[$i]->getTitle() == $tag_found){
				$tag_search = $tags[$i];
			}
		}


		$queryBuilder = $repository_multimediaObjects->createStandardQueryBuilder();

		/*------------------Aplicamos los FILTROS y nos quedamos con los objetos multimedia deseados ----------------------*/

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$tag_found>
		if($tag_found != "All"){	
			$queryBuilder->field('tags._id')->equals(new \MongoId($tag_search->getId()));
			
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$type_found>
		if($type_found != "All"){
			$queryBuilder->field('tracks.only_audio')->equals($type_found == "Audio");
			//$queryBuilder->field('tracks.only_audio')->equals($type_found == "Video");
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$duration_found>
		if($duration_found != "All"){
			if($duration_found == "Up to 5 minutes"){
				$queryBuilder->field('tracks.duration')->lte(5);
			}
			if($duration_found == "Up to 10 minutes"){
				$queryBuilder->field('tracks.duration')->range(6, 10);;
			}
			if($duration_found == "Up to 30 minutes"){
				$queryBuilder->field('tracks.duration')->range(11, 30);;
			}
			if($duration_found == "Up to 60 minutes"){
				$queryBuilder->field('tracks.duration')->range(31, 60);;
			}
			if($duration_found == "More than 60 minutes"){
				$queryBuilder->field('tracks.duration')->gt(60);
			}
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$day_found>
		if($day_found != "All"){
			$multimediaObjects = $repository_multimediaObjects->CreateBuilder();
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$month_found>
		if($month_found != "All"){
		}

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$year_found>
		if($year_found != "All"){
		}

		//Creamos el paginador
		$adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('multimediaObjects' => $pagerfanta, 'tags' => $tags, 'tag_found' => $tag_found, 'type_found' => $type_found,
        	'duration_found' => $duration_found, 'day_found' => $day_found, 'month_found' => $month_found, 'year_found' => $year_found);
    }
}