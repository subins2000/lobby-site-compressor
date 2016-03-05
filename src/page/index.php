<?php
$this->setTitle("Site Compressor");
?>
<div class="contents" style="margin-top: 100px;">
  <center>
    <div style="margin-bottom: 20px;">
      <a class="btn red btn-large" href="<?php echo \Lobby\App::u("/site");?>" style='font-size: 30px;'>Compress A Site</a>
    </div>
    <div style="margin-bottom: 20px;">
      <a class="btn blue"  href="<?php echo \Lobby\App::u("/html");?>">Compress Hyper Text Markup Language (HTML)</a>
    </div>
    <a class="btn green" href="<?php echo \Lobby\App::u("/css");?>">Compress Cascading Style Sheet (CSS)</a>
    <a class="btn orange" href="<?php echo \Lobby\App::u("/js");?>">Compress JavaScript (JS)</a>
  </center>
</div>
