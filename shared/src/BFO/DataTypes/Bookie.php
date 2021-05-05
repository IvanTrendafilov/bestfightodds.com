<?php

namespace BFO\DataTypes;

/**
 * Bookie class
 */
class Bookie
{
    private $id;
    private $name;
    private $url;
    private $affiliate_url;

    /**
     * Constructor
     *
     * @param int $id ID
     * @param string $name Name
     * @param string $url URL
     * @param string $affiliate_url Affiliate URL
     */
    public function __construct($id, $name, $url, $affiliate_url)
    {
        $this->id = $id;
        $this->name = $name;
        $this->url = $url;
        $this->affiliate_url = $affiliate_url;
    }

    /**
     * Get ID
     *
     * @return int ID
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string Name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get URL
     *
     * @return string URL
     */
    public function getURL()
    {
        return $this->url;
    }

    /**
     * Get Affiliate URL
     *
     * @return string Affiliate URL
     */
    public function getRefURL()
    {
        return $this->affiliate_url;
    }
}
