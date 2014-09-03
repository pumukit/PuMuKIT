<?php

namespace Pumukit\SchemaBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\SchemaBundle\Document\MultimediaObject
 *
 * @MongoDB\Document(repositoryClass="Pumukit\SchemaBundle\Repository\MultimediaObjectRepository")
 */
class MultimediaObject
{
	const STATUS_NORMAL = 0;
	const STATUS_BLOQ = 1;
	const STATUS_HIDE = 2;
	const STATUS_NEW = -1;
	const STATUS_PROTOTYPE = -2;

	/**
	 * @var int $id
	 *
	 * @MongoDB\Id
	 */
	private $id;

	/**
	 * @MongoDB\EmbedOne(targetDocument="Series")
	 */
	private $series;

	/**
	 * @var array $tags
	 *
	 * @MongoDB\EmbedMany(targetDocument="Tag")
	 */
	private $tags;

	/**
	 * @var ArrayCollection $tracks
	 *
	 * @MongoDB\EmbedMany(targetDocument="Track")
	 */
	private $tracks;

	/**
	 * @var ArrayCollection $pics
	 *
	 * @MongoDB\EmbedMany(targetDocument="Pic")
	 */
	private $pics;

	/**
	 * @var ArrayCollection $materials
	 *
	 * @MongoDB\EmbedMany(targetDocument="Material")
	 */
	private $materials;

	/**
	 * @var int $rank
	 *
	 * @MongoDB\Int
	 */
	private $rank;

	/**
	 * @var int $status
	 *
	 * @MongoDB\Int
	 */
	private $status = self::STATUS_NEW;

	/**
	 * @var date $record_date
	 *
	 * @MongoDB\Date
	 */
	private $record_date;

	/**
	 * @var date $public_date
	 *
	 * @MongoDB\Date
	 */
	private $public_date;

	/**
	 * @var string $title
	 *
	 * @MongoDB\String
	 */
	private $title;

	/**
	 * @var string $subtitle
	 *
	 * @MongoDB\String
	 */
	private $subtitle;

	/**
	 * @var String $description
	 *
	 * @MongoDB\String
	 */
	private $description;

	/**
	 * @var string $line2
	 *
	 * @MongoDB\String
	 */
	private $line2;

	/**
	 * @var string $copyright
	 *
	 * @MongoDB\String
	 */
	private $copyright;

	/**
	 * @var string $keyword
	 *
	 * @MongoDB\String
	 */
	private $keyword;

	/**
	 * @var int $duration
	 *
	 * @MongoDB\Int
	 */
	private $duration = 0;

	/**
	 * @var ArrayCollection $people_in_multimedia_object
	 *
	 * //@MongoDB\EmbedMany(targetDocument="PersonInMultimediaObject")
	 */
	//private $people_in_multimedia_object;

	/**
	 * //@Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 */
	private $locale;

	public function setTranslatableLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * //@MongoDB\EmbedMany(targetDocument="MultimediaObjectTranslation")
	 */
	private $translations;

	public function getTranslations() 
	{ 
		return $this->translations; 
	}

	public function addTranslation($t)
	{
		if (!$this->translations->contains($t)) {
			$this->translations[] = $t;
			$t->setObject($this);
		}
	}
	public function removeTranslation($t)
	{
	}

	public function __construct()
	{
		$this->tracks = new ArrayCollection();
		$this->pics = new ArrayCollection();
		$this->materials = new ArrayCollection();
		$this->tags = new ArrayCollection();
		//$this->people_in_multimedia_object = new ArrayCollection();
	}

	public function __toString()
	{
		return $this->title;
	}

	/**
	 * Get id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set series
	 *
	 * @param Series $series
	 */
	public function setSeries(Series $series)
	{
		$this->series = $series;
	}

	/**
	 * Get series
	 *
	 * @return Series
	 */
	public function getSeries()
	{
		return $this->series;
	}

	// Start tag section. Caution: MultimediaObject tags are Tag objects, not strings.
	/**
	 * Set tags
	 *
	 * @param array $tags
	 */
	public function setTags(array $tags)
	{
		$this->tags = new ArrayCollection($tags);
	}

	/**
	 * Get tags
	 *
	 * @return array
	 */
	public function getTags()
	{
		return $this->tags;
	}

	/**
	 * Add tag
	 * The original string tag logic used array_unique to avoid tag duplication.
	 * @param Tag $tag
	 */
	public function addTag(Tag $tag)
	{
		if (!($this->containsTag($tag))) {
			$this->tags[] = $tag;
		}
	}

	/**
	 * Remove tag
	 * The original string tag logic used array_search to seek the tag element in array.
	 * This function uses doctrine2 arrayCollection contains function instead.
	 * @param Tag $tag
	 * @return boolean TRUE if this multimedia_object contained the specified tag, FALSE otherwise.
	 */
	public function removeTag(Tag $tag)
	{
		if ($this->tags->contains($tag)) {
			return $this->tags->removeElement($tag);
		}

		return false;
	}

	/**
	 * Contains tag
	 * The original string tag logic used in_array to check it.
	 * This function uses doctrine2 arrayCollection contains function instead.
	 * @param Tag $tag
	 * @return boolean TRUE if this multimedia_object contained the specified tag, FALSE otherwise.
	 */
	public function containsTag(Tag $tag)
	{
		return $this->tags->contains($tag);
	}

	/**
	 * Contains all tags
	 * The original string tag logic used array_intersect and count to check it.
	 * This function uses doctrine2 arrayCollection contains function instead.
	 * @param array $tags
	 * @return boolean TRUE if this multimedia_object contained all tags, FALSE otherwise.
	 */
	public function containsAllTags(array $tags)
	{
		foreach ($tags as $tag) {
			if (!($this->tags->contains($tag))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Contains any tags
	 * The original string tag logic used array_intersect and count to check it.
	 * This function uses doctrine2 arrayCollection contains function instead.
	 * @param array $tags
	 * @return boolean TRUE if this multimedia_object contained any tag of the list, FALSE otherwise.
	 */
	public function containsAnyTag(array $tags)
	{
		foreach ($tags as $tag) {
			if ($this->tags->contains($tag)) {
				return true;
			}
		}

		return false;
	}

	// End of tags section

	/**
	 * Add track
	 *
	 * @param Track $track
	 */
	public function addTrack(Track $track)
	{
		$this->tracks[] = $track;
		$track->setMultimediaObject($this);

		if ($track->getDuration() > $this->getDuration()) {
			$this->setDuration($track->getDuration());
		}

		$track->setRank(count($this->tracks));
	}

	/**
	 * Remove track
	 *
	 * @param Track $track
	 */
	public function removeTrack(Track $track)
	{
		$this->tracks->removeElement($track);
	}

	/**
	 * Contains track
	 *
	 * @param Track $track
	 *
	 * @return boolean
	 */
	public function containsTrack(Track $track)
	{
		return $this->tracks->contains($track);
	}

	/**
	 * Get tracks
	 *
	 * @return ArrayCollection
	 */
	public function getTracks()
	{
		return $this->tracks;
	}

	/**
	 * Get tracks ...
	 *
	 * @param string $tag
	 * @return ArrayCollection
	 * TODO
	 */
	public function getTracksByTag($tag)
	{
		$r = array();

		foreach ($this->tracks as $track) {
			if ($track->containsTag($tag)) {
				$r[] = $track;
			}
		}

		return $r;
	}

	/**
	 * Get track ...
	 *
	 * @param string $tag
	 * @return Track
	 * TODO
	 */
	public function getTrackByTag($tag)
	{
		foreach ($this->tracks as $track) {
			if ($track->containsTag($tag)) {
				return $track;
			}
		}

		return null;
	}

	/**
	 * Get tracks ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getTracksWithAllTags(array $tags)
	{
		$r = array();

		foreach ($this->tracks as $track) {
			if ($track->containsAllTags($tags)) {
				$r[] = $track;
			}
		}

		return $r;
	}

	/**
	 * Get tracks ...
	 *
	 * @param array $tags
	 * @return Track
	 * TODO
	 */
	public function getTrackWithAllTags(array $tags)
	{
		foreach ($this->tracks as $track) {
			if ($track->containsAllTags($tags)) {
				return $track;
			}
		}

		return null;
	}

	/**
	 * Get tracks ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getTracksWithAnyTag(array $tags)
	{
		$r = array();

		foreach ($this->tracks as $track) {
			if ($track->containsAnyTag($tags)) {
				$r[] = $track;
			}
		}

		return $r;
	}

	/**
	 * Get track ...
	 *
	 * @param array $tags
	 * @return Track
	 * TODO
	 */
	public function getTrackWithAnyTag(array $tags)
	{
		foreach ($this->tracks as $track) {
			if ($track->containsAnyTag($tags)) {
				return $track;
			}
		}

		return null;
	}

	/**
	 * Get tracks ...
	 *
	 * @param array $any_tags
	 * @param array $all_tags
	 * @param array $not_any_tags
	 * @param array $not_all_tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getFilteredTracksByTags(
			array $any_tags = array(),
			array $all_tags = array(),
			array $not_any_tags = array(),
			array $not_all_tags = array())
	{
		$r = array();

		foreach ($this->tracks as $track) {
			if($any_tags && !$track->containsAnyTag($any_tags))
				continue;
			if($all_tags && !$track->containsAllTags($all_tags))
				continue;
			if($not_any_tags && $track->containsAnyTag($not_any_tags))
				continue;
			if($not_all_tags && $track->containsAllTags($not_all_tags))
				continue;

			$r[] = $track;
		}

		return $r;
	}

	// End of Track getter - setter etc methods section

	/**
	 * Add pic
	 *
	 * @param Pic $pic
	 */
	public function addPic(Pic $pic)
	{
		$this->pics[] = $pic;
		$pic->setMultimediaObject($this);
		$pic->setRank(count($this->pics));
	}

	/**
	 * Remove pic
	 *
	 * @param Pic $pic
	 */
	public function removePic(Pic $pic)
	{
		$this->pics->removeElement($pic);
	}

	/**
	 * Contains pic
	 *
	 * @param Pic $pic
	 *
	 * @return boolean
	 */
	public function containsPic(Pic $pic)
	{
		return $this->pics->contains($pic);
	}

	/**
	 * Get pics
	 *
	 * @return ArrayCollection
	 */
	public function getPics()
	{
		return $this->pics;
	}

	/**
	 * Get pics ...
	 *
	 * @param string $tag
	 * @return ArrayCollection
	 * TODO
	 */
	public function getPicsByTag($tag)
	{
		$r = array();

		foreach ($this->pics as $pic) {
			if ($pic->containsTag($tag)) {
				$r[] = $pic;
			}
		}

		return $r;
	}

	/**
	 * Get pic ...
	 *
	 * @param string $tag
	 * @return Pic
	 * TODO
	 */
	public function getPicByTag($tag)
	{
		foreach ($this->pics as $pic) {
			if ($pic->containsTag($tag)) {
				return $pic;
			}
		}

		return null;
	}

	/**
	 * Get pics ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getPicsWithAllTags(array $tags)
	{
		$r = array();

		foreach ($this->pics as $pic) {
			if ($pic->containsAllTags($tags)) {
				$r[] = $pic;
			}
		}

		return $r;
	}

	/**
	 * Get pics ...
	 *
	 * @param array $tags
	 * @return Pic
	 * TODO
	 */
	public function getPicWithAllTags(array $tags)
	{
		foreach ($this->pics as $pic) {
			if ($pic->containsAllTags($tags)) {
				return $pic;
			}
		}

		return null;
	}

	/**
	 * Get pics ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getPicsWithAnyTag(array $tags)
	{
		$r = array();

		foreach ($this->pics as $pic) {
			if ($pic->containsAnyTag($tags)) {
				$r[] = $pic;
			}
		}

		return $r;
	}

	/**
	 * Get pic ...
	 *
	 * @param array $tags
	 * @return Pic
	 * TODO
	 */
	public function getPicWithAnyTag(array $tags)
	{
		foreach ($this->pics as $pic) {
			if ($pic->containsAnyTag($tags)) {
				return $pic;
			}
		}

		return null;
	}

	/**
	 * Get pics ...
	 *
	 * @param array $any_tags
	 * @param array $all_tags
	 * @param array $not_any_tags
	 * @param array $not_all_tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getFilteredPicsByTags(
			array $any_tags = array(),
			array $all_tags = array(),
			array $not_any_tags = array(),
			array $not_all_tags = array())
	{
		$r = array();

		foreach ($this->pics as $pic) {
			if($any_tags && !$pic->containsAnyTag($any_tags))
				continue;
			if($all_tags && !$pic->containsAllTags($all_tags))
				continue;
			if($not_any_tags && $pic->containsAnyTag($not_any_tags))
				continue;
			if($not_all_tags && $pic->containsAllTags($not_all_tags))
				continue;

			$r[] = $pic;
		}

		return $r;
	}

	// End of Pic getter - setter etc methods section

	/**
	 * Add material
	 *
	 * @param Material $material
	 */
	public function addMaterial(Material $material)
	{
		$this->materials[] = $material;
		$material->setMultimediaObject($this);
		$material->setRank(count($this->materials));
	}

	/**
	 * Remove material
	 *
	 * @param Material $material
	 */
	public function removeMaterial(Material $material)
	{
		$this->materials->removeElement($material);
	}

	/**
	 * Contains material
	 *
	 * @param Material $material
	 *
	 * @return boolean
	 */
	public function containsMaterial(Material $material)
	{
		return $this->materials->contains($material);
	}

	/**
	 * Get materials
	 *
	 * @return ArrayCollection
	 */
	public function getMaterials()
	{
		return $this->materials;
	}

	/**
	 * Get materials ...
	 *
	 * @param string $tag
	 * @return ArrayCollection
	 * TODO
	 */
	public function getMaterialsByTag($tag)
	{
		$r = array();

		foreach ($this->materials as $material) {
			if ($material->containsTag($tag)) {
				$r[] = $material;
			}
		}

		return $r;
	}

	/**
	 * Get material ...
	 *
	 * @param string $tag
	 * @return Material
	 * TODO
	 */
	public function getMaterialByTag($tag)
	{
		foreach ($this->materials as $material) {
			if ($material->containsTag($tag)) {
				return $material;
			}
		}

		return null;
	}

	/**
	 * Get materials ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getMaterialsWithAllTags(array $tags)
	{
		$r = array();

		foreach ($this->materials as $material) {
			if ($material->containsAllTags($tags)) {
				$r[] = $material;
			}
		}

		return $r;
	}

	/**
	 * Get materials ...
	 *
	 * @param array $tags
	 * @return Material
	 * TODO
	 */
	public function getMaterialWithAllTags(array $tags)
	{
		foreach ($this->materials as $material) {
			if ($material->containsAllTags($tags)) {
				return $material;
			}
		}

		return null;
	}

	/**
	 * Get materials ...
	 *
	 * @param array $tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getMaterialsWithAnyTag(array $tags)
	{
		$r = array();

		foreach ($this->materials as $material) {
			if ($material->containsAnyTag($tags)) {
				$r[] = $material;
			}
		}

		return $r;
	}

	/**
	 * Get material ...
	 *
	 * @param array $tags
	 * @return Material
	 * TODO
	 */
	public function getMaterialWithAnyTag(array $tags)
	{
		foreach ($this->materials as $material) {
			if ($material->containsAnyTag($tags)) {
				return $material;
			}
		}

		return null;
	}

	/**
	 * Get materials ...
	 *
	 * @param array $any_tags
	 * @param array $all_tags
	 * @param array $not_any_tags
	 * @param array $not_all_tags
	 * @return ArrayCollection
	 * TODO
	 */
	public function getFilteredMaterialsByTags(
			array $any_tags = array(),
			array $all_tags = array(),
			array $not_any_tags = array(),
			array $not_all_tags = array())
	{
		$r = array();

		foreach ($this->materials as $material) {
			if($any_tags && !$material->containsAnyTag($any_tags))
				continue;
			if($all_tags && !$material->containsAllTags($all_tags))
				continue;
			if($not_any_tags && $material->containsAnyTag($not_any_tags))
				continue;
			if($not_all_tags && $material->containsAllTags($not_all_tags))
				continue;

			$r[] = $material;
		}

		return $r;
	}

	// End of Material getter - setter etc methods section

	/**
	 * Set rank
	 *
	 * @param integer $rank
	 */
	public function setRank($rank)
	{
		$this->rank = $rank;
	}

	/**
	 * Get rank
	 *
	 * @return integer
	 */
	public function getRank()
	{
		return $this->rank;
	}

	/**
	 * Set status
	 *
	 * @param integer $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Get status
	 *
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Set record_date
	 *
	 * @param DateTime $recordDate
	 */
	public function setRecordDate($recordDate)
	{
		$this->record_date = $recordDate;
	}

	/**
	 * Get record_date
	 *
	 * @return datetime
	 */
	public function getRecordDate()
	{
		return $this->record_date;
	}

	/**
	 * Set public_date
	 *
	 * @param DateTime $publicDate
	 */
	public function setPublicDate($publicDate)
	{
		$this->public_date = $publicDate;
	}

	/**
	 * Get public_date
	 *
	 * @return datetime
	 */
	public function getPublicDate()
	{
		return $this->public_date;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Set subtitle
	 *
	 * @param string $subtitle
	 */
	public function setSubtitle($subtitle)
	{
		$this->subtitle = $subtitle;
	}

	/**
	 * Get subtitle
	 *
	 * @return string
	 */
	public function getSubtitle()
	{
		return $this->subtitle;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set line2
	 *
	 * @param string $line2
	 */
	public function setLine2($line2)
	{
		$this->line2 = $line2;
	}

	/**
	 * Get line2
	 *
	 * @return string
	 */
	public function getLine2()
	{
		return $this->line2;
	}

	/**
	 * Set copyright
	 *
	 * @param string $copyright
	 */
	public function setCopyright($copyright)
	{
		$this->copyright = $copyright;
	}

	/**
	 * Get copyright
	 *
	 * @return string
	 */
	public function getCopyright()
	{
		return $this->copyright;
	}

	/**
	 * Set keyword
	 *
	 * @param string $keyword
	 */
	public function setKeyword($keyword)
	{
		$this->keyword = $keyword;
	}

	/**
	 * Get keyword
	 *
	 * @return string
	 */
	public function getKeyword()
	{
		return $this->keyword;
	}

	/**
	 * Set duration
	 *
	 * @param integer $duration
	 */
	public function setDuration($duration)
	{
		$this->duration = $duration;
	}

	/**
	 * Get duration
	 *
	 * @return integer
	 */
	public function getDuration()
	{
		return $this->duration;
	}
	// End of basic setter & getters

	// Start people_in_multimedia_object section.

	// Caution: these are objects, not strings; ArrayCollection instead of plain php arrays...
	// Note: As this is an auxiliary table, these functions will be scarcely used.
	// Maybe in debug or very specific functions.

	/**
	 * Set people_in_multimedia_object
	 * TO DO: check if ranks should be set to the person_i_m_o objects.
	 * check if the person_i_m_o objects are already assigned to this.
	 *
	 * @param array $people_in_multimedia_object
	 */
	/*public function setPeopleInMultimediaObject(array $people_in_multimedia_object)
	{
		$this->people_in_multimedia_object = new ArrayCollection($people_in_multimedia_object);
	}*/

	/**
	 * Get people_in_multimedia_object
	 *
	 * @return array
	 */
	/*public function getPeopleInMultimediaObject()
	{
		return $this->people_in_multimedia_object;
	}*/

	/**
	 * Add person_in_multimedia_object.
	 *
	 * @param PersonInMultimediaObject $pimo
	 */
	/*public function addPersonInMultimediaObject(PersonInMultimediaObject $pimo)
	{
		// This condition would check the rank, typically it isn't set yet.
		// if (!$this->containsPersonInMultimediaObject($pimo)) {
		if (!$this->containsPersonWithRole( $pimo->getPerson(), $pimo->getRole() ) ) {
			$pimo->setMultimediaObject($this);
			$pimo->setRank(count($this->people_in_multimedia_object));
			$this->people_in_multimedia_object[] = $pimo;

		}
	}*/

	/**
	 * Remove person_in_multimedia_object
	 *
	 * @param PersonInMultimediaObject $pimo
	 * @return boolean TRUE if this multimedia_object contained the specified person_in_multimedia_object, FALSE otherwise.
	 */
	/*public function removePersonInMultimediaObject(PersonInMultimediaObject $pimo)
	{
		if ($this->people_in_multimedia_object->contains($pimo)) {
			return $this->people_in_multimedia_object->removeElement($pimo);
		}

		return false;
	}*/

	/**
	 * Contains person_in_multimedia_object (the whole object, not a given person)
	 * Caution: if input $pimo has no rank set, it will return false.
	 * Use containsPersonWithRole instead.
	 *
	 * @param PersonInMultimediaObject $$pimo
	 * @return boolean TRUE if this multimedia_object contained the specified person_in_multimedia_object, FALSE otherwise.
	 */
	/*public function containsPersonInMultimediaObject(PersonInMultimediaObject $pimo)
	{
		return $this->people_in_multimedia_object->contains($pimo);
	}*/

	// End of people_in_multimedia_object section

	// THE CHICHA STARTS HERE!!!

	/**
	 * Contains person - searches all the PersonInMultimediaObject objects
	 * associated with this MultimediaObject and looks for a given person.
	 * Also checks for the required role if it is included in the call.
	 *
	 * @param Person $person
	 * @param Role $role
	 *
	 * @return boolean
	 */
	/*public function containsPersonWithRole(Person $person, Role $role = null)
	{
		foreach ($this->people_in_multimedia_object as $pimo) {
			if ($pimo->getPerson($person) === $person) {
				if (($role == null) || $role === $pimo->getRole($role)) {
					return true;
				}
			}
		}

		return false;
	}*/

	/**
	 * Get people associated to multimediaobject with a given role.
	 * If the role is zero, it returns all people with a "displayable" role
	 *
	 * @param Role $role
	 */
	/*public function getPeopleInMultimediaObjectByRole(Role $role = null)
	{
		$r = array();

		foreach ($this->people_in_multimedia_object as $pimo) {
			if ( $pimo->getRole()->getDisplay() == true) {
				if ( $role == null || ($pimo->getRole() === $role)) {
					$r[] = $pimo;
				}
			}
		}
		usort($r, function ($a, $b) {
				if ($a->getRank() > $b->getRank()) {
				return 1;
				}
				});

		return $r;
	}*/

	/**
	 * Adds a new PersonInMultimediaObject to the existing people_in_multimedia_object
	 * (ArrayCollection) with the given person and role
	 *
	 *
	 * @param Person $person
	 * @param Role $role
	 */
	/*public function addPersonWithRole(Person $person, Role $role)
	{
		if (!$this->containsPersonWithRole($person, $role)) {
			$pimo = new PersonInMultimediaObject();

			$pimo->setMultimediaObject( $this );
			$pimo->setPerson( $person );
			$pimo->setRole( $role );
			$pimo->setRank(count($this->people_in_multimedia_object));
			$this->people_in_multimedia_object[] = $pimo;
		}
	}*/

	// Igual sirve removePersonInMultimediaObject(PersonInMultimediaObject $pimo)

	/**
	 * Removes a person with a given role
	 *
	 * FIXME: check if it this function is really useful.
	 *
	 * @param Person $person
	 * @param Role $role
	 * @return boolean TRUE if this multimedia_object contained the specified person and role, FALSE otherwise.
	 */
	/*public function removePersonWithRole(Person $person, Role $role)
	{
		foreach ($this->people_in_multimedia_object as $pimo) {
			if ($pimo->getPerson($person) === $person) {
				if (($role == null) || $role === $pimo->getRole($role)) {
					return $this->people_in_multimedia_object->removeElement($pimo);
				}
			}
		}

		return false;
	}*/

	// TO DO: revisar funciones de manejo de arraycollections, igual me acortan alguna de las mías.

	/**
	 * Add people_in_multimedia_object
	 *
	 * @param \Pumukit\SchemaBundle\Document\PersonInMultimediaObject $peopleInMultimediaObject
	 * @return MultimediaObject
	 */
	/*public function addPeopleInMultimediaObject(\Pumukit\SchemaBundle\Document\PersonInMultimediaObject $peopleInMultimediaObject)
	{
		$this->people_in_multimedia_object[] = $peopleInMultimediaObject;

		return $this;
	}*/

	/**
	 * Remove people_in_multimedia_object
	 *
	 * @param \Pumukit\SchemaBundle\Document\PersonInMultimediaObject $peopleInMultimediaObject
	 */
	/*public function removePeopleInMultimediaObject(\Pumukit\SchemaBundle\Document\PersonInMultimediaObject $peopleInMultimediaObject)
	{
		$this->people_in_multimedia_object->removeElement($peopleInMultimediaObject);
	}*/
}
