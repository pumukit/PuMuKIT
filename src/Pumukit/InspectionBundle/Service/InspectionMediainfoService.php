<?php

namespace Pumukit\InspectionBundle\Service;

use Pumukit\SchemaBundle\Entity\Track;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class InspectionMediainfoService implements InspectionServiceInterface
{

	private $logger;

	public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Gets file duration in s.
     * Check "mediainfo -f file" output.
     * @param $file
     * @return integer $duration file duration in s rounded up.
     */
	public function getDuration($file) 
	{
		if (!file_exists($file)){	
			throw new \BadMethodCallException("The file " . $file . " does not exist");
		}

		$xml = simplexml_load_string( $this->getMediaInfo($file) );
		if (!$this->xmlHasMediaContent($xml)) {
			throw new \InvalidArgumentException("This file has no accesible video " .
				"nor audio tracks\n" . $file );
		}

		$duration = ceil($xml->File->track->Duration / 1000); // in ms (using mediainfo -f)

		return $duration;
	}
	
	// Check the desired codec names (MPEG Audio/MPEG-1 Audio layer 3; AAC / Advanced Audio Codec / ...)
	// Now we choose FORMAT.
	/**
     * Completes track information from a given path using mediainfo.
     * @param Track $track
     */
	public function autocompleteTrack(Track $track)
	{
		$only_audio = true; //initialized true until video track is found.
		if (!$track->getPath()){	
			throw new \BadMethodCallException('Input track has no path defined');
		}

		$xml = simplexml_load_string( $this->getMediaInfo( $track->getPath() ) );
		if (!$this->xmlHasMediaContent($xml)) {
			throw new \InvalidArgumentException("This file has no accesible video " .
				"nor audio tracks\n" . $track->getPath() );
		}

		foreach ($xml->File->track as $xml_track){
			
			switch ( (string) $xml_track['type'] ){
				case "General":
					$track->setMimetype( $xml_track->InternetMediaType );
					$track->setBitrate( $xml_track->Overall_bit_rate );
					$track->setDuration( ceil($xml_track->Duration / 1000)  );
					$track->setSize( $xml_track->File_size ); 
					break;

				case "Video":
					$track->setVcodec( $xml_track->Format );
					$track->setFramerate( $xml_track->Frame_rate );
					$track->setWidth( $xml_track->Width );
					$track->setHeight( $xml_track->Height );
					$only_audio = false;
					break;

				case "Audio":
				/* In video files, there is "CODEC_FAMILY" i.e. AAC, "FORMAT_Info", etc 
				but audio only files like mp3 do not have these fields */
					$track->setAcodec( $xml_track->Format); //TO DO check the desired codec name
					$track->setChannels( $xml_track->Channel_s_);
					break;
			}
			$track->setOnlyAudio($only_audio);
		}
	}

	private function xmlHasMediaContent($xml){
		if( $xml->File->track != null ){
			foreach ($xml->File->track as $track) {
				if ($track['type'] == "Audio" || $track['type'] == "Video"){
		
					return true;
				}
			}
		}
	
		return false;	
	}

	private function getMediaInfo($file){
		$process = new Process('mediainfo -f --Output=XML \'' . $file . '\'');
		$process->setTimeout(60);
		$process->run();
		if (!$process->isSuccessful()) {
		    throw new \RuntimeException($process->getErrorOutput());
		}

		return $process->getOutput();
	}
}