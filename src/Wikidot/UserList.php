<?php

namespace ScpCrawler\Wikidot;

use ScpCrawler\Logger\Logger;

// List of wikidot user accounts
class UserList
{
    /*** Fields ***/
    // Wikidot name of site
    private $siteName;
    // Wikidot id of site
    protected $siteId;
    // Array of (UserId (int) => array('User' => User, 'JoinDate' => \DateTime)
    protected $users;

    /*** Protected ***/
    // Name of class used for user objects
    protected function getUserClass()
    {
        return '\ScpCrawler\Wikidot\User';
    }

    /*** Public ***/
    public function __construct($siteName)
    {
        $this->siteName = $siteName;
        $this->users = array();
    }


    // Add members from a MemberList page
    public function addMembersFromListPage($html, Logger $logger = null)
    {
        $success = 0;
        $doc = \phpQuery::newDocument($html);
        foreach (pq('tr') as $row) {
            $userClass = $this->getUserClass();
            $user = new $userClass();
            if ($user->extractFrom(pq('span.printuser', $row))) {
                $joinDate = \ScpCrawler\Wikidot\Utils::extractDateTime(pq('span.odate', $row));
                //$user->setJoinDate($siteId, $joinDate);
                $this->addUser($user, $joinDate);
                $success++;
            }
        }
        $doc->unloadDocument();
        return $success;
    }

    // Load members list for a specified site and add all users from it
    public function retrieveSiteMembers(Logger $logger = null)
    {
        $res = true;
        $success = 0;
        $startTime = microtime(true);
        $membersHtml = null;
        Logger::logFormat($logger, "::: Retrieving list of members from %s.wikidot.com :::", array($this->siteName));
        try {
            \ScpCrawler\Wikidot\Utils::requestPage($this->siteName, 'system:members', $membersHtml, $logger);
            $this->siteId = \ScpCrawler\Wikidot\Utils::extractSiteId($membersHtml);
            if ($membersHtml && $this->siteId) {
                // $pageCount = \ScpCrawler\Wikidot\Utils::extractPageCount($membersHtml);
                $args = array(
                    'per_page' => 1000000,
                );
                $totaltime = microtime(true);
                $processingtime = 0;
                $memberList = \ScpCrawler\Wikidot\Utils::iteratePagedModule($this->siteName, 'membership/MembersListModule', 0, $args, null /*range(1, $pageCount)*/, $logger);
                foreach ($memberList as $mlPage) {
                    $time_start = microtime(true);
                    if ($mlPage) {
                        $loaded = $this->addMembersFromListPage($mlPage, $logger);
                        if (($success + $loaded) % 1000 > $success % 1000) {
                            Logger::logFormat(
                                $logger,
                                "%d members retrieved [%d kb used]...",
                                array(($success+$loaded) % 1000 * 1000, round(memory_get_usage()/1024))
                            );
                        }
                        $success = $success + $loaded;
                            // return;
                    }
                    else {
                        $res = false;
                        break;
                    }
                    $time_end = microtime(true);
                    $processingtime += $time_end - $time_start;
					// debug
//					sleep(1);
                }
                echo 'Total time : '.(microtime(true) - $totaltime).' sec., processing time : '.$processingtime.'\n';
            }
        } catch (Exception $e) {
            Logger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            throw $e;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            Logger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Time: %.3f sec :::",
                array($success, $totalTime)
            );
        } else {
            Logger::logFormat(
                $logger,
                "::: Failed. Retrieved: %d. Time: %.3f sec :::",
                array($success, $totalTime)
            );
        }
        return $res;
    }

    // Add a user to the list
    public function addUser(User $user, \DateTime $date = null)
    {
        $usr = array('User' => $user, 'Date' => $date);
        if (array_key_exists($user->getId(), $this->users)) {
            $old = &$this->users[$user->getId()];
            $old['User']->updateFrom($user);
            if ($date) {
                $old['Date'] = $date;
            }
        } else {
            $this->users[$user->getId()] = array('User' => $user, 'Date' => $date);
        }
    }

    // Add users associated with page
    public function addUsersFromPage(Page $page)
    {
        foreach($page->getRetrievedUsers() as $userId => $user) {
            $this->addUser($user);
        }
    }

    // Wikidot name of site
    public function getSiteName()
    {
        return $this->siteName;
    }

    // Get user by it's WikidotId
    public function getUserById($id)
    {
        if (isset($this->users[$id])) {
            return $this->users[$id]['User'];
        } else {
            return null;
        }
    }

    // Get user by display name
    public function getUserByDisplayName($name)
    {
        $result = null;
        foreach ($this->users as $user) {
            if ($user['User']->getDisplayName() == $name) {
                if (!$result || $result->getDeleted() && !$user['User']->getDeleted()) {
                    $result = $user['User'];
                }                
            }
        }
        return $result;
    }

    public function getUsers()
    {
        return $this->users;
    }
}