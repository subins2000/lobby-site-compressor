<?php
ini_set('max_execution_time', 0);
set_time_limit(0);

require_once $this->dir . "/src/inc/class.site-compressor.php";

require_once $this->dir . "/src/inc/min-css.php";
require_once $this->dir . "/src/inc/jshrink.php";
require_once $this->dir . "/src/inc/html-compress.php";

$this->data->remove("compress-msg");
$this->data->remove("log");

/**
 * $siteID is auto defined by passing key=value pair to --data parameter
 */

$SC = new Lobby\App\site_compressor\SiteCompressor($this);
$SC->setOptions($this->getSiteInfo($siteID));
$SC->checkOptions();
$SC->startCompress();
