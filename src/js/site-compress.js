lobby.app = $.extend(lobby.app, {

  siteID: {},

  compressionRunning: false,

  compress: function(){
    this.compressionRunning = true;
    $("#workspace #status").html("");
    this.ar("compress", {
      "siteID": this.siteID
    }, function(){
      lobby.app.checkStatus();
    });
  },

  checkStatus: function(){
    this.ar("compress-status", {}, function(response){
      response = JSON.parse(response);
      if(response.type === "overwrite"){
        $("#workspace #status").html(response.msg);
        setTimeout(function(){
          lobby.app.checkStatus();
        }, 1000);
      }else{
        $("#workspace #status").prepend(response.msg);
      }
    });
  }

});

lobby.load(function(){
  $("#workspace #compress").on("click", function(){
    lobby.app.compress();
  });
});
