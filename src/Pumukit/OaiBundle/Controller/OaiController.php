<?php

namespace Pumukit\OaiBundle\Controller;

use Pumukit\OaiBundle\Utils\Iso639Convert;
use Pumukit\OaiBundle\Utils\ResumptionToken;
use Pumukit\OaiBundle\Utils\ResumptionTokenException;
use Pumukit\OaiBundle\Utils\SimpleXMLExtended;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    // Genera la salida de GetRecord
    public function getRecord($request)
    {
        if ('oai_dc' !== $request->query->get('metadataPrefix')) {
            return $this->error('cannotDisseminateFormat', 'cannotDisseminateFormat');
        }

        $identifier = $request->query->get('identifier');

        $mmObjColl = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);
        $object = $mmObjColl->find(['id' => $identifier]);

        if (null === $object) {
            return $this->error('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository');
        }

        $request = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
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

    public function listIdentifiers($request)
    {
        $verb = $request->query->get('verb');
        $limit = 10;

        try {
            $token = $this->getResumptionToken($request);
        } catch (ResumptionTokenException $e) {
            return $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired');
        } catch (\Exception $e) {
            return $this->error('badArgument', 'The request includes illegal arguments, is missing required arguments, includes a repeated argument, or values for arguments have an illegal syntax');
        }

        if ('oai_dc' !== $token->getMetadataPrefix()) {
            return $this->error('cannotDisseminateFormat', 'cannotDisseminateFormat');
        }

        $mmObjColl = $this->filter($limit, $token->getOffset(), $token->getFrom(), $token->getUntil(), $token->getSet());

        if (0 === count($mmObjColl)) {
            return $this->error('noRecordsMatch', 'The combination of the values of the from, until, and set arguments results in an empty list');
        }

        $XMLrequestText = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
        $XMLrequest = new SimpleXMLExtended($XMLrequestText);
        $XMLrequest->addAttribute('metadataPrefix', 'oai_dc');
        if ($token->getFrom()) {
            $XMLrequest->addAttribute('from', $token->getFrom()->format('Y-m-d'));
        }
        if ($token->getUntil()) {
            $XMLrequest->addAttribute('until', $token->getUntil()->format('Y-m-d'));
        }
        if ($token->getSet()) {
            $XMLrequest->addAttribute('set', $token->getSet());
        }

        $XMLrequest->addAttribute('verb', $verb);
        if ('ListIdentifiers' === $verb) {
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

        $next = $token->next();
        $cursor = $limit * $next->getOffset();
        $count = count($mmObjColl);

        if ($cursor < $count) {
            $XMLresumptionToken = $XMLlist->addChild('resumptionToken', $next->encode());
            $XMLresumptionToken->addAttribute('expirationDate', '2222-06-01T23:20:00Z');
            $XMLresumptionToken->addAttribute('completeListSize', $count);
            $XMLresumptionToken->addAttribute('cursor', $cursor < $count ? $cursor : $count);
        }

        return $this->genResponse($XMLrequest, $XMLlist);
    }

    // Genera el XML de error
    protected function error($cod, $msg = '')
    {
        $request = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);

        $error = '<error>'.$msg.'</error>';
        $XMLerror = new SimpleXMLExtended($error);
        $XMLerror->addAttribute('code', $cod);

        return $this->genResponse($XMLrequest, $XMLerror);
    }

    // Modifica el objeto criteria de entrada añadiendo filtros de fechas (until & from) y de set si están definidos en la URI
    protected function filter($limit, $offset, \DateTime $from = null, \DateTime $until = null, $set = null)
    {
        $multimediaObjectRepo = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);
        $seriesRepo = $this->get('doctrine_mongodb')->getRepository(Series::class);

        $queryBuilder = $multimediaObjectRepo
            ->createStandardQueryBuilder()
            ->limit($limit)
            ->skip($limit * $offset)
        ;

        if ($from) {
            $queryBuilder->field('public_date')->gte($from);
        }

        if ($until) {
            $queryBuilder->field('public_date')->lte($until);
        }

        if ($set && '_all_' !== $set) {
            $series = $seriesRepo->find(['id' => $set]);
            if (!$series) {
                return [];
            }
            $queryBuilder->field('series')->references($series);
        }

        return $queryBuilder->getQuery()->execute();
    }

    private function identify()
    {
        $request = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
        $XMLrequest = new SimpleXMLExtended($request);
        $XMLrequest->addAttribute('verb', 'Identify');

        $XMLidentify = new SimpleXMLExtended('<Identify></Identify>');
        $info = $this->container->getParameter('pumukit.info');
        $XMLidentify->addChild('repositoryName', $info['description']);
        $XMLidentify->addChild('baseURL', $this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
        $XMLidentify->addChild('protocolVersion', '2.0');
        $XMLidentify->addChild('adminEmail', $info['email']);
        $XMLidentify->addChild('earliestDatestamp', '1990-02-01T12:00:00Z');
        $XMLidentify->addChild('deletedRecord', 'no');
        $XMLidentify->addChild('granularity', 'YYYY-MM-DDThh:mm:ssZ');

        return $this->genResponse($XMLrequest, $XMLidentify);
    }

    private function listMetadataFormats($request)
    {
        $identifier = $request->query->get('identifier');

        $mmObjColl = $this->get('doctrine_mongodb')->getRepository(MultimediaObject::class);
        $mmObj = $mmObjColl->find(['id' => $identifier]);

        if ($request->query->has('identifier') && null === $mmObj) {
            return $this->error('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository');
        }

        $XMLrequestText = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
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
        $limit = 10;

        try {
            $token = $this->getResumptionToken($request);
        } catch (ResumptionTokenException $e) {
            return $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired');
        } catch (\Exception $e) {
            return $this->error('badArgument', 'The request includes illegal arguments, is missing required arguments, includes a repeated argument, or values for arguments have an illegal syntax');
        }

        $allSeries = $this->get('doctrine_mongodb')->getRepository(Series::class);
        $allSeries = $allSeries
            ->createQueryBuilder()
            ->limit($limit)
            ->skip($limit * $token->getOffset())
            ->getQuery()
            ->execute()
        ;

        $request = '<request>'.$this->generateUrl('pumukit_oai_index', [], UrlGeneratorInterface::ABSOLUTE_URL).'</request>';
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

        $next = $token->next();
        $cursor = $limit * $next->getOffset();
        $count = count($allSeries);

        if ($cursor < $count) {
            $XMLresumptionToken = $XMLlistSets->addChild('resumptionToken', $next->encode());
            $XMLresumptionToken->addAttribute('expirationDate', '2222-06-01T23:20:00Z');
            $XMLresumptionToken->addAttribute('completeListSize', $count);
            $XMLresumptionToken->addAttribute('cursor', $cursor < $count ? $cursor : $count);
        }

        return $this->genResponse($XMLrequest, $XMLlistSets);
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
        $XMLoai_dc->addChild('dc:date', $object->getPublicDate()->format('Y-m-d'), 'http://purl.org/dc/elements/1.1/');

        switch ($this->container->getParameter('pumukitoai.dc_identifier_url_mapping')) {
            case 'all':
                $url = $this->generateUrl('pumukit_webtv_multimediaobject_iframe', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                $url = $this->generateUrl('pumukit_webtv_multimediaobject_index', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                foreach ($object->getFilteredTracksWithTags(['display']) as $track) {
                    $url = $this->generateTrackFileUrl($track);
                    $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                }

                break;
            case 'portal_and_track':
                $url = $this->generateUrl('pumukit_webtv_multimediaobject_index', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                foreach ($object->getFilteredTracksWithTags(['display']) as $track) {
                    $url = $this->generateTrackFileUrl($track);
                    $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                }

                break;
            case 'track':
                foreach ($object->getFilteredTracksWithTags(['display']) as $track) {
                    $url = $this->generateTrackFileUrl($track);
                    $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');
                }

                break;
            case 'iframe':
                $url = $this->generateUrl('pumukit_webtv_multimediaobject_iframe', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');

                break;
            default: //portal
                $url = $this->generateUrl('pumukit_webtv_multimediaobject_index', ['id' => $object->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                $XMLoai_dc->addChild('dc:identifier', $url, 'http://purl.org/dc/elements/1.1/');

                break;
        }

        //$XMLiden->addAttribute('xsi:type', 'dcterms:URI');
        //$XMLiden->addAttribute('id', 'uid');

        if ($this->container->getParameter('pumukitoai.use_dc_thumbnail')) {
            $thumbnail = $this->get('pumukitschema.pic')->getFirstUrlPic($object, true);
            $XMLoai_dc->addChild('dc:thumbnail', $thumbnail, 'http://purl.org/dc/elements/1.1/');
        }

        foreach ($object->getFilteredTracksWithTags(['display']) as $track) {
            $type = $track->isOnlyAudio() ?
                $this->container->getParameter('pumukitoai.audio_dc_type') :
                $this->container->getParameter('pumukitoai.video_dc_type');
            $XMLoai_dc->addChild('dc:type', $type, 'http://purl.org/dc/elements/1.1/');
            $XMLoai_dc->addChild('dc:format', $track->getMimeType(), 'http://purl.org/dc/elements/1.1/');
        }
        foreach ($object->getTags() as $tag) {
            $XMLsubject = $XMLoai_dc->addChild('dc:subject', null, 'http://purl.org/dc/elements/1.1/');
            switch ($this->container->getParameter('pumukitoai.dc_subject_format')) {
                case 'e-ciencia':
                    $cod = $tag->getCod();
                    if (($tag->isDescendantOfByCod('UNESCO')) || (0 === strpos($tag->getCod(), 'U9'))) {
                        $cod = $tag->getLevel();
                        switch ($tag->getLevel()) {
                        case 3:
                            $cod = substr($tag->getCod(), 1, 2);

                            break;
                        case 4:
                            $cod = substr($tag->getCod(), 1, 4);

                            break;
                        case 5:
                            $cod = sprintf('%s.%s', substr($tag->getCod(), 1, 4), substr($tag->getCod(), 5, 2));

                            break;
                        }
                    }
                    $subject = sprintf('%s %s', $cod, $tag->getTitle());

                    break;
                case 'all':
                    $subject = sprintf('%s - %s', $tag->getCod(), $tag->getTitle());

                    break;
                case 'code':
                    $subject = $tag->getCod();

                    break;
                default: //title
                    $subject = $tag->getTitle();

                    break;
            }
            $XMLsubject->addCDATA($subject);
        }

        if ($this->container->getParameter('pumukitoai.use_copyright_as_dc_publisher')) {
            $XMLpublisher = $XMLoai_dc->addChild('dc:publisher', null, 'http://purl.org/dc/elements/1.1/');
            $XMLpublisher->addCDATA($object->getCopyright());
        } else {
            $XMLpublisher = $XMLoai_dc->addChild('dc:publisher', null, 'http://purl.org/dc/elements/1.1/');
            $XMLpublisher->addCDATA('');
        }

        $people = $object->getPeopleByRoleCod($this->container->getParameter('pumukitoai.role_for_dc_creator'), true);
        foreach ($people as $person) {
            $XMLcreator = $XMLoai_dc->addChild('dc:creator', null, 'http://purl.org/dc/elements/1.1/');
            $XMLcreator->addCDATA($person->getName());
        }

        if ($object->getLocale()) {
            $XMLoai_dc->addChild('dc:language', $object->getLocale(), 'http://purl.org/dc/elements/1.1/');
        }
        if ($codeLocale3 = Iso639Convert::get($object->getLocale())) {
            $XMLoai_dc->addChild('dc:language', $codeLocale3, 'http://purl.org/dc/elements/1.1/');
        }

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

    private function genResponse($responseXml, $verb)
    {
        $XML = new SimpleXMLExtended('<OAI-PMH></OAI-PMH>');
        $XML->addAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/');
        $XML->addAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
        $XML->addChild('responseDate', date('Y-m-d\\TH:i:s\\Z'));

        $toDom = dom_import_simplexml($XML);
        $fromDom = dom_import_simplexml($responseXml);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
        $XML = simplexml_import_dom($toDom);

        $toDom = dom_import_simplexml($XML);
        $fromDom = dom_import_simplexml($verb);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
        $XML = simplexml_import_dom($toDom);

        return new Response($XML->asXML(), 200, ['Content-Type' => 'text/xml']);
    }

    private function getResumptionToken(Request $request)
    {
        if ($request->query->has('resumptionToken')) {
            return ResumptionToken::decode($request->query->get('resumptionToken'));
        }

        $from = $request->query->has('from') ?
            \DateTime::createFromFormat('Y-m-d', $request->query->get('from')) :
            null;

        $until = $request->query->has('until') ?
            \DateTime::createFromFormat('Y-m-d', $request->query->get('until')) :
            null;

        return new ResumptionToken(0, $from, $until, $request->query->get('metadataPrefix'), $request->query->get('set'));
    }

    private function generateTrackFileUrl($track)
    {
        $trackService = $this->get('pumukit_baseplayer.trackurl');

        return $trackService->generateTrackFileUrl($track, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
