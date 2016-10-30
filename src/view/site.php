<?php
$this->addStyle("site.css");
$this->addScript("site.js");

if($siteID !== null){
  $siteInfo = $this->getSiteInfo($siteID);
  if($siteInfo === false)
    $this->redirect("/sites?site-doesnt-exist=$siteID");
}

if($siteID !== null && $page === "delete" && Request::postParam("confirm-delete") === "yes"){
  $this->removeData("site-$siteID");
  $this->data->saveArray("sites", array(
    $siteID => false
  ));
  $this->redirect("/sites?site-deleted=$siteID");
}

if(Request::postParam("refreshAssets") !== null){
  $this->refreshAssets($siteInfo);
  $this->redirect("/site/$siteID/assets");
}
?>
<div id="wrap" class="row black">
  <div id="sidebar" class="col s10 m2 l2">
    <a href="<?php echo $this->u("/sites");?>" class="sidebar-link purple">Sites</a>
    <?php
    if($siteID !== null){
      echo $this->l("/site/$siteID", $siteInfo["name"], "class='sidebar-link sidebar-link-fixed orange'");
    }
    $nav = array(
      "compress" => "Compress",
      "assets" => "Assets",
      "replacer" => "Replacer",
      "settings" => "Settings"
    );
    if($siteID !== null){
      foreach($nav as $link => $text){
        echo "<a href='". $this->u("/site/$siteID/$link") ."' class='sidebar-link sidebar-link-fixed ". ($page === $link ? "active" : "") ."'>$text</a>";
      }
    }
    ?>
  </div>
  <div id="content" class="col s10 m10 l10">
    <?php
    if($siteID === null && $page === null){
    ?>
      <h1>Sites</h1>
      <?php
      if(Request::getParam("site-doesnt-exist") !== null)
        echo ser("Site Doesn't Exist", "Error - The site <b>". htmlspecialchars(Request::getParam("site-doesnt-exist")) ."</b> doesn't exist.");
      if(Request::getParam("site-deleted") !== null)
        echo ser("Site Doesn't Exist", "Error - The site <b>". htmlspecialchars(Request::getParam("site-deleted")) ."</b> doesn't exist.");
      ?>
      <p>Choose a site or <a href="<?php echo $this->u("/sites/new");?>" class="btn green">create one</a></p>
    <?php
      echo "<ul>";
      foreach($this->data->getArray("sites") as $siteIDi => $siteName){
        echo "<li>". $this->l("/site/$siteIDi", $siteName, "") ."</li>";
      }
      echo "</ul>";
    }else{
      $siteInfo = $this->getSiteInfo($siteID);

      if($page === null){
      ?>
        <h2><?php echo $siteInfo["name"];?></h2>
        <blockquote>
          From <b><?php echo $siteInfo["src"];?></b> to <b><?php echo $siteInfo["out"];?></b>
        </blockquote>
        <?php
        if($siteInfo["lastCompressed"] !== 0)
          echo "<p>Last Compression: ". Lobby\Time::date($siteInfo["lastCompressed"]) ."</p>";
        ?>
        <a class="btn red" href="<?php echo $this->u("/site/$siteID/compress?now");?>">Compress NOW!</a>
        <a class="btn" href="<?php echo $this->u("/site/$siteID/settings");?>">Settings</a>
        <a class="btn red" href="<?php echo $this->u("/site/$siteID/delete");?>">Delete Site</a>
      <?php
      }else if($page === "compress"){
        $this->addScript("jquery.form.min.js");
        $this->addScript("site-compress.js");
      ?>
        <h2>Compress</h2>
        <a class="btn red" id="compress">Compress NOW!</a>
        <div id="status-wrapper">
          <div id="status"></div>
        </div>
        <script>
        lobby.load(function(){
          lobby.app.siteID = "<?php echo $siteID;?>";
          <?php
          if(isset($_GET["now"])){
          ?>
          if(confirm("Are you sure you want to compress now ?"))
            lobby.app.compress();
          <?php
          }
          ?>
        });
        </script>
      <?php
      }else if($page === "assets"){
        $this->addStyle("assets.css");
        $this->addScript("assets.js");

        $skipAssets = $this->data->getArray("$siteID-skip-assets");
      ?>
        <h2>Skip Assets</h2>
        <p>Find & Filter out assets of your site from compression. This is useful to skip already minified files like jQuery.min.js</p>
        <form action="<?php echo $this->u("/site/$siteID/assets");?>" method="POST">
          <?php
          if(Request::isPOST() && Request::postParam("skipAssets") !== null){
            $this->removeData("$siteID-skip-assets");
            $this->data->saveArray("$siteID-skip-assets", Request::postParam("skipAssets"));
            echo sss("Saved Skipped Assets", "The list of assets that needs to be skipped has been saved");
          }

          if(empty($skipAssets)){
            echo "<p>No assets are currently skipped from compression. Add one below :</p>";
            $skipAssets[] = "";
          }else{
            echo "<div class='row'>";
              foreach($skipAssets as $asset){
                echo "<div class='col l6 m12 s12'><input type='text' class='col l11 m11 s11' name='skipAssets[]' value='$asset' placeholder='Relative path to the asset that needs to be skipped' /><a id='remove' class='col l1 m1 s1'></a></div>";
              }
            echo "</div>";
          }
          ?>
          <a id="add"></a>
          <button class="btn green">Save Skipped Assets</button>
        </form>
        <?php
        echo "<h3>Assets</h3>";
        echo "<form action='". $this->u("/site/$siteID/assets") ."' method='POST'><button class='btn red' name='refreshAssets' value='1'>Refresh Assets</button></form>";

        foreach($this->data->getArray("$siteID-assets") as $type => $assets){
          echo "<h4>$type</h4>";
          echo "<ul class='row'>";
          foreach($assets as $asset){
            echo "<li class='col l3 m3 s3 truncate' title='$asset'>$asset</li>";
          }
          echo "</ul>";
        }
      }else if($page === "replacer"){
        $this->addStyle("replacer.css");
        $this->addStyle("replacer.js");
      ?>
        <h2>Replacer</h2>
        <p>You can replace strings in your site's source code with this tool.</p>
        <?php
        $replacer = $this->data->getArray("$siteID-replacer");

        if(!empty(Request::postParam("replacer"))){
          $replacer = array();
          $inputReplacer = Request::postParam("replacer");

          for($i=0;$i < count($inputReplacer["from"]);$i++){
            if(!empty($inputReplacer["from"][$i]))
              $replacer[$inputReplacer["from"][$i]] = $inputReplacer["to"][$i];
          }
          $this->removeData("$siteID-replacer");
          $this->data->saveArray("$siteID-replacer", $replacer);
        }
        ?>
        <form action="<?php echo $this->u("/site/{$siteID}/replacer");?>" method="POST">
          <a id="add"></a>
          <?php
          if(!empty($replacer)){
            foreach($replacer as $from => $to){
              echo "<div class='row'><input type='text' name='replacer[from][]'  class='col s6 m6 l6' value='$from' placeholder='From' /><input type='text'  class='col s6 m6 l6' name='replacer[to][]' value='$to' placeholder='To' /></div>";
            }
          }
          ?>
          <div class="row">
            <input type="text" name="replacer[from][]" class="col s6 m6 l6" placeholder="From" />
            <input type="text" name="replacer[to][]" class="col s6 m6 l6" placeholder="To" />
          </div>
          <button class="btn green">Save</button>
        </form>
      <?php
      }else if($page === "delete"){
      ?>
        <h2>Delete Site</h2>
        <p>If you choose to delete, the site's directory will not be affected.</p>
        <p>Do you want to remove this site from <b>Site Compressor</b>'s list of sites ?</p>
        <form action="<?php echo $this->u("/site/$siteID/delete");?>" method="POST">
          <button name="confirm-delete" value="yes" class="btn red">Yes</button>
          <?php
          echo $this->l("/site/$siteID", "No", "class='btn'");
          ?>
        </form>
      <?php
      }else if($page === "settings"){
      ?>
        <h1>Settings</h1>
      <?php
      }else if($page === "new"){
      ?>
        <h1>New Site</h1>
      <?php
      }else{
        echo ser("Invalid request", "Check the URL");
      }
    }

    if($page === "new" || $page === "settings"){
      $this->addScript("site-settings.js");

      $editing = $siteID !== null;

      if(!$editing){
        $siteInfo = array(
          "name" => null,
          "src" => null,
          "out" => null,
          "minHTML" => true,
          "minPHP" => true,
          "noComments" => true,
          "minCSS" => true,
          "minJS" => true,
          "minInline" => true,
          "skipMinFiles" => true
        );
      }

      if(Request::isPOST()){
        $siteInfo = array(
          "name" => Request::postParam("siteName"),
          "src" => Request::postParam("siteSRC"),
          "out" => Request::postParam("siteOut"),
          "minHTML" => (int) (Request::postParam("minHTML") !== null),
          "minPHP" => (int) (Request::postParam("minPHP") !== null),
          "noComments" => (int) (Request::postParam("noComments") !== null),
          "minCSS" => (int) (Request::postParam("minCSS") !== null),
          "minJS" => (int) (Request::postParam("minJS") !== null),
          "minInline" => (int) (Request::postParam("minInline") !== null),
          "skipMinFiles" => (int) (Request::postParam("skipMinFiles") !== null),
        );
        if($siteInfo["name"] == null || $siteInfo["src"] == null || $siteInfo["out"] == null){
          echo ser("Fields Missing", "Please fill up the entire form.");
        }else{
          /**
           * Add fields that are optional
           */
          $siteInfo["beforeCMD"] = Request::postParam("beforeCMD");
          $siteInfo["afterCMD"] = Request::postParam("afterCMD");

          $id = $siteID === null ? strtolower(preg_replace('/[^\da-z]/i', '', $siteInfo["name"])) : $siteID;
          $this->data->saveArray("site-$id", $siteInfo);

          $this->data->saveArray("sites", array(
            $id => $siteInfo["name"]
          ));

          if($siteInfo["skipMinFiles"] === 1){
            $this->data->saveArray("$siteID-skip-assets", $this->findMinFiles($siteInfo["src"]));
          }

          echo sss("Site Saved", "Your site was saved. <a href='". $this->u("/site/$id/compress?now") ."'>Compress it now!</a>");
        }
      }else if(!$editing){
        $siteInfo["beforeCMD"] = null;
        $siteInfo["afterCMD"] = null;
      }
      ?>
      <form action="<?php echo $editing ? $this->u("/site/$siteID/settings") : $this->u("/sites/new");?>" method="POST">
        <label>
          <span>Name</span>
          <input type="text" name="siteName" value="<?php echo $siteInfo["name"];?>" />
        </label>
        <div class="row">
          <label class="col l6">
            <span title="Path to your site's source code">Source</span>
            <input type="text" id="site_location" name="siteSRC" placeholder="/var/www/html/mysite/source-code" value="<?php echo $siteInfo["src"];?>" />
            <a id="choose_site_path" class="btn orange">Choose Path</a>
          </label>
          <label class="col l6">
            <span title="Path to where the compressed source code be outputted">Output</span>
            <input type="text" id="site_output_location" name="siteOut" placeholder="/var/www/html/mysite/compressed-site" value="<?php echo $siteInfo["out"];?>" />
            <a id="choose_site_output_path" class="btn orange">Choose Path</a>
          </label>
        </div>
        <h3>Compression Options</h3>
        <div class="row">
          <label class="col s4">
            <input type="checkbox" name="minHTML" <?php if($siteInfo["minHTML"]){echo "checked='checked'";}?> />
            <span>Minimize HTML</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="minPHP" <?php if($siteInfo["minPHP"]){echo "checked='checked'";}?> />
            <span>Minimize HTML in .php files</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="noComments" <?php if($siteInfo["noComments"]){echo "checked='checked'";}?> />
            <span>Remove Comments</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="minCSS" <?php if($siteInfo["minCSS"]){echo "checked='checked'";}?> />
            <span>Minimize CSS</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="minJS" <?php if($siteInfo["minJS"]){echo "checked='checked'";}?>/>
            <span>Minimize JS</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="minInline" <?php if($siteInfo["minInline"]){echo "checked='checked'";}?>/>
            <span title="Minimize code inside &lt;script>&lt;/script> and &lt;style>&lt;/style>">Minimize Inline CSS, JS</span>
          </label>
          <label class="col s4">
            <input type="checkbox" name="skipMinFiles" <?php if($siteInfo["skipMinFiles"]){echo "checked='checked'";}?>/>
            <span title="Files like jquery.min.js will be skipped from compression">Skip files that has ".min" in it's name</span>
          </label>
        </div>
        <h3>Callbacks</h3>
        <label>
          <span title="Run a Terminal command before compression starts">Before Compression</span>
          <input type="text" placeholder="python /home/user/myScriptBeforeCompression.py" name="beforeCMD" value="<?php echo $siteInfo["beforeCMD"];?>" />
        </label>
        <label>
          <span title="Run a Terminal command after compression finished">After Compression</span>
          <input type="text" placeholder="python /home/user/myScriptAfterCompression.py" name="afterCMD" value="<?php echo $siteInfo["afterCMD"];?>" />
        </label>
        <button class="btn green"><?php echo $editing ? "Update site" : "Add New Site";?></button>
      </form>
    <?php
    }
    ?>
  </div>
</div>
<?php
/**
 * <div class="top">
  <div class="table">
    <div id="left" class="left">
      <h2>Saves</h2>
      <div>
        <table>
          <thead>
            <tr>
              <td>Name</td>
              <td>Actions</td>
            </tr>
          </thead>
          <tbody id="configSaves"></tbody>
        </table>
      </div>
    </div>
    <div id="right" class="left">
      <h2>Quick Actions</h2>
      <button class="btn green" id="startCompress">Start Compressing</button>
      <a class="btn" id="saveConfig">Save Current Configuration</a>
      <h2>Compression Options</h2>
      <form id="options">
        <label>
          <input type="checkbox" data-binding="minHtml" checked="checked" name="minHtml"/>
          <span>Minimize HTML</span>
        </label>
        <label>
          <input type="checkbox" data-binding="minPHP" checked="checked" name="minPHP"/>
          <span>Minimize HTML in .php Files</span>
        </label>
        <label>
          <input type="checkbox" data-binding="noComments" checked="checked" name="noComments"/>
          <span>Remove Comments</span>
        </label>
        <label>
          <input type="checkbox" data-binding="minCss" checked="checked" name="minCss"/>
          <span>Minimize CSS</span>
        </label>
        <label>
          <input type="checkbox" data-binding="minJs" checked="checked" name="minJs"/>
          <span>Minimize JS</span>
        </label>
        <label>
          <input type="checkbox" data-binding="minInline" checked="checked" name="minInline"/>
          <span>Minimize Inline CSS, JS (&lt;script>&lt;/script>, &lt;style>&lt;/style>)</span>
        </label>
      </form>
      <h2>Site Details</h2>
      <form id="siteDetails">
        <label>
          <span>Site Location</span>
          <input type="text" id="site_location" data-binding="siteLoc" name="location" placeholder="/var/www/html/mysite/local" />
          <a id="choose_site_path" class="btn orange">Choose Path</a>
        </label>
        <label>
          <span>Output</span>
          <input type="text" id="site_output_location" data-binding="siteOutput" name="output" placeholder="/var/www/html/mysite/compressed" />
          <a id="choose_site_output_path" class="btn orange">Choose Path</a>
        </label>
        <p>^ The location where the output must be written</p>
        <h2>Replacer</h2>
        <div id="replaceFields">
          <p>You can also replace strings like <b>localsite.dev</b> to <b>mydomain.com</b></p>
          <a class="btn addReplaceField">Add New Field</a>
        </div>
        <div>
          <h2>Before Compression</h2>
          <label>
          <p>Run a Terminal command before compression starts (Avoid using double quotes ("))</p>
            <input type="text" data-binding="beforeCommand" placeholder="Type Command Here" name="beforeCommand"/>
          </label>
        </div>
        <div>
          <h2>After Compression</h2>
          <label>
            <p>Run a Terminal command after compression finished (Avoid using double quotes ("))</p>
            <input type="text" data-binding="afterCommand" placeholder="Type Command Here" name="afterCommand"/>
          </label>
        </div>
        <h2>Finish</h2>
        <p>Don't forget to save !</p>
        <button class="btn green">Start Compressing</button>
        <a class="btn" id="saveConfig">Save Current Configuration</a>
      </form>
    </div>
   </div>
</div>
<div class="compress-status">
  Compression details will be shown up here after you request for compression.
</div>
*/
?>
