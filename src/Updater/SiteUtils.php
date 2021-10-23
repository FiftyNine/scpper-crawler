<?php

namespace ScpCrawler\Updater;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class SiteUtils
{
    const ROLE_AUTHOR = 1;
    const ROLE_REWRITER = 2;
    const ROLE_TRANSLATOR = 3;

    public static function setContributors(KeepAliveMysqli $link, \ScpCrawler\Scp\Page $page, $role, $users)
    {
        $userIds = array();
        foreach ($users as $user) {
            $userIds[] = (string)($user->getId());
        }
        $link->query(vsprintf("CALL SET_CONTRIBUTORS(%d, %d, '%s')", array($page->getId(), $role, implode(',', $userIds))));
    }

    // Get information about authorship overrides from Alexandra's override page and write it to DB
    public static function updateStatusOverridesEn_Old(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        \ScpCrawler\Scp\UserList $users = null,
        Logger $logger = null
    )
    {
        $html = null;
        \ScpCrawler\Wikidot\Utils::requestPage('05command', 'alexandra-rewrite', $html, $logger);
        if (!$html) {
            return;
        }
        $doc = \phpQuery::newDocument($html);
        $table = pq('div#page-content table.wiki-content-table', $doc);
        if (!$table) {
            return;
        }
        $list = array();
        $i = 0;
        foreach (pq('tr', $table) as $row) {
            if ($i > 0) {
                $pgName = strtolower(pq('td:first', $row)->text());
                $list[$pgName] = pq('td:last', $row)->text();
            }
            $i++;
        }
        $doc->unloadDocument();
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-wiki');
            $pages->loadFromDB($link, $logger);
        }
        if (!$users) {
            $users = new \ScpCrawler\Scp\UserList('scp-wiki');
            $users->loadFromDB($link, $logger);
        }
        $saved = 0;
        foreach ($list as $pageName => $override) {
            $ovStr = explode(':', $override);
            $page = $pages->getPageByName($pageName);
            $userName = ($ovStr[0] == '') ? $ovStr[2] : $ovStr[0];
            if ($userName == 'Unknown Author') {
                $user = $users->getUserById(-1);
            } else {
                $user = $users->getUserByDisplayName($userName);
            }
            $status = ($ovStr[0] == '') ? $ovStr[1] : 'rewrite';
            if ($page && $user) {
                if ($status == 'rewrite') {
                    self::setContributors($link, $page, self::ROLE_REWRITER, array($user));
                } else {
                    self::setContributors($link, $page, self::ROLE_AUTHOR, array($user));
                }
                $saved++;
            } else {
                if (!$page) {
                    Logger::logFormat($logger, 'Overriden page "%s" not found', array($pageName));
                }
                if (!$user) {
                    Logger::logFormat($logger, 'Overriden author "%s" not found', array($userName));
                }
            }
        }
        Logger::logFormat($logger, "::: Author overrides updates, %d entries saved (%d total) :::", array($saved, count($list)));
    }

    // Get information about authorship overrides from attribution page and write it to DB
    public static function updateStatusOverridesEn(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        \ScpCrawler\Scp\UserList $users = null,
        Logger $logger = null
    )
    {
        $html = null;
        \ScpCrawler\Wikidot\Utils::requestPage('scp-wiki', 'attribution-metadata', $html, $logger);
        if (!$html) {
            return;
        }
        $doc = \phpQuery::newDocument($html);
        $table = pq('div#page-content table.wiki-content-table', $doc);
        if (!$table) {
            return;
        }
        $list = array();
        $i = 0;
        foreach (pq('tr', $table) as $row) {
            if ($i > 0) {
                $pgName = strtolower(pq('td:first-child', $row)->text());
                $type = pq('td:nth-child(3)', $row)->text();
                if (!array_key_exists($pgName, $list)) {
                    $list[$pgName] = array();
                }
                if (!array_key_exists($type, $list[$pgName])) {
                    $list[$pgName][$type] = array();
                }
                $list[$pgName][$type][] = array(
                    'user' => pq('td:nth-child(2)', $row)->text(),
                    'date' => pq('td:last-child', $row)->text()
                );
            }
            $i++;
        }
        $doc->unloadDocument();
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-wiki');
            $pages->loadFromDB($link, $logger);
        }
        if (!$users) {
            $users = new \ScpCrawler\Scp\UserList('scp-wiki');
            $users->loadFromDB($link, $logger);
        }
        $saved = 0;
        $nonDefault = 0;
        foreach ($list as $pageName => $overrideTypes) {
            if (strpos($pageName, ':') !== FALSE) {
                $nonDefault++;
                continue;
            } else {
                $page = $pages->getPageByName($pageName);
                if (!$page) {
                    Logger::logFormat($logger, 'Overriden page "%s" not found', array($pageName));
                    continue;
                }
            }
            foreach ($overrideTypes as $type => $overrides) {
                $ovUsers = array();
                foreach ($overrides as $override) {
                    if ($override['user'] == 'Unknown Author') {
                        $user = $users->getUserById(-1);
                    } else {
                        $user = $users->getUserByDisplayName($override['user']);
                    }
                    if (!$user) {
                        Logger::logFormat($logger, 'Overriden author "%s" not found', array($override['user']));
                        continue;
                    } else {
                        $ovUsers[] = $user;
                    }
                }
                if (count($ovUsers) == 0) {
                    continue;
                }
                switch ($type) {
                    case 'rewrite':
                        self::setContributors($link, $page, self::ROLE_REWRITER, $ovUsers);
                        break;
                    case 'translator':
                        self::setContributors($link, $page, self::ROLE_TRANSLATOR, $ovUsers);
                        break;
                    case 'author':
                        self::setContributors($link, $page, self::ROLE_AUTHOR, $ovUsers);
                        break;
                    default:
                        Logger::logFormat($logger, 'Unknown role "%s" for page "%s"', array($type, $pageName));
                }
                $saved++;
            }
        }        
        Logger::logFormat($logger, "::: Author overrides updates, %d entries saved, %d non-defaults skipped (%d total) :::", array($saved, $nonDefault, count($list)));
    }

    //
    private static function updateAltTitlesFromPage(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages,
        $wiki,
        $listPage,
        $pattern,
        Logger $logger = null
    )
    {
        $html = null;
        \ScpCrawler\Wikidot\Utils::requestPage($wiki, $listPage, $html, $logger);
        if (!$html) {
            return;
        }
        $doc = \phpQuery::newDocument($html);
        $i = 0;
        foreach (pq('div#page-content li', $doc) as $row) {
            $a = pq('a', $row);
            $pageName = substr($a->attr('href'), 1);
            if (preg_match($pattern, $pageName)) {
                $rowText = mb_convert_encoding($row->textContent, "UTF-8");
                $pageTitle = mb_convert_encoding($a->text(), "UTF-8");
                $altTitle = mb_substr($rowText, mb_strlen($pageName, "UTF-8")+3, NULL, "UTF-8");
                $page = $pages->getPageByName($pageName);
                if ($page) {
                    $page->setProperty('altTitle', $altTitle);
                    if ($page->getModified()) {
                        $page->saveToDB($link, $logger);
                        $i++;
                    }
                }
            }
        }
        $doc->unloadDocument();
        return $i;
    }

    // Update alternative titles for SCPs
    public static function updateAltTitlesEn(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-wiki');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'archived-scps', '/scp-\d{3,4}-arc/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }
    
    public static function updateAltTitlesRu(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-ru');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-ru', '/scp-\d{3,4}-ru/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-fr', '/scp-\d{3,4}-fr/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-jp', '/scp-\d{3,4}-jp/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-es', '/scp-\d{3,4}-es/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-pl', '/scp-\d{3,4}-pl/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-de', '/scp-\d{3,4}-de/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'scp-list-j', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'archive', '/scp-\d{3,4}-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ru', 'explained-list', '/scp-\d{3,4}-.+/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }
    
    public static function updateAltTitlesKr(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-kr');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-series-ko', '/scp-\d{3,4}-ko/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'joke-scps-ko', '/scp-.+/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-kr', 'scp-ko-ex', '/scp-\d{3,4}-ko-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }
    
    public static function updateAltTitlesCn(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-wiki-cn');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-6', '/scp-\d{3,4}/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-cn', '/scp-cn-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-series-cn-2', '/scp-cn-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'joke-scps-cn', '/scp-cn-.+/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-cn', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }
    
    public static function updateAltTitlesFr(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('fondationscp');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'liste-francaise', '/scp-\d{3,4}-fr/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scps-humoristiques-francais', '/scp-.+/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondationscp', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }
    
    public static function updateAltTitlesPl(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-pl');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-eng', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-eng-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-eng-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-eng-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-eng-5', '/scp-\d{3,4}/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'lista-pl', '/scp-pl-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pl', 'joke', '/scp-.+/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }    

    public static function updateAltTitlesEs(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('lafundacionscp');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'serie-scp-es', '/scp-es-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'serie-scp-es-2', '/scp-es-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'serie-scp-es-3', '/scp-es-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scps-humoristicos', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'lafundacionscp', 'scps-exs', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }

    
    public static function updateAltTitlesTh(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-th');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'scp-series-th', '/scp-\d{3,4}-th/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-th', 'joke-scps-th', '/scp-.+/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }    
        
    public static function updateAltTitlesJp(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-jp');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-jp', '/scp-\d{3,4}-jp/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-jp-2', '/scp-\d{3,4}-jp/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-series-jp-3', '/scp-\d{3,4}-jp/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'joke-scps-jp', '/scp-.+/i', $logger);        
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-jp', 'scp-jp-ex', '/scp-\d{3,4}-jp-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }    
    
    public static function updateAltTitlesDe(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-wiki-de');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series-4', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series-5', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-series-6', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-de', '/scp-\d{3,4}-de/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-wiki-de', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }        

    public static function updateAltTitlesIt(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('fondazionescp');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-series-4', '/scp-\d{3,4}/i', $logger);       
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-series-5', '/scp-\d{3,4}/i', $logger);       
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-it-serie-i', '/scp-\d{3,4}-it/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'fondazionescp', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }            

    public static function updateAltTitlesUa(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-ukrainian');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-4', '/scp-\d{3,4}/i', $logger);       
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-5', '/scp-\d{3,4}/i', $logger);       
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-6', '/scp-\d{3,4}/i', $logger);               
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-series-ua', '/scp-\d{3,4}-ua/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'scp-list-j', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-ukrainian', 'explained-list', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }  

    public static function updateAltTitlesPt(
        KeepAliveMysqli $link,
        \ScpCrawler\Scp\PageList $pages = null,
        Logger $logger = null
    )
    {
        if (!$pages) {
            $pages = new \ScpCrawler\Scp\PageList('scp-pt-br');
            $pages->loadFromDB($link, $logger);
        }
        $total = 0;
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-series', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-series-2', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-series-3', '/scp-\d{3,4}/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-series-4', '/scp-\d{3,4}/i', $logger);       
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-series-5', '/scp-\d{3,4}/i', $logger);               
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'series-1-pt', '/scp-\d{3,4}-pt/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'joke-scps', '/scp-.+/i', $logger);
        $total += self::updateAltTitlesFromPage($link, $pages, 'scp-pt-br', 'scp-ex', '/scp-\d{3,4}-ex/i', $logger);
        Logger::logFormat($logger, 'Updated alternative titles for %d pages', [$total]);
    }    
}
