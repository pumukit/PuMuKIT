<?php

namespace Pumukit\AdminBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Iterator\RecursiveItemIterator;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
  public function mainMenu(FactoryInterface $factory, array $options)
  {
    $menu = $factory->createItem('root');

    $menu->addChild('Dashboard');

    $menu->addChild('Series Multimedia');   
    
    $menu->addChild('Catalogador Unesco');

    $portal_design = $menu->addChild('Diseño Portal');
    $portal_design->addChild('Diseño');
    $portal_design->addChild('Plantillas');
    $portal_design->addChild('FileManager');
    $portal_design->addChild('Categorías');
    $portal_design->addChild('Noticias');

    $live = $menu->addChild('Directos');
    $live->addChild('Canales en Directo', array('route'=>'pumukitadmin_direct_index'));
    $live->addChild('Anuncios de Directos');

    $transcoding = $menu->addChild('Transcodificación');
    $transcoding->addChild('Perfil de Transcodificación');
    $transcoding->addChild('Lista de tareas');
    $transcoding->addChild('Trascodificadores');

    $menu->addChild('Editoriales Temporizadas');

    $tables = $menu->addChild('Tablas');
    $tables->addChild('Personas');

    $management = $menu->addChild('Administración');
    $management->addChild('Usuarios admin', array('route'=>'pumukitadmin_user_index'));
    $management->addChild('Categorías');
    $management->addChild('Géneros');
    $management->addChild('Tipos de materiales');
    $management->addChild('Tipos de series');
    $management->addChild('Idiomas');
    $management->addChild('Roles', array('route'=>'pumukitadmin_role_index'));
    $management->addChild('Perfiles Acceso');
    $management->addChild('Servidores de Distribución');

    $ingester = $menu->addChild('Ingestador');
    $ingester->addChild('Ingestador Matterhorn');

    return $menu;
  }
}