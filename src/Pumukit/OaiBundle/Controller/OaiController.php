<?php

namespace Pumukit\OaiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Pumukit\OaiBundle\Utils\SimpleXMLExtended;

/*
 * Open Archives Initiative Controller for PuMuKIT.
 *
 * Test with Repository Explorer (http://re.cs.uct.ac.za/)
 */
class OaiController extends Controller
{
    /**
     * @Route("/oai.xml", defaults={"_format": "xml"}, name="pumukit_oai_index")
     */
    public function indexAction(Request $request)
    {
        $verb = $request->query->get('verb');

        switch ($request->query->get('verb')) {
        case 'Identify':
            return $this->identify();
        case 'ListMetadataFormats':
            return $this->listMetadataFormats($request);
        case 'ListSets':
            return $this->listSets($request);
        case 'ListIdentifiers':
        case 'ListRecords':
            return $this->listIdentifiers($request);
        case 'GetRecord':
            return $this->getRecord($request);
        default:
            return $this->error('badVerb', 'Illegal OAI verb');
        }
    }

    /*
     * Genera la salida de GetRecord
     */
    public function getRecord($request)
    {
        if ($request->query->get('metadataPrefix') != 'oai_dc') {
            return $this->error('cannotDisseminateFormat', 'cannotDisseminateFormat');
        }

        $identifier = $request->query->get('identifier');

        $mmObjColl = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $object = $mmObjColl->find(array('id' => $identifier));

        if ($object == null) {
            return $this->error('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository');
        }

        $request = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);
        $XMLrequest->addAttribute('verb', 'GetRecord');
        $XMLrequest->addAttribute('identifier', $identifier);
        $XMLrequest->addAttribute('metadataPrefix', 'oai_dc');

        $XMLgetRecord = new SimpleXMLExtended('<GetRecord></GetRecord>');
        $XMLrecord = $XMLgetRecord->addChild('record');
        $this->genObjectHeader($XMLrecord, $object);
        $this->genObjectMetadata($XMLrecord, $object);

        return $this->genResponse($XMLrequest, $XMLgetRecord);
    }

    private function identify()
    {
        $request = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);
        $XMLrequest->addAttribute('verb', 'Identify');

        $XMLidentify = new SimpleXMLExtended('<Identify></Identify>');
        $info = $this->container->getParameter('pumukit2.info');
        $XMLidentify->addChild('repositoryName', $info['description']);
        $XMLidentify->addChild('baseURL', $this->generateUrl('pumukit_oai_index', array(), true));
        $XMLidentify->addChild('protocolVersion', '2.0');
        $XMLidentify->addChild('adminEmail', $info['email']);
        $XMLidentify->addChild('earliestDatestamp', '1990-02-01T12:00:00Z');
        $XMLidentify->addChild('deletedRecord', 'no');
        $XMLidentify->addChild('granularity', 'YYYY-MM-DDThh:mm:ssZ');

        return $this->genResponse($XMLrequest, $XMLidentify);
    }

    public function listIdentifiers($request)
    {
        $pag = 2;
        $verb = $request->query->get('verb');
        $from = $request->query->get('from');
        $until = $request->query->get('until');
        $set = $request->query->get('set');
        $resumptionToken = $request->query->get('resumptionToken');

        if ($request->query->get('metadataPrefix') != 'oai_dc') {
            return $this->error('cannotDisseminateFormat', 'cannotDisseminateFormat');
        }

        $token = $this->validateToken($resumptionToken);
        if ($token['pag'] != null) {
            $pag = $token['pag'];
        }

        $mmObjColl = $this->filter($request, $pag);

        if (count($mmObjColl) == 0) {
            return $this->error('noRecordsMatch', 'The combination of the values of the from, until, and set arguments results in an empty list');
        }

        if ((($resumptionToken > ceil(count($mmObjColl) / 10)) || ($resumptionToken < 1)) && $resumptionToken != null) {
            return $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired');
        }

        if ($pag >= ceil(count($mmObjColl) / 10)) {
            $pag = ceil(count($mmObjColl) / 10);
        }

        $XMLrequestText = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($XMLrequestText);
        $XMLrequest->addAttribute('metadataPrefix', 'oai_dc');
        if ($request->query->get('from')) {
            $XMLrequest->addAttribute('from', $from);
        }
        if ($request->query->get('until')) {
            $XMLrequest->addAttribute('until', $until);
        }
        if ($request->query->get('set')) {
            $XMLrequest->addAttribute('set', $set);
        }

        $XMLrequest->addAttribute('verb', $verb);
        if ($verb == 'ListIdentifiers') {
            $XMLlist = new SimpleXMLExtended('<ListIdentifiers></ListIdentifiers>');
            foreach ($mmObjColl as $object) {
                $this->genObjectHeader($XMLlist, $object);
            }
        } else {
            $XMLlist = new SimpleXMLExtended('<ListRecords></ListRecords>');
            foreach ($mmObjColl as $object) {
                $XMLrecord = $XMLlist->addChild('record');
                $this->genObjectHeader($XMLrecord, $object);
                $this->genObjectMetadata($XMLrecord, $object);
            }
        }
        $XMLresumptionToken = $XMLlist->addChild('resumptionToken', $pag);
        $XMLresumptionToken->addAttribute('expirationDate', '2222-06-01T23:20:00Z');
        $XMLresumptionToken->addAttribute('completeListSize', count($mmObjColl));
        $XMLresumptionToken->addAttribute('cursor', '0');

        return $this->genResponse($XMLrequest, $XMLlist);
    }

    private function listMetadataFormats($request)
    {
        $identifier = $request->query->get('identifier');

        $mmObjColl = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $mmObj = $mmObjColl->find(array('id' => $identifier));

        if ($request->query->has('identifier') && $mmObj == null) {
            return $this->error('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository');
        }

        $XMLrequestText = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($XMLrequestText);
        $XMLrequest->addAttribute('verb', 'ListMetadataFormats');
        if ($request->query->has('identifier')) {
            $XMLrequest->addAttribute('identifier', $identifier);
        }

        $XMLlistMetadataFormats = new SimpleXMLExtended('<ListMetadataFormats></ListMetadataFormats>');
        $XMLmetadataFormat = $XMLlistMetadataFormats->addChild('metadataFormat');
        $XMLmetadataFormat->addChild('metadataPrefix', 'oai_dc');
        $XMLmetadataFormat->addChild('schema', 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd');
        $XMLmetadataFormat->addChild('metadataNamespace', 'http://www.openarchives.org/OAI/2.0/oai_dc/');

        return $this->genResponse($XMLrequest, $XMLlistMetadataFormats);
    }

    private function listSets($request)
    {
        $pag = 2;
        $resumptionToken = $request->query->get('resumptionToken');

        //TODO fix.
        $token = $this->validateToken($resumptionToken);
        if ($token['error'] == true) {
            return $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired');
        }

        if ($token['pag'] != null) {
            $pag = $token['pag'];
        }

        $allSeries = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');
        $allSeries = $allSeries->createQueryBuilder()->limit(10)->skip(10 * ($pag - 2));
        $allSeries = $allSeries->getQuery()->execute();

        if ((($resumptionToken > ceil(count($allSeries) / 10)) || ($resumptionToken < 1)) && $resumptionToken != null) {
            return $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired');
        }

        if ($pag >= ceil(count($allSeries) / 10)) {
            $pag = ceil(count($allSeries) / 10);
        }

        $request = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);
        $XMLrequest->addAttribute('verb', 'ListSets');

        $XMLlistSets = new SimpleXMLExtended('<ListSets></ListSets>');
        foreach ($allSeries as $series) {
            $XMLset = $XMLlistSets->addChild('set');
            $XMLsetSpec = $XMLset->addChild('setSpec');
            $XMLsetSpec->addCDATA($series->getId());
            $XMLsetName = $XMLset->addChild('setName');
            $XMLsetName->addCDATA($series->getTitle());
        }
        $XMLresumptionToken = $XMLlistSets->addChild('resumptionToken', $pag);
        $XMLresumptionToken->addAttribute('expirationDate', '2222-06-01T23:20:00Z');
        $XMLresumptionToken->addAttribute('completeListSize', count($allSeries));
        $XMLresumptionToken->addAttribute('cursor', '0');

        return $this->genResponse($XMLrequest, $XMLlistSets);
    }

    /*
     * Genera el XML de error
     */
    protected function error($cod, $msg = '')
    {
        $this->cod = $cod;
        $this->msg = $msg;

        $request = '<request>'.$this->generateUrl('pumukit_oai_index', array(), true).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);

        $error = '<error>'.$this->msg.'</error>';
        $XMLerror = new SimpleXMLExtended($error);
        $XMLerror->addAttribute('code', $this->cod);

        return $this->genResponse($XMLrequest, $XMLerror);
    }

    /*
     * Modifica el objeto criteria de entrada añadiendo filtros de fechas (until & from) y de set si están definidos en la URI
     */
    protected function filter($request, $pag)
    {
        $repository_multimediaObjects = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
        $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

        $queryBuilder_multimediaObjects = $repository_multimediaObjects->createStandardQueryBuilder()->limit(10)->skip(10 * ($pag - 2));
        $queryBuilder_series = $repository_series->createQueryBuilder();

        if ($request->query->get('from')) {
            $from = \DateTime::createFromFormat('Y/m/d', $request->query->get('from'));
            $queryBuilder_multimediaObjects->field('public_date')->gte($from);
        }

        if ($request->query->get('until')) {
            $until = \DateTime::createFromFormat('Y/m/d', $request->query->get('until'));
            $queryBuilder_multimediaObjects->field('public_date')->lte($until);
        }

        if ($request->query->get('set')) {
            $set = $request->query->get('set');
            $series = $repository_series->find(array('id' => $set));
            $queryBuilder_multimediaObjects->field('series')->references($series);
        }

        $objects = $queryBuilder_multimediaObjects->getQuery()->execute();

        return $objects;
    }

    private function genObjectHeader($XMLlist, $object)
    {
        $XMLheader = $XMLlist->addChild('header');
        $XMLidentifier = $XMLheader->addChild('identifier');
        $XMLidentifier->addCDATA($object->getId());
        $XMLheader->addChild('datestamp', $object->getPublicDate()->format('Y-m-d'));
        $XMLsetSpec = $XMLheader->addChild('setSpec');
        $XMLsetSpec->addCDATA($object->getSeries()->getId());

        return $XMLheader;
    }

    private function genObjectMetadata($XMLlist, $object)
    {
        $XMLmetadata = $XMLlist->addChild('metadata');

        $XMLoai_dc = new SimpleXMLExtended('<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd"></oai_dc:dc>');
        $XMLtitle = $XMLoai_dc->addChild('dc:title', null, 'http://purl.org/dc/elements/1.1/');
        $XMLtitle->addCDATA($object->getTitle());
        $XMLdescription = $XMLoai_dc->addChild('dc:description', null, 'http://purl.org/dc/elements/1.1/');
        $XMLdescription->addCDATA($object->getDescription());
        $XMLoai_dc->addChild('dc:date', $object->getPublicDate()->format('Y-m-d'));
        $url = $this->generateUrl('pumukit_webtv_multimediaobject_index', array('id' => $object->getId()), true);
        $XMLiden = $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
        //$XMLiden->addAttribute('xsi:type', 'dcterms:URI');
        //$XMLiden->addAttribute('id', 'uid');

        if ($this->container->getParameter('pumukitoai.use_dc_thumbnail')) {
            $thumbnail = $this->get('pumukitschema.pic')->getFirstUrlPic($object, true);
            $XMLiden = $XMLoai_dc->addChild('dc:thumbnail', $thumbnail, 'http://purl.org/dc/elements/1.1/');
        }

        foreach ($object->getFilteredTracksWithTags(array('display')) as $track) {
            $type = $track->isOnlyAudio() ?
                $this->container->getParameter('pumukitoai.audio_dc_type') :
                $this->container->getParameter('pumukitoai.video_dc_type');
            $XMLoai_dc->addChild('dc:type', $type, 'http://purl.org/dc/elements/1.1/');
            $XMLoai_dc->addChild('dc:format', $track->getMimeType(), 'http://purl.org/dc/elements/1.1/');
        }
        foreach ($object->getTags() as $tag) {
            $XMLsubject = $XMLoai_dc->addChild('dc:subject', null, 'http://purl.org/dc/elements/1.1/');
            $XMLsubject->addCDATA($tag->getTitle());
        }
        $XMLcreator = $XMLoai_dc->addChild('dc:creator', null, 'http://purl.org/dc/elements/1.1/');
        $XMLcreator->addCDATA('');
        $XMLpublisher = $XMLoai_dc->addChild('dc:publisher', null, 'http://purl.org/dc/elements/1.1/');
        $XMLpublisher->addCDATA('');
        $XMLoai_dc->addChild('dc:language', $object->getLocale(), 'http://purl.org/dc/elements/1.1/');
        if ($this->container->getParameter('pumukitoai.use_license_as_dc_rights')) {
            $XMLrights = $XMLoai_dc->addChild('dc:rights', null, 'http://purl.org/dc/elements/1.1/');
            $XMLrights->addCDATA($object->getLicense());
        } else {
            $XMLrights = $XMLoai_dc->addChild('dc:rights', null, 'http://purl.org/dc/elements/1.1/');
            $XMLrights->addCDATA($object->getCopyright());
        }


        $toDom = dom_import_simplexml($XMLmetadata);
        $fromDom = dom_import_simplexml($XMLoai_dc);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    private function validateToken($resumptionToken)
    {
        if ($resumptionToken != null) {
            $error = false;

            return array('pag' => (((int) $resumptionToken) + 1), 'error' => $error);
        }
    }

    private function genResponse($responseXml, $verb)
    {
        $XML = new SimpleXMLExtended('<OAI-PMH></OAI-PMH>');
        $XML->addAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $XML->addAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
        $XMLresponseDate = $XML->addChild('responseDate', date("Y-m-d\TH:i:s\Z"));

        $toDom = dom_import_simplexml($XML);
        $fromDom = dom_import_simplexml($responseXml);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
        $XML = simplexml_import_dom($toDom);

        $toDom = dom_import_simplexml($XML);
        $fromDom = dom_import_simplexml($verb);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
        $XML = simplexml_import_dom($toDom);

        return new Response($XML->asXML(), 200, array('Content-Type' => 'text/xml'));
    }
}
