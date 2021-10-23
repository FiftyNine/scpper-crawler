<?php

namespace ScpCrawler\Wikidot;

// Wikidot user account
class User
{
    /*** Fields ***/
    // Wikidot id
    private $userId;
    // Deleted account
    private $deleted;
    // Displayed name, e.g. "Kain Pathos Crow"
    private $displayName;
    // Wikidot inner name used in links, e.g. "kain-pathos-crow"
    private $wikidotName;

    // Set value by property name
    protected function setProperty($name, $value)
    {
        if ($name == "userId" && is_int($value) && $this->userId !== $value) {
            $this->userId = $value;
            $this->changed();
        } elseif ($name == "deleted" && is_bool($value) && $this->deleted !== $value) {
            $this->deleted = $value;
            $this->changed();
        } elseif ($name == "displayName" && is_string($value) && $this->displayName !== $value) {
            $this->displayName = $value;
            $this->changed();
        } elseif ($name == "wikidotName" && is_string($value) && $this->wikidotName !== $value) {
            $this->wikidotName = $value;
            $this->changed();
        }
    }

    // Informs the object that it was changed
    protected function changed()
    {
        // Do nothing
    }

    /*** Public ***/

    // Extract information about user from a html element
    public function extractFrom(\phpQueryObject $elem)
    {
        if ($elem && $elem->hasClass('printuser')) {
            if ($elem->hasClass('avatarhover')) {
                if ($link = pq('a:last', $elem)) {
                    if (preg_match('/\d+/', $link->attr('onclick'), $matches)) {
                        $this->setProperty('userId', (int)$matches[0]);
                        $this->setProperty('deleted', false);
                        $temp = explode('/', $link->attr('href'));
                        $this->setProperty('wikidotName', end($temp));
                        $this->setProperty('displayName', $link->text());
                        return true;
                    }
                }
            } elseif ($elem->hasClass('deleted')) {
                $this->setProperty('userId', (int)$elem->attr('data-id'));
                $this->setProperty('deleted', true);
                $this->setProperty('wikidotName', 'deleted-account');
                $this->setProperty('displayName', 'Deleted Account');
                return true;
            } elseif ($elem->hasClass('anonymous')) {
                $this->setProperty('userId', -1);
                $this->setProperty('deleted', true);
                $this->setProperty('wikidotName', 'anonymous-user');
                $this->setProperty('displayName', 'Anonymous User');
                return true;
            }
        }
        return false;
    }

    // Add information from a different object of the same class and $id
    public function updateFrom(User $user)
    {
        if (!$user || ($user->getId() != $this->getId()))
            return;
        if ($user->deleted) {
            $this->setProperty('$deleted', true);
        } else {
            if ($user->displayName) {
                $this->setProperty('displayName', $user->displayName);
            }
            if ($user->wikidotName) {
                $this->setProperty('wikidotName', $user->wikidotName);
            }
        }
    }

    /*** Access methods ***/
    // Returns id
    public function getId()
    {
        return $this->userId;
    }

    // Is account deleted
    public function getDeleted()
    {
        return $this->deleted;
    }

    // Displayed name, e.g. "Kain Pathos Crow"
    public function getDisplayName()
    {
        return $this->displayName;
    }

    // Wikidot inner name used in links, e.g. "kain-pathos-crow"
    public function getWikidotName()
    {
        return $this->wikidotName;
    }
}