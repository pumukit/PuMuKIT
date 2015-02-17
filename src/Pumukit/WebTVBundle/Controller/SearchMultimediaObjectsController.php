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
    	$limit = 2;
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

		//Obtenemos todos los objetos multimedia del repositorio que contengan <$tag_found>
		if($tag_found != "All"){	
			$multimediaObjects = $repository_multimediaObjects->createBuilderWithTag($tag_search, array('record_date' => 1));
		}

		//Creamos el paginador
		$adapter = new DoctrineODMMongoDBAdapter($multimediaObjects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('multimediaObjects' => $pagerfanta, 'tags' => $tags, 'tag_found' => $tag_found, 'type_found' => $type_found,
        	'duration_found' => $duration_found, 'day_found' => $day_found, 'month_found' => $month_found, 'year_found' => $year_found);
    }
}