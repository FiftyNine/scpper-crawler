<?php

namespace ScpCrawler\Wikidot;

// Class with properties of a single category
class Category
{
    /*** Fields ***/
    // Wikidot category id
    protected $categoryId;
    // Wikidot site id
    protected $siteId;
    // Category name
    protected $name;
    // Ignore pages of this category
    protected $ignored;
    
    /*** Public ***/
    public function __construct($categoryId, $name)
    {
        $this->categoryId = $categoryId;
        $this->name = $name;
    }

    /*** Access methods ***/
    // Wikidot category id
    public function getId()
    {
        return $this->categoryId;
    }

    // Wikidot site id
    public function getSiteId()
    {
        return $this->siteId;
    }

    // Category name
    public function getName()
    {
        return $this->name;
    }

    // Ignore pages of this category
    public function getIgnored()
    {
        return $this->ignored;
    }
}