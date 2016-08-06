lobby.load(function(){
  /**
   * Site path Picker
   */
  $("#workspace #choose_site_path").live("click", function(){
    lobby.mod.FilePicker("/", function(result){
      $("#workspace #site_location").val(result.dir);
    });
  });
  
  $("#workspace #choose_site_output_path").live("click", function(){
    lobby.mod.FilePicker("/", function(result){
      $("#workspace #site_output_location").val(result.dir);
    });
  });
});
