<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;

// SCP database user list class
class UserList extends \ScpCrawler\Wikidot\UserList
{
    // User object class
    protected function getUserClass()
    {
        return '\ScpCrawler\Scp\User';
    }

    // Save list of users to DB
    public function saveMembershipToDB($link, Logger $logger = null)
    {
        $saved = 0;
        $deleted = 0;
        $all = 0;
        $oldMembership = array();
        DbUtils\Membership::select($link, $this->siteId, $oldMembership, true, $logger);
        foreach($this->users as $userId => $usr) {
            if ($usr['Date']) {
                $all++;
                if (isset($oldMembership[$userId])) {
                    unset($oldMembership[$userId]);
                } else {
                    DbUtils\Membership::insert($link, $this->siteId, $userId, $usr['Date'], $logger);
                    $saved++;
                }
            }
        }
        foreach ($oldMembership as $userId => $joinDate) {
            DbUtils\Membership::delete($link, $this->siteId, $userId, $logger);
            $deleted++;
        }
        Logger::logFormat($logger, "Membership saved. Inserted %d, deleted %d, total members %d", array($saved, $deleted, $all));
    }

    // Load membership from DB
    public function loadMembershipFromDB($link, Logger $logger = null)
    {
        foreach($this->users as $userId => $usr) {
            $usr['Date'] = null;
        }
        $membership = array();
        DbUtils\Membership::select($link, $this->siteId, $membership, false, $logger);
        foreach($membership as $userId => $joinDate) {
            $this->users[$userId]['Date'] = $joinDate;
        }
        //Logger::logFormat($logger, "Members loaded: %d", [count($membership)]);
    }

    // Load list of users from DB
    public function loadFromDB($link, Logger $logger = null)
    {
        $res = true;
        $page = new Page($this->getSiteName(), 'system:members');
        if ($page->retrievePageInfo($logger)) {
            $this->siteId = $page->getSiteId();
        } else {
            Logger::logFormat($logger, "Failed to retrieve list of users site Id for site %s", array($this->getSiteName()));
            return false;
        }
        $this->users = array();
        $startTime = microtime(true);
        Logger::log($logger, "::: Loading list of users from DB :::");
        if ($dataset = $link->query("SELECT * FROM ".DbUtils\User::VIEW)) {
            while ($row = $dataset->fetch_assoc()) {
                $user = new User($row);
                $this->addUser($user);
            }
        } else {
            $res = false;
            Logger::logFormat($logger, "Failed to retrieve list of users from DB\nError: \"%s\"", array($link->error()));
        }
        $this->loadMembershipFromDB($link, $logger);
        $total = microtime(true)-$startTime;
        if ($res) {
            $members = 0;
            foreach ($this->users as $userId => $usr) {
                if (isset($usr['Date'])) {
                    $members++;
                }
            }
            Logger::logFormat(
                $logger,
                "::: Success. Loaded %d users (%d members) in %.3f sec :::",
                array(count($this->users), $members, $total)
            );
        } else {
            Logger::logFormat($logger, "::: Fail. Loaded %d users in %.3f sec before failing :::", array(count($this->users, $total)));
        }
    }

    // Save list of users to DB
    public function saveToDB($link, Logger $logger = null)
    {
        $saved = 0;
        $changed = 0;
        $startTime = microtime(true);
        Logger::log($logger, "::: Saving list of users to DB :::");
        try {
            foreach ($this->users as $usr) {
                if ($usr['User']->getModified()) {
                    $changed++;
                    if ($usr['User']->saveToDB($link, $logger)) {
                        $saved++;
                    }
                }
            }
            $this->saveMembershipToDb($link, $logger);
        } catch (Exception $e) {
            Logger::logFormat($logger, "::: Failed. Saved %d of %d changed users\nError:\"%s\" :::", array($saved, $changed, $e->getMessage()));
            throw $e;
        }
        $total = microtime(true)-$startTime;
        Logger::logFormat($logger, "::: Success. Saved %d of %d changed users in %.3f sec :::", array($saved, $changed, $total));
        return true;
    }

    // Remove all membership data, but retain users
    public function clearMembership()
    {
        foreach ($this->users as $userId => &$usr) {
            $usr['Date'] = null;
        }
    }

    // Update from website list of users and membership previously loaded from DB
    public function updateFromSite(Logger $logger = null)
    {
        $this->clearMembership();
        $webList = new UserList($this->getSiteName());
        $webList->retrieveSiteMembers($logger);
        foreach ($webList->users as $userId => $usr) {
            $this->addUser($usr['User'], $usr['Date']);
        }
    }
}