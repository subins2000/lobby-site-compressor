lobby.load(function(){
  $("#workspace #add").on("click", function(){
    $(this).before("<div class='col l6 m12 s12'><input type='text' class='col l11 m11 s11' name='skipAssets[]' placeholder='Relative path to the asset that needs to be skipped' /><a id='remove' class='col l1 m1 s1'></a></div>");
  });

  $("#workspace #remove").on("click", function(){
    $(this).parent().remove();
  });
});
