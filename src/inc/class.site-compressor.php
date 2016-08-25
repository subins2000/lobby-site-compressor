<?php
namespace Lobby\App\site_compressor;

class SiteCompressor {

  /**
   * Path to directory where compression classes are stored
   */
  private $root;
  
  /**
   * The current settings for compression
   */
  private $options = array();
  
  /**
   * List of files in the site according to file type
   */
  private $files = array();
  
  /**
   * Array of files and it's contents already read
   */
  private $fileReadCache = array();
  
  /**
   * The site's ID
   */
  private $siteID = null;
  
  private $statusID = 0;
  
  private $startTime = 0;
  
  private $defaultOptions = array(
    /**
     * Site settings
     */
    "src" => "",
    "out" => "",
    "beforeCMD" => "",
    "afterCMD" => "",
    
    /**
     * Compression settings
     */
    "minHTML" => false,
    "minPHP" => false,
    "noComments" => false,
    "minCSS" => false,
    "minJS" => false,
    "minInline" => false,
    "skipMinFiles" => true,
    
    "replace" => array()
  );
  
  /**
   * The Lobby\App\site_compressor object
   */
  private $app = null;
  
  public function __construct($app) {
    $this->app = $app;
  }
  
  /**
   * Set the settings
   */
  public function setOptions($options) {
    $this->options = array_replace_recursive($this->defaultOptions, $options);
    $this->siteID = $this->options["id"];
  }
  
  /**
   * Check Options to see if they're right
   */
  public function checkOptions() {
    if($this->options["src"] == null) {
      $this->ser("Site Location Not Given", "The absolute path of the site locations is not given");
    } else if($this->options["out"] == null) {
      $this->ser("Output Location Not Given", "The output path where the compressed site is written is not given");
    } else if(!is_dir($this->options["src"])) {
      $this->ser("Site Location Not Found", "The site location path <b>{$this->options["src"]}</b> was not found");
    } else if(!file_exists($this->options["out"])) {
      $this->ser("Site Location Not Found", "The site output path was not found");
    } else if(!is_writable($this->options["out"])) {
      $this->ser("Output Path not writable", "The output path given is not writable for me. Set the permission of the output folder to Read & Write (0755)");
    } else if(!is_readable($this->options["src"])) {
      $this->ser("Site Path Not Readable", "The site path given is not readable for me. Set the permission of the site folder to Read (444)");
    }
  }
  
  /**
   * Start Compressing
   */
  public function startCompress() {
    $this->startTime = microtime(true);
    
    $src    = $this->options["src"];
    $output = $this->options["out"];
    
    /**
     * List of files not needed to compress
     */
    $skipAssets = $this->app->getJSONData("{$this->siteID}-skip-assets");
    
    $this->status("Compression inited");
    
    /**
     * Callback before compression
     */
    if($this->options["beforeCMD"] != "") {
      $this->status("Executing Terminal Command");
      exec($this->options["beforeCMD"]);
    }
    
    $this->status("Emptying Output Directory");
    /**
     * Empty the Output Dir just in case
     */
    $this->recursiveRemoveDirectory($output);
    $this->status("Emptied Output Directory");
    
    /**
     * Browse through the folder and
     * make an array of found files
     */
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src), \RecursiveIteratorIterator::SELF_FIRST);
    $this->status("Copying approximately <b>". iterator_count($iterator) ."</b> files to output directory");
    
    foreach($iterator as $location => $object) {
      $relativePath = str_replace($src . DIRECTORY_SEPARATOR, "", $location);
      
      if($relativePath !== "." && $relativePath !== ".."){
        $outLoc = "$output/$relativePath";
        if($object->isFile()) {
          if(!in_array($relativePath, $skipAssets)){
            $type                 = $this->app::getMIMEType($location);
            $this->files[$type][] = $relativePath;
          }
          copy($location, $outLoc);
        } else if($object->isDir() && !file_exists($outLoc)) {
          /**
           * Make sub directories in output folder
           */
          mkdir($outLoc);
        }
      }
    }
    
    if(empty($this->files)) {
      $this->ser("No Files Found", "No files were found in the site directory to compress.");
    } else {
      $this->status("Started Compressing");
      
      /* Replace strings */
      if(!empty($this->options["replace"])) {
        $this->replaceStrings();
      }
      
      /* We will proceed only if HTML, CSS, JS files are found */
      
      /* Start Compressing JS */
      if($this->options["minJS"] && isset($this->files["application/javascript"])) {
        $this->compressJS();
      }
      
      /* Start Compressing CSS */
      if($this->options["minCSS"] && isset($this->files["text/css"])) {
        $this->compressCSS();
      }
      
      /* Start Compressing HTML, PHP */
      if($this->options["minHTML"] && isset($this->files["text/html"])) {
        $this->compressHTML();
      }
      
      /* Execute after commands */
      if($this->options["afterCMD"] != "") {
        $this->status("Executing Terminal Command");
        exec($this->options["afterCMD"]);
      }
      $this->app->saveJSONData("log", array(
        "finished" => round(microtime(true) - $this->startTime, 4)
      ));
    }
  }
  
  /**
   * Show errors in HTML format
   */
  public function ser($title, $description = "") {
    $this->app->saveData("compress-msg", ser($title, $description));
    exit;
  }
  
  /**
   * Show success messages in HTML format
   */
  public function sss($title, $description = "") {
    $this->app->saveData("compress-msg", sss($title, $description));
  }
  
  /**
   * Publish status
   */
  public function status($msg) {
    $msg = \Lobby\Time::now("H:i:s") . " - " . $msg;
    $this->app->saveJSONData("log", array(
      $this->statusID => $msg
    ));
    $this->statusID++;
  }
  
  /* Get file contents from file */
  private function input($file) {
    $out      = $this->options["src"];
    $filename = "$out/$file";
    
    /* Has the file already been read ? */
    if(array_key_exists($file, $this->fileReadCache)) {
      $contents = base64_decode($this->fileReadCache[$file]);
    } else {
      $contents                   = file_get_contents($filename);
      $this->fileReadCache[$file] = base64_encode($contents);
    }
    return $contents;
  }
  
  /**
   * Write compressed file in output folder
   */
  private function output($name, $content) {
    $out                        = $this->options["out"];
    $location                   = "$out/$name";
    $this->fileReadCache[$name] = base64_encode($content);
    file_put_contents($location, $content);
  }
  
  /**
   * Replace Strings
   */
  public function replaceStrings() {
    $this->status("Started Replacing Strings");
    $strings = $this->options["replace"];
    
    $files = $this->files;
    foreach($files as $subFiles) {
      foreach($subFiles as $file) {
        $contents = $this->input($file);
        $replaced = $contents;
        foreach($strings as $from => $to) {
          $replaced = str_replace($from, $to, $replaced);
        }
        /**
         * Check if content changed
         */
        if($contents !== $replaced) {
          $this->status("Replaced strings in <b>$file</b>");
          $this->output($file, $replaced);
        }
      }
    }
    $this->status("Finished Replacing Strings");
  }
  
  /**
   * Compress HTML
   */
  public function compressHTML() {
    $this->status("Started HTML Compression");
    $files = $this->files["text/html"];
    
    if($this->options["minPHP"] && isset($this->files["text/x-php"])) {
      $files = array_merge($this->files["text/x-php"], $files);
    }
    foreach($files as $file) {
      $code = $this->input($file);
      
      /**
       * Check if page is actually HTML
       */
      preg_match("/\<html|\<body|\<div]/", $code, $matches);
      if(!empty($matches)) {
        $this->status("Compressing HTML <b>$file</b>");
        $minified = self::_compressor("html", $code);
        $this->output($file, $minified);
      }
    }
    $this->status("Finished HTML Compression");
  }
  
  /**
   * JS compression
   */
  public function compressJS() {
    $this->status("Started JavaScript Compression");
    $files = $this->files["application/javascript"];
    
    foreach($files as $file) {
      $this->status("Compressing JS <b>$file</b>");
      $code     = $this->input($file);
      $minified = self::_compressor("js", $code);
      $this->output($file, $minified);
    }
    $this->status("Finished JavaScript Compression");
  }
  
  /**
   * CSS compression
   */
  public function compressCSS() {
    $this->status("Started CSS Compression");
    $files = $this->files["text/css"];
    
    foreach($files as $file) {
      $this->status("Compressing CSS <b>$file</b>");
      $code     = $this->input($file);
      $minified = self::_compressor("css", $code);
      $this->output($file, $minified);
    }
    $this->status("Finished CSS Compression");
  }
  
  /**
   * Compress each languages
   * @param string $language The language of source code
   * @param string $code The source code
   * @return string Compressed code
   */
  public static function _compressor($language, $code = "") {
    /**
     * Skip if file size is > 500KB
     */
    if(mb_strlen($code, '8bit') > 500000) {
      return $code;
    } else {
      if($language == "css") {
        /**
         * What kind of css stuff should it convert
         */
        
        $minifier = new MatthiasMullie\Minify\CSS();
        $minifier->add($code);
        return $minifier->minify();
      } else if($language == "js") {
        return JShrink\Minifier::minify($code);
      } else if($language == "html") {
        $html = new Tinyfier_HTML_Tool();
        if($this->options["minInline"]) {
          return $html->process($code, array(
            "compress_all" => true
          ));
        } else {
          return $html->process($code, array(
            "compress_all" => false
          ));
        }
      }
    }
  }
  
  /**
   * Remove a directory recursively
   */
  public function recursiveRemoveDirectory($dir) {
    $it    = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file) {
      if($file->getFilename() === '.' || $file->getFilename() === '..') {
        continue;
      }
      if($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }
    if($dir != $this->options["out"]) {
      rmdir($dir);
    }
  }
  
}
?>
