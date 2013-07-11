<?php

namespace Pumukit\ImportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pumukit\SchemaBundle\Entity\Series;
use Pumukit\SchemaBundle\Entity\SeriesType;
use Pumukit\SchemaBundle\Entity\Pic;
use Pumukit\SchemaBundle\Entity\MultimediaObject;
use Pumukit\SchemaBundle\Entity\Tag;
use Pumukit\SchemaBundle\Entity\Role;
use Pumukit\SchemaBundle\Entity\Person;
use Pumukit\SchemaBundle\Entity\PersonInMultimediaObject;
use Pumukit\SchemaBundle\Entity\Track;
use Pumukit\SchemaBundle\Entity\Material;

use Symfony\Component\DependencyInjection\SimpleXMLElement;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
 
/* 
    Naming conventions:
        Simplexml objects are named with one letter / short acronym.
        Entity properties use a descriptive variable name 
            (new naming scheme: "series...")
        Functions follow the old naming scheme ("...Serial...")

    Important: check utf-8 collation in mysql settings.
    http://stackoverflow.com/questions/3513773/change-mysql-default-character-set-to-utf8-in-my-cnf
*/



/**
 *
 * @author Ruben Gonzalez <rubenrua@teltek.es>
 * @author Andres Perez
 */
class ImportSeriesCommand extends ContainerAwareCommand
{

    private $em;    
    private $trepo;     // Translation repository
    private $srepo;     // Series repository
    private $strepo;    // SeriesType repository
    private $mmorepo;   // MultimediaObject repository
    private $picrepo;
    private $tagrepo;
    private $rolerepo;
    private $personrepo;
    private $trackrepo;
    private $materialrepo;

    // langs used in the exported xml file.
    private $langs = array("es","gl");
    private $output;
    private $logger; 

    private $tag_genres;
    private $tag_ground_types;
    private $tag_places;
    
    private $tag_pub_channels;
    private $tag_WebTV;
    private $tag_ARCA;
    private $tag_iTunesU;

    // Imports video (file / track) metadata by parsing the exported xml file by default.
    private $use_inspection_service = false;
    private $inspection;
    
    private $ids_series_array = array();
    private $ids_mms_array    = array();
    private $ids_tracks_array = array();
    private $ids_csv_path; // Where these arrays are to be saved as csv files.

    const DEFAULT_LANG   = "es"; // Used inside xml files, not Gedmo/translatable or config file.
    const UVIGOTV_DOMAIN = 'http://tv.uvigo.es'; // see function setUrlWithDomain()

    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setName('pumukit:import:series')
	  ->setDefinition(array(
			new InputArgument('xml-file-path', InputArgument::REQUIRED, 'Path to xml file.')
			))
	  ->setDescription('Import series information from UvigoTV PuMuKit.')
	  ->setHelp(<<<EOF
The <info>%command.name%</info> command imports the series information
(and all the multimedia_object related information) from previous
versions of Pumukit.

A custom 'import_log.log' will be created in /app/logs .
This log is configured in:
src/Pumukit/ImportBundle/Resources/config/services.xml .

Some .csv files will be created in $this->ids_csv_path with the old_id,new_id equivalence 
for series, multimedia objects and tracks.

	    <info>php %command.full_name% xml-file-path</info>
EOF
		    );
    }   

    /**
    * {@inheritdoc}
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em           = $this->getContainer()->get("doctrine.orm.entity_manager");
        $this->trepo        = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
        $this->srepo        = $this->em->getRepository('PumukitSchemaBundle:Series');
        $this->strepo       = $this->em->getRepository('PumukitSchemaBundle:SeriesType');
        $this->mmorepo      = $this->em->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->picrepo      = $this->em->getRepository('PumukitSchemaBundle:Pic');
        $this->tagrepo      = $this->em->getRepository('PumukitSchemaBundle:Tag');
        $this->rolerepo     = $this->em->getRepository('PumukitSchemaBundle:Role');
        $this->personrepo   = $this->em->getRepository('PumukitSchemaBundle:Person');
        $this->trackrepo    = $this->em->getRepository('PumukitSchemaBundle:Track');
        $this->materialrepo = $this->em->getRepository('PumukitSchemaBundle:Material');
        
        $this->logger       = $this->getContainer()->get('pumukit_import.logger');
        $this->output       = $output;
        $this->inspection   = $this->getContainer()->get("pumukit.inspection");

        $this->ids_csv_path = __DIR__."/../../../../app/logs/";
        // $this->deleteTables(); Not used now.
        
        $path = $input->getArgument("xml-file-path");  	
    	if (!file_exists($path)) {
    		throw new \Exception("File does not exist ".$path);
    	}
        $this->showAndLog("Warning: the prod database will be erased and re-created from $path","Warning");
        $this->initializeTags();
        $this->parseXMLFile($path);
        $this->em->flush();   
    }

    private function deleteTables()
    {
    // Remember: this uses the production environment database ("symfony")
        // $this->em->createQuery("DELETE PumukitSchemaBundle:Series s")->getResult();
        // $this->em->createQuery("DELETE PumukitSchemaBundle:SeriesType st")->getResult();
        // $this->em->createQuery("DELETE PumukitSchemaBundle:Pic pic")->getResult();

        // $this->em->createQuery("DELETE PumukitSchemaBundle:PersonInMultimediaObject pimo")->getResult();      
        // $this->em->createQuery("DELETE PumukitSchemaBundle:MultimediaObject mm")->getResult();
        // $this->em->createQuery("DELETE Gedmo\Translatable\Entity\Translation et WHERE et.objectClass != 'Pumukit\SchemaBundle\Entity\Tag'")->getResult();
    }

    /**
     * parseXMLFile: parses the information from one series (generated with
     *                  export_uvigo.php script, 1 series per xml file.
     *            Creates all the database objects related to the series.
     *
     * @param string $file complete path (from the command input).
     */
    private function parseXMLFile($file)
    {
        $this->showAndLog ("\nImporting " . $file . "\n", "Info");
        $s = simplexml_load_file($file); // Throws ErrorException if not a valid XML.

        $series      = $this->parseSerial( $s );
        $series_type = $this->parseSerialType( $s->serialType );
        $this->em->flush(); 
        $this->ids_series_array = $this->updateId( $s->id, $series, $this->ids_series_array);

        $series->setSeriesType( $series_type ); 
        $this->showDeprecated($s, array("serialItuness"));    
        
        $this->parsePics( $s->pics, $series );
        $this->parseMmTemplates( $s->mmTemplates, $series );
        $this->parseMms( $s->mms, $series );
       
    // TO DO: find bugs, unused xml nodes, etc.

    // TO DO ¡IMPORTANTE! broadcast - NEW ENTITIES OR TAGS REQUIRED.
        $this->logUpdatedIds();
        $this->showAndLog("\nAll done!","Info");
    }

    private function parseSerial( \SimpleXMLElement $s )
    {
        $series = new Series();
        $this->showAndLog("Pumukit version: ".$s->version);
        
        $this->showAsciiArt("series");
        $this->showAndLog("Original serial Id: \t".$s->id."\t");
        $this->showAndLog("------------------------------------------------------------", "Comment");

        $translate_array = array(                       "title", 
                                                        "subtitle", 
                                                        "keyword", 
                                                        "description",
                                                        "header", 
                                                        "footer", 
                                                        "line2");

        $this->translateProperties( $s, $series, $translate_array);

        $this->showDeprecated( $s,                array("announce", 
                                                        "mail"));

        $this->setProperties( $s, $series, array("copyright") );
        $this->setDates( $s, $series,      array("publicDate") );
        $this->showDeprecated( $s,         array("serialTemplate") );
        $series = $this->findOrPersist($series);

        return $series;
    }

    private function parseSerialType( \SimpleXMLElement $st )
    {
        $this->output->writeln("<comment>------------------------------------------------------------</comment>\n");
        $this->output->writeln("\t\t<info>Parsing SeriesType</info>\n");
        $series_type_temp = new SeriesType();

        $this->showAndLog("SerialType Id:\t" . $st->attributes()->id);
        
        $this->setProperties( $st, $series_type_temp,   array("cod"));
        $this->showDeprecated( $st,                     array("defaultsel"));

        $translate_array =                              array("name",
                                                              "description");
        $this->translateProperties( $st, $series_type_temp, $translate_array, false );

        $series_type = $this->findOrPersist($series_type_temp);
        if ($series_type === $series_type_temp) {
            // doctrine translations iclude persist and can lead to problems,
            // so they are postponed until a new object has to be persisted by all means.
            $this->translateProperties( $st, $series_type, $translate_array);
        }

        return $series_type;
    }
    
    /**
     * parsePics parses pics from serial or mm_object,
     *           XML 'pics' or 'mmPics' nodes respectively.
     *
     * @param \SimpleXMLElement $sp 
     * @param $entity the Series or MultimediaObject which these pics are assigned to.
     */
    private function parsePics( \SimpleXMLElement $sp, $entity)
    {
        // $class_name = 'MultimediaObject' or 'Series'
        $set_entity = "set" . $this->entityClassName($entity);
        $this->output->writeln("<comment>------------------------------------------------------------</comment>");      
        $this->showAndLog("\n\t\tParsing Pics\n","Info");
        foreach ($sp->children() as $p){
            $pic = new Pic();
            $pic->$set_entity( $entity ); // setSeries or setMultimediaObject
            $this->output->writeln("\nOriginal pic id:\t". $p->attributes()->id . "\t" .
                "<comment>Please note: the old_id - new_id relation is not saved</comment>\n");

            $this->setProperties( $p->attributes(), $pic,  array("rank") );

            $image_url = $this->setUrlWithDomain( $p, $pic );
            $this->setImageProperties( $image_url, $pic );
            $pic = $this->findOrPersist( $pic );            
        }
    }

    private function parseMmTemplates( \SimpleXMLElement $mmt, Series $series ){
        $this->showAndLog("\n------------------------------------------------------------","Comment");
        $this->showAndLog("\n\t\tParsing mmTemplates\n", "Info");      
        foreach ($mmt->children() as $m){            
            $mm_template = $this->parseMmObject( $m, $series, 
                multimediaObject::STATUS_PROTOTYPE);

            $this->parsePlaceAndPrecinct( $m->place, $mm_template);
            $this->parseGenre( $m->genre, $mm_template);

// TO DO ¡IMPORTANTE! broadcast - NEW ENTITIES OR TAGS REQUIRED.
         
            $this->parseMmTemplatePersons($m->mmTemplatePersons, $mm_template);
            $this->parseMmTemplateGrounds($m->mmTemplateGrounds, $mm_template);
        }
    }
    
    
    private function initializeTags()
    {
    // Metatag: the main categories (places, ground types, genres, publication channels)
    //          ground type subcategories (Unesco, Directriz, YouTube, iTunesxxx)
        $metatag_found = $this->tagrepo->findBy ( array (
            'metatag' => true) );

        foreach( $metatag_found as $mtf ){
            switch( $mtf->getTitle() ){
                case "places":
                    $this->tag_places = $mtf;
                    break;

                case "genres":
                    $this->tag_genres = $mtf;
                    break;

                case "ground types":
                    $this->tag_ground_types = $mtf;
                    break;

                case "publication channels":
                    $this->tag_pub_channels = $mtf;
                    break;
            }
        }

        $pub_channels = $this->tagrepo->children( $this->tag_pub_channels );
        foreach( $pub_channels as $pc ){
            switch ( $pc->getTitle() ){
                case "WebTV":
                    $this->tag_WebTV = $pc;
                    break;

                case "ARCA":
                    $this->tag_ARCA = $pc;
                    break;

                case "iTunesU":
                    $this->tag_iTunesU = $pc;
                    break;

                default:
                    $this->showAndLog("Unknown metatag: " . $mtf->getTitle(), "Error");
                    throw new \Exception("Unknown metatag");
            }
        }
    }

    private function parsePlaceAndPrecinct( \SimpleXMLElement $p, MultimediaObject $mm_object )
    {
        $parent      = $this->tag_places;
        $title       = trim($p->name->{self::DEFAULT_LANG});
        $description = trim($p->address->{self::DEFAULT_LANG});
        $cod         = trim($p->cod);

        $this->output->writeln("\n<info>Parsing place with id: " . $p['id'] .
        " and title: " . $title . "</info>");
       
        $place = $this->findOrCreateTag( $parent, $title, $description, $cod ); 
        $mm_object->addTag( $place );
        $this->em->persist( $mm_object );

        // Parse precinct:
        $parent      = $place;
        $title       = trim($p->precinct->name->{self::DEFAULT_LANG});
        $description = trim($p->precinct->equipment->{self::DEFAULT_LANG});
        $cod         = ""; // Precincts have blank cod instead of null.

        $this->output->writeln("\n<info>Parsing precinct with id: " . 
            $p->precinct['id'] . " and title: " . $title . "</info>");
       
        $precinct = $this->findOrCreateTag( $parent, $title, $description, $cod ); 
        $mm_object->addTag( $precinct );
        $this->em->persist( $mm_object );        
    }

    private function parseGenre( \SimpleXMLElement $g, MultimediaObject $mm_object )
    {
        $parent      = $this->tag_genres;
        $title       = $g->name->{self::DEFAULT_LANG};
        $description = null;
        $cod         = $g->cod;

        $this->output->writeln("\n<info>Parsing genre with id: " . $g['id'] .
        " and title: " . $title . "</info>");
       
        $genre = $this->findOrCreateTag( $parent, $title, $description, $cod ); 
        $mm_object->addTag( $genre );
        $this->em->persist( $mm_object );
    }
   
    private function findOrCreateTag( Tag $parent, $title, $description, $cod )
    {
        $parent_id = $parent->getId();
        $tag_found = $this->tagrepo->findBy ( array (
                'parent'      => $parent_id,
                'title'       => $title,
                'description' => $description,
                'cod'         => $cod) );
        
        if ( 0 == count($tag_found) ){

            $this->showAndLog("Persisting new tag - title: " . $title, "Info");
        // I assume that no new tags will be created. They should be imported previously
        // from export_structure script.
        //    throw new \Exception ("Tag not found - a new tag had to be created");
            $this->showAndLog("Warning - creating a new tag for this precinct","Warning");
            $new_tag = new Tag();
            $new_tag->setTitle( $title );
            $new_tag->setDescription( $description );
            $new_tag->setCod( $cod );
            $new_tag->setParent( $parent );
            $this->em->persist( $new_tag );

            return $new_tag;

        } else if ( 1 == count($tag_found) ){
            $this->output->writeln("<comment>Retrieving existing tag from the DB ".
                $description."</comment>");                
            
            return $tag_found[0];

        } else {
            $this->showAndLog('Error / Warning - There are more than one tag with '.
                'title = "' . $title . '"', "Error");
            return $tag_found[0];

        /* In the original tag structure imported from uvigotv, some places have
         duplicated precincts. It will log an error and simply assign the first tag. 
         Examples: precincts 223 and 59 ("Paraninfo" assigned to "Edificio Rectorado")
             blank precincts 199 and 291 assigned to "Confederación de Empresarios de Galicia"
        */

            //throw new \Exception('There were more than one tag with title: ' . $title );
        }     
    }

    private function findTag( $title, $cod )
    {
        $tag_found = $this->tagrepo->findBy ( array (
                'title'       => $title,
                'cod'         => $cod) );
        
        if ( 0 == count($tag_found) ){
            throw new \Exception ("Tag not found - a new tag had to be created");
        
        } else if ( 1 == count($tag_found) ){
            $this->showAndLog("Retrieving existing tag from the DB", "Comment");                
            
            return $tag_found[0];

        } else {
            throw new \Exception('There were more than one tag with title: '. $title );
        }
    }

    private function parseMmTemplatePersons( \SimpleXMLElement $mmtp, 
        MultimediaObject $mm_object )
    {
        $this->output->writeln("\n<info>Parsing mmTemplatePersons' roles</info>");
        foreach ($mmtp->children() as $r){
            $this->parseRole( $r, $mm_object );       
        }
    }
    
    // Exactly the same than the previous, but created a different function
    // for readability and troubleshooting purposes.
    private function parseMmPersons( \SimpleXMLElement $mmp, 
        MultimediaObject $mm_object )
    {
        $this->output->writeln("\n<info>Parsing mmPersons' roles</info>");
        foreach ($mmp->children() as $r){
            $this->parseRole( $r, $mm_object );       
        }
    }

    private function parseRole( \SimpleXMLElement $r, MultimediaObject $mm_object )
    {
        
        if ($r->persons->person == null) {
            // The old export script wrote all roles regardless of whether 
            // they were used or not (roles without any person are not used)
            $this->showAndLog("Warning: there is a role without person associated" .
                " - old export script?", "Warning");
            return;
        }
        $this->output->writeln("\n");
        $this->showAndLog("\tParsing role rank:" . $r['rank'] . " id:" . $r['id'] .
            " cod:" . $r->cod, "");
        $role = new Role();
        $this->setProperties( $r, $role,       array("cod",
                                                     "xml",
                                                     "display") );
        $this->setPropertyAPelo( $r['rank'], $role,  "rank" );
        $this->translateProperties( $r, $role, array("name",
                                                     "text") );

        $role = $this->findOrPersist( $role );

        foreach ($r->persons->children() as $p){
            $this->parsePerson( $p, $role, $mm_object);
        }
    } 
    
    /**
     * parsePerson sets the information of a given person,
     *      receives a role and mm_object previously persisted and
     *      creates a new PersonInMultimediaObject assigned to these objects.
     *
     * @param SimpleXMLElement $p
     * @param Role $role
     * @param MultimediaObject $mm_object
     */
    private function parsePerson( \SimpleXMLElement $p, Role $role,
        MultimediaObject $mm_object)
    {
        // PDO complains if this flush is not inserted.
        $this->em->flush(); // magic flush

        $this->showAndLog("\t\tParsing person id:" . $p['id'] . " name:" . $p->name, "");
        $person_temp = new Person();
        $this->setProperties( $p, $person_temp,       array("name",
                                                       "email",
                                                       "web", 
                                                       "phone" ));
        
        $translate_array =                       array("honorific",
                                                       "firm",
                                                       "post",
                                                       "bio");

        $this->translateProperties( $p, $person_temp, $translate_array, false );
        $person = $this->findOrPersist( $person_temp );
        if ($person === $person_temp) {
            // doctrine translations iclude persist and can lead to problems,
            // so they are postponed until a new object has to be persisted by all means.
            $this->translateProperties( $p, $person_temp, $translate_array);
        }

        $this->em->flush(); // pimo needs its components' ids to be set.    
        
        $pimo = new PersonInMultimediaObject();
        $pimo->setMultimediaObject( $mm_object );
        $pimo->setRole( $role );
        $pimo->setPerson( $person );   
                
        $mm_object->addPersonInMultimediaObject($pimo); 

        $this->em->flush();
    }

    private function parseMmTemplateGrounds( \SimpleXMLElement $mmtg, 
        MultimediaObject $mm_object)
    {
        $this->output->writeln("\n<info>Parsing mmTemplateGrounds</info>");
        foreach ($mmtg->children() as $g){
            $this->parseGround( $g, $mm_object );       
        }
    }

    // Exactly the same than the previous, but created a different function
    // for readability and troubleshooting purposes.
    private function parseMmGrounds( \SimpleXMLElement $mmg, 
        MultimediaObject $mm_object)
    {
        $this->output->writeln("\n\t\t<info>Parsing mmGrounds</info>");
        foreach ($mmg->children() as $g){
            $this->parseGround( $g, $mm_object );       
        }
    }

    private function parseGround ( \SimpleXMLElement $g, MultimediaObject $mm_object )
    {
        // groundTypes are not needed in the new tag structure.
        $title       = $g->name->{self::DEFAULT_LANG};
        $cod         = $g->cod;

        $this->output->writeln("\n<info>Parsing Ground with id: " . $g['id'] .
        " title: " . $title . " cod: " . $cod . "</info>");
       
        $ground = $this->findTag( $title, $cod );
        $mm_object->addTag( $ground );
        $this->em->persist( $mm_object );
    }

    /**
     * parseMmObject Sets the common properties of a given mmobject 
     *      Assigns the mmobject to the series.
     *      It does NOT process children nodes with data belonging to
     *      other (pmk 2) entities or tags such as places, persons, etc.
     *
     * @param SimpleXMLElement $mmo
     * @param Series $series
     * @param $status - Used only to know if it is a PROTOTYPE (old MmTemplate)
     *
     */
    private function parseMmObject( \SimpleXMLElement $mmo, Series $series, 
        $status = null ){

            $mm_temp = new MultimediaObject();
            $mm_temp->setSeries($series);
            $this->showAsciiArt("multimediaobject");
            $this->output->writeln("\n<comment>------------------------------------------------------------</comment>\n");

            $this->showAndLog("\t\tParsing mmObject with rank: " .
                $mmo['rank'] . " id: " . $mmo['id'] , "Info");
        
            if ($mm_temp->getSeries() == $series) {
                $this->showAndLog("MultimediaObject assigned to this Series" .
                " with Series.ID = " . $series->getId(), "Info");
            } else {
                $this->showAndLog("\tError - cannot assign to this Series" , "Error");
                throw new \Exception("Cannot assign multimedia object to series");
            }

            $this->setMmObjectStatusAndPubChannel( $mmo, $mm_temp, $status );

            $this->output->writeln("\nOriginal MultimediaObject id:\t" . 
                $mmo->attributes()->id );            

            $this->setPropertyAPelo( $mmo['rank'], $mm_temp,  "rank" );

            $this->showDeprecated( $mmo,                  array("subserial", 
                                                                "announce", 
                                                                "mail"));

            $this->setProperties($mmo, $mm_temp,        array("copyright"));
            $this->setDates($mmo, $mm_temp,             array("recordDate", 
                                                                "publicDate"));          

            $translate_array =                            array("title", 
                                                                "subtitle",
                                                                "keyword", 
                                                                "description",
                                                                "line2");

            $this->translateProperties( $mmo, $mm_temp, $translate_array, false);

            $this->showDeprecated( $mmo,                  array("subserialTitle"));

            $mm_object = $this->findOrPersist( $mm_temp);
            
            if ($mm_object === $mm_temp) {
                // doctrine translations iclude persist and can lead to problems,
                // so they are postponed until a new object has to be persisted by all means.
                $this->translateProperties( $mmo, $mm_temp, $translate_array);
            }

            $this->em->flush();
            $this->ids_mms_array = $this->updateId( $mmo['id'], $mm_object, $this->ids_mms_array);

            return $mm_object;      
    }

/* The old mm.statusid = xxx_TRANC , XXX_BLOCK , XXX_TRASH are <0 
   but they are assimilated to the old status_id = 0 working/bloq (blocked).
   Review: /lib/model/Mm.php and MmPeer.php in pumukit 1.
           /batch/import/import.php in Pumukit 1.7.

   The new mm.status (static properties as before) in Pumukit 2 use
   the same values as Pumukit 1.7.
   
  old             new (Pumukit 1.7 and 2)       new tags
  mm.status_id    mm.status_id / mm.status      (publication channels)

  <0 (misc)       1 Bloq                        (nothing)
   0 Working      1 Bloq                        (nothing)
   1 Hide         2 Hide *                      (nothing)
   2 Mediateca    0 Normal                      WebTv
   3 ARCA         0 Normal                      WebTv + ARCA
   4 iTunesU      0 Normal                      WebTv + ARCA + iTunesU
  */
    private function setMmObjectStatusAndPubChannel( \SimpleXMLElement $mmo, 
        MultimediaObject $mm_object, $statusMmTemplate = null)
    {
        // Set this multimedia object as template 
        // if multimediaObject::STATUS_PROTOTYPE) is passed
        if ( null != $statusMmTemplate ){
            $this->setPropertyAPelo( $statusMmTemplate, $mm_object, "Status" );
            $this->showAndLog("MmTemplate: Multimedia object STATUS set to PROTOTYPE", "Info");    
            $this->showDeprecated( $mmo,  array("statusId") );
            
            return;
        } 

        $this->output->writeln("<info>Adding publication channel tags for the statusId: " . 
            $mmo->statusId . "</info>");
        
        $status = intval( $mmo->statusId ) < 0 ? 0 : intval( $mmo->statusId );
        switch ( $status ){
            case 4:
                $mm_object->addTag( $this->tag_iTunesU );
                $this->showAndLog("Assigned to iTunesU Publication");
                // Continues and adds next tags

            case 3:
                $mm_object->addTag( $this->tag_ARCA );
                $this->showAndLog("Assigned to ARCA Publication");
                // Continues and adds next tag

            case 2:
                $mm_object->addTag( $this->tag_WebTV );
                $this->showAndLog("Assigned to WebTv Publication");
                
                $this->setPropertyAPelo( MultimediaObject::STATUS_NORMAL, 
                    $mm_object, "Status" );
                break;
            
            case 1:
                $this->setPropertyAPelo( MultimediaObject::STATUS_HIDE, 
                    $mm_object, "Status" );
                break;

            case 0:
                $this->setPropertyAPelo( MultimediaObject::STATUS_BLOQ, 
                    $mm_object, "Status" );
                break;

            default:
                $this->showAndLog("Something is broken in mm.status_id", "Error");
                throw new \Exception("Something is broken in mm.status_id");
        }
    }

    private function parseMms( \SimpleXMLElement $mms, Series $series)
    {
        $this->output->writeln("<comment>------------------------------------------------------------</comment>\n");
        $this->output->writeln("\t\t<info>Parsing mmObjects</info>\n");
        foreach ( $mms->children() as $m ){
            $mm_object = $this->parseMmObject( $m, $series);

// TO DO ¡IMPORTANTE! broadcast - NEW ENTITIES OR TAGS REQUIRED.

            $this->parseGenre( $m->genre, $mm_object);
            $this->parsePlaceAndPrecinct( $m->place, $mm_object);
            $this->parseMmGrounds( $m->mmGrounds, $mm_object);
            $this->parseMmPersons( $m->mmPersons, $mm_object);
            $this->parsePics( $m->mmPics, $mm_object);
            $this->parseFiles( $m->files, $mm_object);
            $this->parseMaterialsAndLinks( $m, $mm_object);         
        }
    }

    private function parseFiles ( \SimpleXMLElement $fs, MultimediaObject $mm_object )
    {
        $this->output->writeln("<comment>------------------------------------------------------------</comment>\n");
        $this->output->writeln("\t\t<info>Parsing Files</info>\n");
        foreach ( $fs->children() as $f ){
            $this->em->flush(); // another magic flush         
            $track_temp = new Track();
            $this->output->writeln("\nOriginal file rank: " . $f['rank'] .
                "\tid:\t" . $f['id'] );
            
            $this->setPropertyAPelo( $f->file, $track_temp,            "path");
            $translate_array =                                  array( "description");
            $this->translateProperties( $f, $track_temp, $translate_array, false );

            // Note: entities that inherit from "element" (track, pic, material)
            // use their own (simple string) tags. Not related to Tag entity.
            $track_temp->addTag( (string) $f->perfil->name );       // "perfil"
            $this->setUrlWithDomain( $f, $track_temp );             // "url"
            $this->setPropertyAPelo( $f['rank'], $track_temp,          "rank" );

            if ($this->use_inspection_service) {
                // Note that local paths are needed, won't work with remote urls. 
                $this->inspection->autocompleteTrack($track_temp);
            } else {
                // Imports metadata from xml by default.
                $this->parseFileMetadata ($f, $track_temp);
            }
            
            // display - hidden status also adds a tag.
            if ('true' == $f->display) {
                $this->setPropertyAPelo ( false, $track_temp,          "hide");
            } else {
                $this->setPropertyAPelo ( true, $track_temp,           "hide");
                $track_temp->addTag( "hidden" );
            }

            $this->setPropertyAPelo ( $f->language->cod, $track_temp,  "language");
            $this->showDeprecated( $f,                     array( "tickets") );
            $track = $this->findOrPersist( $track_temp, $mm_object );

            if ($track === $track_temp) {
                // doctrine translations iclude persist and can lead to problems,
                // so they are postponed until a new object has to be persisted by all means.
                $this->translateProperties( $f, $track, $translate_array);
            }

            $this->em->flush(); //debug
            // $track->setMultimediaObject( $mm_object );
            // It is better to use MultimediaObject->addTrack as it handles
            // duration and rank by its own.
         
// Review and remove rank assignment if needed.
            
            $mm_object->addTrack($track);

            // $this->em->persist($mm_object); //test
            $this->em->flush();
            $this->ids_tracks_array = $this->updateId( $f['id'], $track, $this->ids_tracks_array);
        }        
    }

    /**
     * setFileMetadata imports metadata information from the xml file.
     *              It is used by default instead of Inspection service.
     */
    private function parseFileMetadata( \SimpleXMLElement $f, Track $material)
    {
        // serial0167 is useful to test this.
        $this->setPropertyAPelo( $f->format->name, $material,    "format" );
        if ("1" == $f->audio){
            $this->setPropertyAPelo( $f->codec->name, $material, "acodec" );
            $this->setPropertyAPelo ( true , $material,          "OnlyAudio");
        } else {
            $this->setPropertyAPelo( $f->codec->name, $material, "vcodec" );
            $this->setPropertyAPelo ( false , $material,         "OnlyAudio");
        }
               
        $this->setPropertyAPelo( $f->mimetype->type, $material,  "mimetype" );
        $this->setPropertyAPelo( $f->resolution->hor, $material, "width" );
        $this->setPropertyAPelo( $f->resolution->ver, $material, "height" );

        $this->setProperties( $f, $material,              array( "bitrate",
                                                                 "framerate",
                                                                 "channels",
                                                                 "duration",
                                                                 "size") ); 
    }

    private function parseMaterialsAndLinks( \SimpleXMLElement $mm, MultimediaObject $mm_object)
    {

        $this->output->writeln("<comment>------------------------------------------------------------</comment>\n");
        $this->output->writeln("\t\t<info>Parsing Materials</info>\n");
        $last_materials_rank = 0;

        foreach( $mm->materials->children() as $m ){
            $material = new Material();
            $this->output->writeln("\nOriginal material rank: " . $m['rank'] .
                "\tid:\t" . $m['id'] );
            $last_materials_rank = $m['rank'];
            $this->setPropertyAPelo( $m['rank'], $material,        "rank" );
            $this->translatePropertyAPelo( $m->name, $material,    "description");
            $url = $this->setUrlWithDomain( $m, $material );    // "url"
            // display - hidden status also adds a tag.
            if ('true' == $m->display) {
                $this->setPropertyAPelo ( false, $material,        "hide");
            } else {
                $this->setPropertyAPelo ( true, $material,         "hide");
                $material->addTag( "hidden" );
            }
            $this->setPropertyAPelo( $m->mattype->type, $material, "format"); // not really useful. 
            $this->setProperties( $m->mattype, $material, array(   "mimetype"));

            $size = $this->retrieveRemoteFileSize($url);
            $this->setPropertyAPelo( $size, $material,             "size");

            $material = $this->findOrPersist( $material );
            $material->setMultimediaObject ($mm_object);
            $this->em->flush();
        }
        
        // Parse and add links as materials.
        foreach( $mm->links->children() as $l ){
            $link = new Material();
            $rank = $l['rank'] + $last_materials_rank;
            $this->output->writeln("\nOriginal link rank: " . $l['rank'] .
                "\t new material id: " . $rank . "\tid:\t" . $l['id'] );

            $this->setPropertyAPelo( $rank, $link,          "rank" );
            $url = $this->setUrlWithDomain( $l, $link ); // "url"
            $this->translatePropertyAPelo( $l->name, $link, "description");
            $this->setPropertyAPelo( "link", $link,         "format"); 
            $this->setPropertyAPelo( 0, $link,              "size");
            $link->addTag( "link" );
            $link = $this->findOrPersist( $link );
            $link->setMultimediaObject ($mm_object);

            $this->em->flush();
        }
    }

    /**
     * setProperties: 
     *  Traverses a parent xml node. 
     *      Sets the entities' properties given inside an array. 
     *      Tests them and shows output status.
     * It assumes that xml children and entities' properties have the same names.
     *
     * @param SimpleXMLElement $xml node with information of one entity
     * @param $entity
     * @param Array $properties
     */
    private function setProperties( \SimpleXMLElement $xml, $entity, $properties)
    {
        foreach ($properties as $p){
            
            if (isset($xml->$p)) { 
                
                $setProperty = "set".ucfirst($p);
                $getProperty = "get".ucfirst($p);
                $entity->$setProperty( trim($xml->$p));                         
                if ($entity->$getProperty() == trim($xml->$p)) {
                    $this->showAndLog("$p:\t".$xml->$p, "Property", "\tsaved");
                } else {
                    echo "$p:\t".$xml->$p;
                    $this->output->writeln("\t<error>Error - cannot set $p property<error>");
                }
            } else {
                $this->output->writeln("<error>Error - cannot find $p property<error>");
            }           
        }
    }

    /**
     * setDates: setProperties adaptation to work with dates (DateTime types).
     */
    private function setDates( \SimpleXMLElement $xml, $entity, $properties)
    {
        foreach ($properties as $p){
            
            if (isset($xml->{$p})) { 
                $setProperty = "set".ucfirst($p);
                $getProperty = "get".ucfirst($p);             
                $date_time = new \DateTime(trim($xml->$p));
                $entity->$setProperty($date_time);           

                if (trim($xml->$p) == $entity->$getProperty()->format('Y-m-d H:i:s')) {
                    $this->ShowAndLog("$p:\t" . $xml->$p, "Property", "\tsaved");
                } else {
                    echo "$p:\t".$xml->$p;
                    $this->showAndLog("\tError - cannot set $p property (dates)", "Error");
                }
            } else {
                $this->showAndLog("Error - cannot find $p property (dates)", "Error");
            }           
        }
    }

    /**
     * setUrlWithDomain: checks if the xml node->url is a complete url, 
     * sets the pic of file's url property, tests it and returns the full url.
     *
     * @return String $full_url
     */
    private function setUrlWithDomain( \SimpleXMLElement $xml, $entity)
    {
        if ( isset( $xml->url ) ) { 
            if ( "" == trim($xml->url) ) {
                $this->showAndLog("Blank url", "Debug", "Not saved");

                return;
            }
            if (strpos($xml->url, '://') === false) {
                $full_url = self::UVIGOTV_DOMAIN . trim($xml->url);
            } else {
                $full_url = trim($xml->url);
            }
            $this->showAndLog("url:\t" . $xml->url, "Url");
           
            
            $entity->setUrl($full_url);           
            
            if ($full_url == $entity->getUrl()) {
                $this->showAndLog("complete url:\t" . $full_url, "Property", "saved");
            } else {
                echo "complete url:\t".$full_url;
                $this->showAndLog("\tError - cannot set url property " . "$full_url", "Error");
            }

            return $full_url;

        } else {
            $this->showAndLog("Error - cannot find url property", "Error");
        }           
    }
    
    /** 
     * setImageProperties retrieves image information remotely and sets its properties.
     * It relies on external connections so it can be time-consuming. 
     *
     * @param $image_url
     * @param Pic $pic
     */
    private function setImageProperties ($image_url, Pic $pic)
    {
        $info = @getimagesize($image_url);     
        // getimagesize throws errors and warnings that trigger sf exceptions 
        // if it cannot load the image or the file is not a proper image.
        // An image with "zero" properties is initialized instead.
        if (!$info){
            $info = array( 0, // Width
                           0, // Height
                           0, // Imagetype - IMAGETYPE_UNKNOWN = 0
                           'width="0" height="0"',
                           'bits'     => 0,
                           'channels' => 0,
                           "mime"     => 'application/octet-stream'); // rfc2046 4.2
            $this->showAndLog("Error: the image " . $image_url . " can't be loaded", "Error");
        }

        $size = $this->retrieveRemoteFileSize($image_url);
             
        $pic->setWidth($info[0]);
        if ($pic->getWidth() == $info[0]) {
            $this->showAndLog("width:\t" . $info[0], "Property","\tsaved");
        } else {
            echo "width:\t" . $info[0]; 
            $this->showAndLog("\tError - cannot set width", "Error");
        }

        $pic->setHeight($info[1]);
        if ($pic->getHeight() == $info[1]) {
            $this->showAndLog("height:\t" . $info[1], "Property","\tsaved");
        } else {
            $this->showAndLog("\tError - cannot set height", "Error");
        }

        // http://www.php.net/manual/en/function.exif-imagetype.php#refsect1-function.exif-imagetype-constants
        $image_type = array(    0   => "UNKNOWN",
                                1   => "GIF",
                                2   => "JPEG",
                                3   => "PNG",
                                4   => "SWF",
                                5   => "PSD",
                                6   => "BMP",
                                7   => "TIFF_II",
                                8   => "TIFF_MM",
                                9   => "JPC",
                                10  => "JP2",
                                11  => "JPX",
                                12  => "JB2",
                                13  => "SWC",
                                14  => "IFF",
                                15  => "WBMP",
                                16  => "XBM",
                                17  => "ICO" );
        $pic->setFormat( $image_type[ $info[2] ]);
        if ($pic->getFormat() == $image_type[ $info[2] ] ) {
            $this->showAndLog("format:\t" . $image_type[ $info[2] ], "Property","\tsaved");
        } else {
            $this->showAndLog("\tError - cannot set format", "Error");
        }

        $pic->setMimeType($info["mime"]);
        if ($pic->getMimeType() == $info["mime"]) {
            $this->showAndLog("mime_type:\t" . $info["mime"], "Property","\tsaved");
        } else {
            $this->showAndLog("\tError - cannot set mime_type", "Error");
        }

        $pic->setSize($size);
        if ($pic->getSize() == $size) {
            $this->showAndLog("size:\t" . $size . " bytes", "Property","\tsaved");
        } else {
            $this->showAndLog("\tError - cannot set format", "Error");
        }
    }

    private function setPropertyAPelo( $value, $entity, $property)
    {
       
        $setProperty = "set".ucfirst($property);
        $getProperty = "get".ucfirst($property);
        $entity->$setProperty($value);                         
        if ($entity->$getProperty() == $value) {
            $this->showAndLog("$property:\t" . $value, "Property","\tsaved");
        } else {
            $this->output->writeln("\t<error>Error - cannot set $property property<error>");
        }
        
    }

    /**
     * translateProperties: setProperties adaptation to work with translations.
     */
    private function translateProperties( \SimpleXMLElement $xml, $entity, array $properties, 
        $all_locales = true )
    {
        foreach ($properties as $p){
            foreach ($this->langs as $l){    
                if (isset($xml->$p->$l)) { 
                    $setProperty = "set".ucfirst($p);
                    $getProperty = "get".ucfirst($p);
                    if ($l == self::DEFAULT_LANG){                      
                        $entity->$setProperty( trim($xml->$p->$l));
                        if ($entity->$getProperty() == trim($xml->$p->$l)){
                            $this->showAndLog("$p - $l:\t" . $xml->$p->$l, "Property",
                             "\tdefault locale saved" );
                        } else {
                            echo "$p - $l:\t".$xml->$p->$l;
                            $this->output->writeln("<error>Error - cannot set default $l locale <error>");
                        }
                        
                    } else if ($all_locales) {
                        $this->trepo->translate($entity, $p, $l, $xml->$p->$l);
                        $this->showAndLog("$p - $l:\t" . $xml->$p->$l, "Property",
                             "\ttranslation saved but not checked" );
                    }
                    
                } else {
                    $this->output->writeln("<error>Error - cannot find $l locale <error>");
                }
            }
        }
    }

    /**
     * translatePropertyAPelo: sets translations when the xml node 
     * and the entity's property don't have the same name.
     */
    private function translatePropertyAPelo( \SimpleXMLElement $xml, $entity, $property)
    {
        foreach ($this->langs as $l){    
            if (isset($xml->$l)) { 
                $setProperty = "set".ucfirst($property);
                $getProperty = "get".ucfirst($property);
                if ($l == self::DEFAULT_LANG){                      
                    $entity->$setProperty( trim($xml->$l));
                    if ($entity->$getProperty() == trim($xml->$l)){
                        $this->ShowAndLog("$property - $l:\t" . $xml->$l, "Property",
                           "\tdefault locale saved" );
                    } else {
                        echo "$property - $l:\t".$xml->$l;
                        $this->output->writeln("<error>Error - cannot set default $l locale <error>");
                    }
                    
                } else {
                    $this->trepo->translate($entity, $property, $l, $xml->$l);
                    $this->ShowAndLog("$property - $l:\t" . $xml->$l, "Property",
                           "\ttranslation saved but not checked" );
                }
                
            } else {
                $this->output->writeln("<error>Error - cannot find $l locale <error>");
            }
        }
    }


    private function showDeprecated( \SimpleXMLElement $xml, $properties)
    {
        foreach ($properties as $p){
            if (count($xml->$p->attributes()) > 0) {
                $this->showAndLog("$p: \t" . $xml->{$p}->attributes(), "Deprecated", 
                "\t(attribute) Deprecated");
            } 
            if (count($xml->{$p}->children()) > 0) {
                $this->showAndLog("$p: \t" . $xml->{$p}->children()->getName() . ": " .
                    $xml->{$p}->children(), "Deprecated", "\t(children) Deprecated" );

            } else{
                $this->showAndLog("$p: \t" . $xml->{$p}, "Deprecated", "\tDeprecated");
            }
        }
    }

    private function retrieveRemoteFileSize($url)
    {
        // http://stackoverflow.com/questions/2602612/php-remote-file-size-without-downloading-file
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);

        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);

        return $size;
    }

    /**
     * findOrPersist searches DB for the given object. Critical function!
     *      Each class has its own "primary" or unique properties. 
     *      Returns the DB object for further assignments or
     *      persists and returns the same object if it is not present.
     *
     * @param $entity generic entity with its properties already set.
     * @param $assoc_entity generic entity associated to the previous one (i.e. track, mmo)
     */
    private function findOrPersist( $entity, $assoc_entity = null)
    {       
        $class_name = $this->entityClassName( $entity );
        $this->showAndLog("Debug: Looking for a matching ".
            $class_name . " class object", "Question");
        
        // Find objects using "unique" parameters for a given class.
        switch ($class_name){
           
            case 'SeriesType':
                $entities_found = $this->strepo->findBy(array(
                    // 'name' => $entity->getName(),
                    'name'  => $entity->getName()));
                break;

            case 'Series':
                $entities_found   = $this->srepo->findBy( array(
                    'title'       => $entity->getTitle(),
                    'public_date' => $entity->getPublicDate() ));
                break;

            case 'MultimediaObject':
                $entities_found   = $this->mmorepo->findBy( array(
                    'series'      => $entity->getSeries()->getId(),
                    'rank'        => $entity->getRank(),
                    'title'       => $entity->getTitle(),
                    'subtitle'    => $entity->getSubtitle(),
                    'description' => $entity->getDescription(),
                    'keyword'     => $entity->getKeyword(),
                    'record_date' => $entity->getRecordDate(),
                    'public_date' => $entity->getPublicDate() ));
                break;

            case 'Pic':
                $entities_found = $this->picrepo->findBy(array(
                    'rank'      => $entity->getRank(),
                    'url'       => $entity->getUrl(),
                    'size'      => $entity->getSize() )); 
                // pray if pic.url = null.
                // Maybe more properties have to be checked 
                // as 'series' == pic->getSeries() (if not null...)
                // The same pic can be used in several db entries for serial, mm, etc
                break;

            case 'Role':
                $entities_found = $this->rolerepo->findBy(array(
                    'cod'       => $entity->getCod(),
                    'xml'       => $entity->getXml(),
                    'display'   => $entity->getDisplay() ));
                break;

            case 'Person':
                $entities_found = $this->personrepo->findBy(array(
                    'name'      => $entity->getName(),
                    'email'     => $entity->getEmail(),
                    'post'      => $entity->getPost() ));
            // Warning: there could be records with the same data, given that 
            // they have different positions (person_i18n.post). 
            // Example: somebody published media as professor in 2012 and chancellor in 2014.
            // At the moment I did not find any case using: 
            // "select id, name, count(*) from person group by name having count(*) > 1;"
                break;

            case 'Track':
                $entities_found = $this->trackrepo->findBy(array(
                    'url'                  => $entity->getUrl(),
                    'size'                 => $entity->getSize(),
                    'multimedia_object'    => $assoc_entity->getId() ));
                // The same track - file can be used in various mm_objects 
                break;

            case 'Material':
                $entities_found = $this->materialrepo->findBy(array(
                    'rank'      => $entity->getRank(),
                    'url'       => $entity->getUrl(),
                    'size'      => $entity->getSize() )); 
                break;

            default:
                throw new \Exception('Class ' . $class_name . ' Not found!');
                break;
        } // end switch $class_name

        // Process the results of the query.
        if ( 0 == count( $entities_found ) ){
            $this->showAndLog("Persisting new " . $class_name . " object", "Info");
            $this->em->persist($entity);

            return $entity;

        } else if ( 1 == count( $entities_found ) ){
            $this->showAndLog("Retrieving existing ".
                $class_name . " from the DB", "Comment");
            
            return $entities_found[0];

        } else {
            throw new \Exception('There were more than one ' . $class_name . ' object!');
        }         
    } // end function findOrPersist

    private function entityClassName( $entity ) {
        $remove_namespace = explode( '\\', get_class( $entity ) );
        return( end( $remove_namespace ) );
    }

    private function updateId( $old_id, $entity, array $ids_array)
    {
        $old_id = (integer) $old_id;
        $new_id = $entity->getId();
        $ids_array [ $old_id ] = $new_id;
        $classname = $this->entityClassName( $entity );
        $this->showAndLog("This " . $classname . " has old id:" . $old_id .
            " and new id:" . $new_id, "Debug");
        return ($ids_array);
    }

    private function logUpdatedIds()
    {
        $mms_filename    = $this->ids_csv_path . "mms_oldid_newid.csv";
        $series_filename = $this->ids_csv_path . "series_oldid_newid.csv";
        $tracks_filename = $this->ids_csv_path . "tracks_oldid_newid.csv";


        $contents = "";
        foreach ($this->ids_series_array as $old_id => $new_id){
            $contents .= $old_id . "," . $new_id . "\n";
        }
        if ( $contents != "") {
            file_put_contents( $series_filename, $contents, FILE_APPEND)
            or die ('Unable to write series log');
        } else {
            $this->showAndLog("Error - Series ids are not updated. Weird.", "Error");
        }

        $contents = "";
        foreach ($this->ids_mms_array as $old_id => $new_id){
            $contents .= $old_id . "," . $new_id . "\n";
        }
        if ( $contents != "") {
            file_put_contents( $mms_filename, $contents, FILE_APPEND)
            or die ('Unable to write mms log');
        } else {
            $this->showAndLog("Error - Multimedia Object ids are not updated. Weird.", "Error");
        }


        $contents = "";
        foreach ($this->ids_tracks_array as $old_id => $new_id){
            $contents .= $old_id . "," . $new_id . "\n";
        }
        if ( $contents != "") {
            file_put_contents( $tracks_filename, $contents, FILE_APPEND)
            or die ('Unable to write tracks log');
        } else {
            $this->showAndLog("Debug - There isn't any updated track id.", "Debug");
        }
    }

    private function showAsciiArt( $title )
    {
        switch ($title){


            case "multimediaobject":
                $message = '
  __  __      _ _   _              _ _       ___  _     _        _   
 |  \/  |_  _| | |_(_)_ __  ___ __| (_)__ _ / _ \| |__ (_)___ __| |_ 
 | |\/| | || | |  _| | `  \/ -_) _` | / _` | (_) | `_ \| / -_) _|  _|
 |_|  |_|\_,_|_|\__|_|_|_|_\___\__,_|_\__,_|\___/|_.__// \___\__|\__|
                                                     |__/            
';
                break;
            
            case "series":
                $message = '
  ___          _        
 / __| ___ _ _(_)___ ___
 \__ \/ -_) `_| / -_|_-<
 |___/\___|_| |_\___/__/

';
                break;

            default:
                $message="";
        }
        $this->showAndLog($message);


    }


    /**
     * Shows a line with one message colored by its category or 
     * a standard message and a colored result in the same line.
     */
    private function showAndLog($message, $type = "", $message2 = "")
    {
        // Property:   used to test entity setters.
        // Deprecated: used to show xml nodes that are not used.
        // Question:   used in findOrPersist with debug purpose. 
        $dont_show = array("Property", "Deprecated", "Question");
        if (in_array( $type, $dont_show ) ) {

            return;
        }

        $highlighted = ("" == $message2) ? $message : "$message2";
        
        switch ($type){
            case "Property";
            case "Info":
                $screen = '<info>' . $highlighted . '</info>';
                $logger_interface_method = 'info';
                break;
            case "Deprecated";
            case "Comment":
                $screen = '<comment>' . $highlighted . '</comment>';
                $logger_interface_method = 'info';
                break;
            case "Warning":
                $screen = '<question>' . $highlighted . '</question>';
                $logger_interface_method = 'warn';
                break;
            case "Debug";
            case "Question":
                $screen = '<question>' . $highlighted . '</question>';
                $logger_interface_method = 'debug';
                break;
            case "Error":
                $screen = '<error>' . $highlighted . '</error>';
                $logger_interface_method = 'err';
                break;
            case "Url";
            default:
                $screen = $highlighted;
                $logger_interface_method = 'info';
                break;                
        }

        if ($message2 != "") {
            $screen = $message . "\t" . $screen;
        }
        $this->output->writeln($screen);
        $this->logger->$logger_interface_method( $message . $message2);
         /* logger_interface_method = emerg alert crit err warn notice info debug*/

// To do: refine the logger. Maybe debug levels that log different subsets of types.
// http://api.symfony.com/2.0/Symfony/Component/HttpKernel/Log/LoggerInterface.html
    }

} 