tmp.saves={};$.fn.serializeHTML=function(){var formData="";var formId=$(this).attr("id");this.find('[name]').each(function(){name=$(this).attr("name");if(name.substr(-2,2)=="[]"){name=name.substr(0,name.length-2);formData+='<input name="'+formId+"["+name+"][]"+'" value="'+$(this).val()+'" type="text" />';}else{if($(this).is(":checked")){formData+='<input name="'+formId+"["+name+"]"+'" type="checkbox" checked="checked" />';}else if($(this).attr("type")!="checkbox"){formData+='<input name="'+formId+"["+name+"]"+'" value="'+$(this).val().replace(/"/g,"'")+'" type="text" />';}}});return formData;};tmp.restoreConfig=function(configName){$replaceData=tmp["saves"][configName]["replacer"];$mainData=tmp["saves"][configName]["main"];var replaceFields={};$("#replaceFields div").remove();$.each($replaceData,function(key,val){$(".workspace#site-compressor .addReplaceField").click();$(".workspace#site-compressor #replaceFields div:last").find("[name='replaceFrom[]']").val(key);$(".workspace#site-compressor #replaceFields div:last").find("[name='replaceTo[]']").val(val);});$('.workspace#site-compressor .top [data-binding]').each(function(){var $this=$(this);var val=$mainData[$this.data('binding')];if($this.is('[type=checkbox]')){$this.prop('checked',val)}else{$this.val(decodeURIComponent(val.replace($this.attr("name")+"=","")));}});$(".workspace#site-compressor [name='beforeCommand'], .workspace#site-compressor [name='afterCommand']").each(function(){$(this).val($(this).val().replace(/\+/g," "));});};tmp.saveConfig=function(configName){var generalSettings={};$('.workspace#site-compressor .top [data-binding]').each(function(){generalSettings[$(this).data('binding')]=$(this).serialize();});var replacerData={};if($(".workspace#site-compressor #siteDetails div").length!=0){$(".workspace#site-compressor #siteDetails div").each(function(){from=$(this).find("[name='replaceFrom[]']").val();to=$(this).find("[name='replaceTo[]']").val();if(from!=""&&to!=""){replacerData[from]=to;}});}
var configData=JSON.stringify({"main":generalSettings,"replacer":replacerData});lobby.app.save(configName,configData,function(data){if(data=="bad"){alert("Failed Saving Data");}else{tmp.displaySaves();}});}
tmp.displaySaves=function(){lobby.app.ajax("saves.php",{},function(data){var data=JSON.parse(data);if(data.length==0){$(".workspace#site-compressor #configSaves").html("No Saves Found");}else{$(".workspace#site-compressor #configSaves").html("");$.each(data,function(key){$(".workspace#site-compressor #configSaves").append("<div style='margin:5px;' id='"+key+"'>"+key+" - "+"<a href='javascript:void(0);' class='loadConfig'>Load</a> <a href='javascript:void(0);' class='removeConfig'>Remove</a>"+"</div>");});}
tmp.saves=data;});};$(document).ready(function(){localStorage["lastSaveName"]="";$(".workspace#site-compressor #left, .workspace#site-compressor #right, .workspace#site-compressor .compress-status").addClass("scrollbar-inner").scrollbar();$(".workspace#site-compressor .table #left:first, .workspace#site-compressor .table #right:first").width(($(document).width()/2)-10);tmp.displaySaves();$(".workspace#site-compressor .loadConfig").live("click",function(){id=$(this).parent().attr("id");localStorage["lastSaveName"]=id;tmp.restoreConfig(id);});$(".workspace#site-compressor .removeConfig").live("click",function(){configName=$(this).parent().attr("id");lobby.app.remove(configName,function(){tmp.displaySaves();});});$(".workspace#site-compressor #siteDetails").live("submit",function(e){e.preventDefault();var formURL=lobby.app.src+"/src/ajax/compress.php";var formHTML=$("<form>").html($(".workspace#site-compressor #options").serializeHTML()+$(".workspace#site-compressor #siteDetails").serializeHTML()).attr({"method":"POST","action":formURL}).css("display","none").wrap('<p>').parent().html();$(".workspace#site-compressor .compress-status").html("");$("<iframe/>").attr({"height":($(document).height()-($(".workspace#site-compressor .top").offset().top+$(".workspace#site-compressor .top").height()))-50,"width":"100%","frameborder":0,}).appendTo(".workspace#site-compressor .compress-status");$(".workspace#site-compressor .compress-status").find("iframe").contents().find("body").html(formHTML);$(".workspace#site-compressor .compress-status").find("iframe").contents().find("body").find("form").submit();});$(".workspace#site-compressor .addReplaceField").live("click",function(){$(this).before("<div style='margin:5px;'><input name='replaceFrom[]' placeholder='From'><input name='replaceTo[]' style='margin-left:5px' placeholder='To'/></div>");});$(".workspace#site-compressor #saveConfig").live("click",function(){var saveName=prompt("Name the configuration ?",localStorage["lastSaveName"]);if(saveName!=null){if(saveName.match("'")){alert("Can't have special characters in the save name");}else{if(saveName==""){saveName="default";}
localStorage["lastSaveName"]=saveName;tmp.saveConfig(saveName);}}});});