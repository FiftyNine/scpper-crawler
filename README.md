# scpper/crawler

This is a PHP-cli based appliction that updates an instance of scpper/db by crawling over a Wikidot site (i.e. scp-wiki.wikidot.com), fetching pages, parsing them and saving collected data into a database.

## As local copy  
  #### Requirements
 - PHP 7.4
 - [Composer](https://getcomposer.org/download/)
 - An instance of a MySQL scpper/db database
 - [Optional] [Pthreads](https://www.php.net/manual/en/book.pthreads.php) for thread-based implementation of concurrency. 
  #### Instructions
  - Define following environmental variables:
    - DB_HOST - host name of the MySQL server
    - DB_PORT - port of the MySQL server, usually it's 3306
    - DB_NAME - database to be updated
    - DB_USER - MySQL user that has write and execute rights for the specified database
    - DB_PASSWORD - password for the specified user
  - run `composer update`
  - Launch run.php to start an update, like so `php run.php BRANCH [CONCURRENCY]`
    - Argument BRANCH specifies a Wikidot site to be crawled, i.e. scp-wiki, scp-vn, etc. Selected site must already be present in the table "sites" of the database.
    - Optional Argument CONCURRENCY can be one of the two options:
      - --threads=N, to launch a Pthread-based implementation of concurrency in N threads (not compatible with sites that use TLS/SSL).
      - --processes=N, to launch a process-based implementation of concurrenct in N processes (significantly slower and more memory demanding)
      - If the second argument is omitted, the update will run in a single thread.
## As Docker container
  - Define following environmental variables for a container:
    - DB_HOST - host name of the MySQL server
    - DB_PORT - port of the MySQL server, usually it's 3306
    - DB_NAME - database to be updated
    - DB_USER - MySQL user that has write and execute rights for the specified database
    - DB_PASSWORD - password for the specified user
    - CRAWLER_BRANCH - a Wikidot site to be crawled (scp-wiki, scp-vn, etc.)
  - Note that provided dockerfile uses PHP implementation that doesn't support threads and launches an update in a single thread. 

Author: Alexander "FiftyNine" Krivopalov  
E-mail: admin@scpper.com