<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;


class SearchSeriesController extends Controller
{
    /**
     * @Route("/searchseries")
     * @Template()
     */
    public function indexAction(Request $request)
    {
    	//Recogemos los campos de búsqueda de los filtros
    	$start_found = $request->query->get('start');
    	$end_found = $request->query->get('end');

    	//Información proporcionada al paginador
    	$limit = 6;
        $page =  $request->get("page", 1);

        //Accedemos al repositorio de las series
    	$repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

    	//Obtenemos del repositorio todas las series
		//$series = $repository_series->createBuilder();

		$queryBuilder = $repository_series->createQueryBuilder();
		dump($queryBuilder);


		/*------------------Aplicamos los FILTROS y nos quedamos con las series deseadas ----------------------*/

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

		//Creamos el paginador
		$adapter = new DoctrineODMMongoDBAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        return array('series' => $pagerfanta, 'start_found' => $start_found, 'end_found' => $end_found);
    }
}