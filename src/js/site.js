$("#workspace").scroll(function(){
  minHeight = $("#sidebar .sidebar-link:not(.sidebar-link-fixed)").innerHeight();
  if($("#workspace").scrollTop() > minHeight)
    fromTop = $("#workspace").scrollTop() - minHeight;
  else
    fromTop = 0;

  $("#workspace #sidebar .sidebar-link-fixed").css("transform", "translateY("+ fromTop +"px)");
});
