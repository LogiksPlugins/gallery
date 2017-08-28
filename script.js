var currentGallery = null;
var lastPhoto=null;
var reloadPhotosRequired=false;
$(function() {
	$("#componentSpace").css("height","78%");
	$("#pgtoolbar .nav.navbar-nav.navbar-left").css("width",$(".pageCompContainer.withSidebar .pageCompSidebar").width());
	$("<label id='titleContent' class='titleContent'></label>").insertAfter($("#pgtoolbar .nav.navbar-nav.navbar-left"));
	
	$("#componentSpace").delegate("#uploadMaskIcon","click",function() {
    $("#uploadForm input[type=file]").click();
  });
	$("#componentSpace").delegate("#uploadForm input[type=file]","change",function() {
    uploadFiles();
  });
	$("#componentSpace").delegate(".refreshPhotos","click",function() {
    loadGalleryPhotos(currentGallery);
  });
	$("#componentSpace").delegate(".nav li>a[href=#gallery]","click",function() {
    if(reloadPhotosRequired) {
			loadGalleryPhotos(currentGallery);
		}
  });
	
	$("#componentSpace").delegate(".photoCMD[cmd]","click",function() {
		lastPhoto=null;
		
		cmd=$(this).attr('cmd');
		hash=$(this).data('hash');
		fname=$(this).data('fname');
		
		lastPhoto=$(this).closest(".thumb");
		
		switch(cmd) {
			case "remove":
				lgksConfirm("Are you sure about deleting this photo.","Delete Photo?",function(ans) {
					if(ans) {
						processAJAXPostQuery(_service("gallery","deletePhoto"),"gallery="+currentGallery+"&photo="+fname+"&hash="+hash,function(txt) {
									err=txt.split(":");
									if(err[0]=="error") {
										lgksToast(err[1]);
									} else {
										if(lastPhoto!=null) {
											lastPhoto.detach();
										} else {
											loadGalleryPhotos(currentGallery);
										}
										lgksToast("Photo deleted successfully.");
									}
								},"RAW");
					}
				});
				break;
			case "edit":
				thumb=$(this).closest(".thumb");
				cfgData=thumb.find("a.thumbnail").data();
				
				html="<form class='form-horizontal'>";
				html+="<input type='hidden' name='photo' value='"+thumb.data('fname')+"' />";
				html+="<input type='hidden' name='photohash' value='"+thumb.data('hash')+"' />";
				html+="<input type='hidden' name='gallery' value='"+currentGallery+"' />";
				$.each(cfgData, function(k,v) {
					if(v==null || v=="undefined") v="";
					if(k=="visible" || k=="filename") return;
					
					html+='<div class="form-group">';
					html+='<label for="input'+k+'" class="col-sm-3 control-label">'+k.toUpperCase()+'</label>';
					html+='<div class="col-sm-9">';
					html+='<input type="text" class="form-control" id="input'+k+'" name="'+k+'" value="'+v+'">';
					html+='</div>';
					html+='</div>';
				})
				html+='<div class="form-group">';
				html+='<label for="inputvisible" class="col-sm-3 control-label">VISIBLE</label>';
				html+='<div class="col-sm-9">';
				html+='<select class="form-control" id="inputvisible" name="visible">';
				if(cfgData['visible']!=null && cfgData['visible']===1) {
					html+="<option value='1' selected>Visible</option><option value='0'>Not Visible</option>";
				} else {
					html+="<option value='1'>Visible</option><option value='0' selected>Not Visible</option>";
				}
				html+='<select>';
				html+='</div>';
				html+='</div>';
				html+="<hr>";
				html+='<div class="form-group">';
				html+='<div class="col-sm-offset-3 col-sm-9">';
				html+='<button type="button" class="btn btn-primary pull-right" onclick="savePhotoProperties(this)">Update</button>';
				html+='</div>';
				html+='</div>';
				html+="</form>";
				
				$('#myModal .modal-body').html(html);
				$('#myModal .modal-title').html("Edit Photo");
				
				$('#myModal').modal({show:true});
				break;
		}
	});
	
	$("#componentSpace").delegate(".thumbnail>img","click",function() {
		$('.modal-body').empty();
		
		var title = $(this).parent('a').attr("title");
		var lx = $(this).attr("large");
		
		html="<img src='"+lx+"' class='img-responsive' />";
		
		//$('.modal-title').html(title);
		//$($(this).parents('div').html()).appendTo('.modal-body');
		$('#myModal .modal-body').html(html);
		$('#myModal .modal-title').html("Preview");
		
		$('#myModal').modal({show:true});
	});
	
	$('#componentTree').delegate(".list-group-item.list-file a","click",function() {
		file=$(this).closest(".list-group-item");
		
		title=$(this).text();
		slug=$(file).data("slug");
		
		loadGallery(slug);
	});
	listGallery();
});
function listGallery() {
	$("#componentTree").html("<div class='ajaxloading5'></div>");
	
	processAJAXQuery(_service("gallery","list"),function(txt) {
		fs=txt.Data;
		if(fs==null || fs.length<=0) {
			$("#componentTree").html("<p align=center><br>No Galleries Found.</p>");
			return;
		}
		html="";html1="";
		$.each(fs,function(k,v) {
			kx=md5(k);
			
			html1+="<div class='list-group-item list-file' data-slug='"+k+"'>";
			html1+="<a href='#'><i class='fa fa-file'></i><span class='text'>"+k+" ["+v.photos+" photos]"+"</span></a>";
			html1+="<input type='checkbox' name='selectFile' class='pull-right' data-slug='"+k+"' data-title='"+k+" ["+v.photos+" photos]"+"' /></div>";
		});
		$("#componentTree").html(html+html1);
		
		$("#componentTree .list-group-item.active").removeClass('active');
		if($('#componentTree .list-group-item[data-slug="'+currentGallery+'"]').length>0) {
			$('#componentTree .list-group-item[data-slug="'+currentGallery+'"]').addClass("active");
			
			//TODO
		} else {
			$("#pgtoolbar .nav.navbar-right li.active").removeClass('active');
		}
	},"json");
}
function loadGallery(galleryID) {
	$("#componentTree .list-group-item.active").removeClass('active');
	$("#componentSpace").html("<h2 class='ajaxloading5'></h2>");
	
	processAJAXPostQuery(_service("gallery","fetch"),"slug="+galleryID,function(txt) {
		err=txt.split(":");
		if(err[0]=="error") {
			$("#componentSpace").html("<h2 class='errorMsg'>"+err[1]+"</h2>");
		} else {
			currentGallery=galleryID;
			
			$('#componentTree .list-group-item[data-slug="'+currentGallery+'"]').addClass("active");
			
			$("#componentSpace").html(txt);
			initiatePhotoSort();
			
			//loadGalleryPhotos(galleryID);
		}
	},"RAW");
	reloadPhotosRequired=false;
}
function loadGalleryPhotos(galleryID) {
	$("#galleryBox").html("<h2 class='ajaxloading5'></h2>");
	
	$("#galleryBox").load(_service("gallery","fetchPhotos")+"&slug="+galleryID,"",function(ans) {
		initiatePhotoSort();
	});
	reloadPhotosRequired=false;
}
function createGallery() {
	lgksPrompt("New Gallery! <smaller class='clearfix'>(No Space or special characters allowed.)</smaller>","New Gallery",function(newName) {
			if(newName!=null && newName.length>0) {
				processAJAXPostQuery(_service("gallery","create"),"slug="+newName,function(ans) {
						err=ans.split(":");
						if(err[0]=="error") {
							lgksToast(err[1]);
						} else {
							loadGallery(ans)
							listGallery();
						}
					},"RAW");
			}
		});
}
function renameGallery() {
	q=[];q1=[];
	$("#componentTree input[type=checkbox]:checked").each(function() {
		q.push($(this).data("slug"));
		q1.push($(this).data("title"));
	});
	if(q.length>1) {
		lgksToast("Renaming can be done one at a time only.");
		return;
	}
	q=q[0];
	q1=q1[0];
	lgksPrompt("Rename Gallery '"+q1+"' <smaller class='clearfix'>(No Space or special characters allowed.)</smaller>","New Gallery",function(newName) {
		if(newName!=null && newName.length>0 && newName!=q1) {
			processAJAXPostQuery(_service("gallery","rename"),"slug="+q+"&newname="+newName,function(ans) {
						err=ans.split(":");
						if(err[0]=="error") {
							lgksToast(err[1]);
						} else {
							loadGallery(newName)
							listGallery();
						}
					},"RAW");
		}
	});
}
function deleteGallery() {
	q=[];q1=[];
	$("#componentTree input[type=checkbox]:checked").each(function() {
		q.push($(this).data("slug"));
		q1.push("<li>"+$(this).data("title")+"</li>");
	});
	htmlMsg="Are you sure about deleting the following galleries?<br><ul style='margin-top: 10px;list-style-type: decimal;'>";
	htmlMsg+=q1.join("");
	htmlMsg+="</ul>";
	lgksConfirm(htmlMsg,"Delete Gallery",function(ans) {
		if(ans) {
			processAJAXPostQuery(_service("gallery","delete"),"slug="+q.join(","),function(ans) {
						err=ans.split(":");
						if(err[0]=="error") {
							lgksToast(err[1]);
						} else {
							lgksToast(ans);
							listGallery();
						}
					},"RAW");
		}
	});
}
function savePhotoProperties(btn) {
	if(currentGallery==null) {
		lgksToast("Please click a gallery to edit its properties");
		return;
	}
	frm=$(btn).closest("form");
	
	q=[];
	q.push("slug="+currentGallery);
	$(frm).find("input[name],select[name]").each(function() {
		q.push($(this).attr("name")+"="+$(this).val());
	});
	
	processAJAXPostQuery(_service("gallery","updatePhoto"),q.join("&"),function(ans) {
		err=ans.split(":");
		if(err[0]=="error") {
			lgksToast(err[1]);
		} else {
			$(".modal").modal("hide");
			loadGalleryPhotos(currentGallery);
		}
	},"RAW");
}
function saveGalleryProperties(btn) {
	if(currentGallery==null) {
		lgksToast("Please click a gallery to edit its properties");
		return;
	}
	frm=$(btn).closest("form");
	
	q=[];
	q.push("slug="+currentGallery);
	$(frm).find("input[name],select[name]").each(function() {
		q.push($(this).attr("name")+"="+$(this).val());
	});
	processAJAXPostQuery(_service("gallery","save"),q.join("&"),function(ans) {
		err=ans.split(":");
		if(err[0]=="error") {
			lgksToast(err[1]);
		} else {
			lgksToast(ans);
			listGallery();
		}
	},"RAW");
}
function uploadFiles() {
	$("#uploadForm").addClass("hidden");
  $("#uploadLoader").removeClass("hidden");
  
  $("#uploadForm").submit();
}
function uploadMsg(msg) {
  $("#uploadLoader").addClass("hidden");
  $("#uploadForm").removeClass("hidden");
  if(msg=="DONE") {
    $("#uploadForm")[0].reset();
    listGallery();
		reloadPhotosRequired=true;
		lgksToast("Upload Complete");
  } else {
    lgksToast(msg);
  }
}
function initiatePhotoSort() {
	$( "#galleryBox" ).sortable({
      revert: true,
			containment: "#galleryBox", 
			helper: "clone",
			stop: function() {
        q=[];
				$("#galleryBox>.thumb").each(function() {q.push($(this).data("fname"))});
				q=q.join(",");
				
				processAJAXPostQuery(_service("gallery","saveSort"),"slug="+currentGallery+"&photos="+q,function(ans) {
						err=ans.split(":");
						if(err[0]=="error") {
							lgksToast(err[1]);
							loadGalleryPhotos(currentGallery);
						}
					},"RAW");
      }
    });
}