<?php
$this->removeData("finished-compress");
if($this->getData("compress-msg")){
?>
  {"type": "overwrite", "msg": "<?php echo $this->getData("compress-msg");?>"}
<?php
}else{
  $history = $this->getJSONData("log");
  if(isset($history["finished"])){
?>
    {"type": "prepend", "msg": "<div class='status'>Finished site compression in <?php echo $history["finished"];?> seconds</div>"}
<?php
  }else{
    krsort($history);
  
    $log = "";
    foreach($history as $i => $msg){
      $log .= "<div class='status'>$msg</div>";
    }
?>
    {"type": "overwrite", "msg": "<?php echo $log;?>"}
<?php
  }
}
?>
