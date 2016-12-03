<?php
$siteID = Request::postParam("siteID");
$siteInfo = $this->getSiteInfo($siteID);

if($siteInfo){
  $history = $this->data->getArray("log");
  if(!isset($history["finished"])){
    echo "running";
    return;
  }

  $this->data->remove("log");

  $Process = new Fr\Process(Fr\Process::getPHPExecutable(), array(
    "arguments" => array(
      0 => L_DIR . '/lobby.php',
      1 => "app",
      "--a" => "site-compressor",
      "--i" => "src/ar/compress-bg.php",
      "--data" => "siteID=$siteID"
    )
  ));

  $that = $this;
  $command = $Process->start(function() use ($that){
    echo "started";
  });
  $this->log("Command executed for compression : $command");
}
