<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ScpCrawler\Updater\Processes;

class SiteUpdater extends \ScpCrawler\Updater\SiteUpdater
{
    protected function getPagesUpdaterClass()
    {
        return '\ScpCrawler\Updater\Processes\PagesUpdater';
    }

    protected function getUsersUpdaterClass()
    {
        return '\ScpCrawler\Updater\Processes\UsersUpdater';
    }
}