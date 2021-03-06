<?php
namespace Lobby\App;

class site_compressor extends \Lobby\App {

  public function page($page){
    if($page === "/html" || $page === "/css" || $page === "/js"){
      $this->setTitle("Compress ". strtoupper(substr($page, 1)));
    }
    return "auto";
  }

  public function routes(){
    return array(
      "/site[:sites]?/[a:siteID]?/[:page]?" => function($app, $request){
        /**
         * Page is "/sites"
         */
        if($request->sites === "s"){
          echo $app->inc("src/view/site.php", array(
            "siteID" => null,
            "page" => $request->siteID
          ));
        }else{
          echo $app->inc("src/view/site.php", array(
            "siteID" => $request->siteID,
            "page" => $request->page
          ));
        }
      }
    );
  }

  public function getSiteInfo($siteID){
    $siteInfo = $this->data->getArray("site-$siteID");
    if(empty($siteInfo))
      return false;

    $siteInfo = array_replace_recursive($siteInfo, array(
      "id" => $siteID,
      "lastCompressed" => 0,
      "replace" => $this->data->getArray("$siteID-replacer")
    ));
    return $siteInfo;
  }

  /**
   * On App update
   */
  public function onUpdate($version, $oldVersion = null){
    if($oldVersion !== null && $oldVersion < "0.4.1"){
      $saves = $this->data->getValue("", "site-compressor");
      foreach($saves as $save){
        $siteInfo = json_decode($save['value'], true);

        $siteInfo = array(
          "name" => $save['name'],
          "src" => $siteInfo["main"]["siteLoc"],
          "out" => $siteInfo["main"]["siteOutput"],
          "minHTML" => (int) ($siteInfo["main"]["minHtml"] !== ""),
          "minPHP" => (int) ($siteInfo["main"]["minPHP"] !== ""),
          "noComments" => (int) ($siteInfo["main"]["noComments"] !== ""),
          "minCSS" => (int) ($siteInfo["main"]["minCss"] !== ""),
          "minJS" => (int) ($siteInfo["main"]["minJs"] !== ""),
          "minInline" => (int) ($siteInfo["main"]["minInline"] !== ""),
          "skipMinFiles" => 1,
        );

        $siteID = strtolower(preg_replace('/[^\da-z]/i', '', $siteInfo["name"]));
        $this->data->saveArray("site-$siteID", $siteInfo);

        $this->data->saveArray("sites", array(
          $siteID => $siteInfo["name"]
        ));

        if(isset($siteInfo["replacer"]) && !empty($siteInfo["replacer"])){
          $this->data->saveArray("$siteID-replacer", $siteInfo["replacer"]);
        }
      }
    }
  }

  public function refreshAssets($siteInfo){
    $siteID = $siteInfo["id"];
    $src = $siteInfo["src"];

    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::CURRENT_AS_SELF), \RecursiveIteratorIterator::SELF_FIRST);
    $files = array();

    foreach($iterator as $location => $object) {
      $relativePath = str_replace($src . DIRECTORY_SEPARATOR, "", $location);

      if(!$object->isDot() && $object->isFile())
        $files[self::getMIMEType($location)][] = $relativePath;
    }
    $this->data->remove("$siteID-assets");
    $this->data->saveArray("$siteID-assets", $files);
  }

  public function findMinFiles($src){
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src), \RecursiveIteratorIterator::SELF_FIRST);
    $files = array();

    foreach($iterator as $location => $object) {
      $relativePath = str_replace($src . DIRECTORY_SEPARATOR, "", $location);

      if(preg_match("/\.min\./", $object->getFilename()))
        $files[] = $relativePath;
    }
    return $files;
  }

  /**
   * http://subinsb.com/php-find-file-mime-type
   */
  public static function getMIMEType($path) {
    $finfo = new \finfo;
    $mime  = $finfo->file($path, FILEINFO_MIME_TYPE);

    /**
     * MIME Type is text/plain for .js and .css files, so we determine file from extension
     */
    if($mime === "text/plain" || $mime === "text/html") {
      $dots      = explode(".", $path);
      $extension = strtolower($dots[count($dots) - 1]);
      if($extension === "js") {
        $mime = "application/javascript";
      } else if($extension === "css") {
        $mime = "text/css";
      }
    }
    return $mime;
  }

}
?>
