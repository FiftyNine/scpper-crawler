<?php

namespace ScpCrawler\Wikidot;

class PageStatus
{
    const OK = 0;
    const NOT_FOUND = 1;
    const REDIRECT = 2;
    const FAILED = 3;
    const UNKNOWN = 4;
}