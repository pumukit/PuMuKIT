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
    	$start_found = $request->query->get('start');
    	$end_found = $request->query->get('end');

    	//Información proporcionada al paginador
    	$limit = 6;
        $page =  $request->get("page", 1);

        //Accedemos al repositorio de los objetos multimedia y de los tags
    	$repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
    	$repository_tags = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');
    	//dump($repository_tags);

    	//Obtenemos del repositorio todos los objetos multimedia y todos los tags
		//$multimediaObjects = $repository_multimediaObjects->createBuilder();
		$tags = $repository_tags->findall();
		//dump($tags);

		//Buscamos coincidencia del Tag si se modifica el campo del filtro: <Tags>
		for ($i=0;$i<count($tags);$i++){
			if($tags[$i]->getTitle() == $tag_found){
				$tag_search = $tags[$i];
			}
		}


		$queryBuilder = $repository_multimediaObjects->createStandardQueryBuilder();

		/*------------------Aplicamos los FILTROS y nos quedamos con los objetos multimedia deseados ----------------------*/

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
				$queryBuilder->field('tracks.duration')->lt(5);
			}
			if($duration_found == "Up to 10 minutes"){
				$queryBuilder->field('tracks.duration')->range(5, 10);
			}
			if($duration_found == "Up to 30 minutes"){
				$queryBuilder->field('tracks.duration')->range(10, 30);
			}
			if($duration_found == "Up to 60 minutes"){
				$queryBuilder->field('tracks.duration')->range(30, 60);
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

		//Creamos el paginador
		$adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('multimediaObjects' => $pagerfanta, 'tags' => $tags, 'tag_found' => $tag_found, 'type_found' => $type_found,
        	'duration_found' => $duration_found, 'start_found' => $start_found, 'end_found' => $end_found);
    }
}