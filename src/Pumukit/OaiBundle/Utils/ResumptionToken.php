<?php

namespace Pumukit\OaiBundle\Utils;

use DateTime;

/**
 * OAI ResumptionToken immutable class (No setters).
 */
class ResumptionToken
{
    private $offset;
    private $from;
    private $until;
    private $metadataPrefix;
    private $set;

    /**
     * ResumptionToken class construct.
     *
     * @param int      $offset
     * @param DateTime $from
     * @param DateTime $until
     * @param string   $metadataPrefix
     * @param string   $set
     */
    public function __construct($offset = 0, DateTime $from = null, DateTime $until = null, $metadataPrefix = null, $set = null)
    {
        $this->offset = $offset;
        $this->from = $from;
        $this->until = $until;
        $this->metadataPrefix = $metadataPrefix;
        $this->set = $set;
    }

    /**
     * Get the offset.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Get the from.
     *
     * @return DateTime|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get the until.
     *
     * @return DateTime|null
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * Get the metadata prefix.
     *
     * @return string|null
     */
    public function getMetadataPrefix()
    {
        return $this->metadataPrefix;
    }

    /**
     * Get the set.
     *
     * @return string|null
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
     * Get the token of the ResumptionToken.
     *
     * @return string
     */
    public function encode()
    {
        $params = [];
        $params['offset'] = $this->offset;
        $params['metadataPrefix'] = $this->metadataPrefix;
        $params['set'] = $this->set;
        $params['from'] = null;
        $params['until'] = null;

        if ($this->from) {
            $params['from'] = $this->from->getTimestamp();
        }

        if ($this->until) {
            $params['until'] = $this->until->getTimestamp();
        }

        return base64_encode(json_encode($params));
    }

    /**
     * Return next ResumptionToken with same parameters.
     */
    public function next()
    {
        $next = clone $this;
        ++$next->offset;

        return $next;
    }

    /**
     * Factory method to create a new ResumptionToken from a token.
     *
     * @param token string
     *
     * @return ResumptionToken
     */
    public static function decode($token)
    {
        $base64Decode = base64_decode($token, true);
        if (false === $base64Decode) {
            throw new ResumptionTokenException('base64_decode error');
        }
        $params = (array) json_decode(base64_decode($token, true));

        if (json_last_error()) {
            throw new ResumptionTokenException('json_decode error');
        }

        if (!empty($params['from'])) {
            $params['from'] = new DateTime('@'.$params['from']);
        }

        if (!empty($params['until'])) {
            $params['until'] = new DateTime('@'.$params['until']);
        }

        return new self($params['offset'], $params['from'], $params['until'], $params['metadataPrefix'], $params['set']);
    }
}
