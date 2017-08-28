<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$galleryDir=CMS_APPROOT."media/gallery/";

if(!is_dir($galleryDir)) {
	mkdir($galleryDir,0777,true);
}

if(is_dir($galleryDir)) {
	loadModule("pages");
	
	function pageSidebar() {
		// <form role='search'>
		//     <div class='form-group'>
		//       <input type='text' class='form-control' placeholder='Search'>
		//     </div>
		// </form>
		return "<div id='componentTree' class='componentTree list-group list-group-root well'></div>";
	}
	function pageContentArea() {
		return "<div id='componentSpace' class='componentSpace'><h2 align=center>Please load a gallery to view.</h2><p align=center>Tips: You can reorder photos by drag and drop.</p></div>
	<script>
	FORSITE='{$_REQUEST["forsite"]}';
	</script>
		";
	}
	
	echo _css("gallery");
	echo _js("gallery");
	
	printPageComponent(false,[
			"toolbar"=>[
	//			"galleries"=>["title"=>"","align"=>"right","type"=>"select"],
	// 			"comps"=>["title"=>"Components","align"=>"right"],
	// 			"layouts"=>["title"=>"Layouts","align"=>"right"],
				// ['type'=>"bar"],

				// ["title"=>"Search Site","type"=>"search","align"=>"left"]
				"listGallery"=>["icon"=>"<i class='fa fa-refresh'></i>"],
				"createGallery"=>["icon"=>"<i class='fa fa-plus'></i>","tips"=>"Create New"],
				//"openExternal"=>["icon"=>"<i class='fa fa-external-link'></i>","class"=>"onsidebarSelect"],
				"renameGallery"=>["icon"=>"<i class='fa fa-terminal'></i>","class"=>"onsidebarSelect onOnlyOneSelect","tips"=>"Rename Gallery"],
				['type'=>"bar"],
				"deleteGallery"=>["icon"=>"<i class='fa fa-trash'></i>","class"=>"onsidebarSelect"],
			],
			"sidebar"=>"pageSidebar",
			"contentArea"=>"pageContentArea"
		]);
} else {
	echo "<h1 class='errorMsg'>Sorry, the plugin is not properly installed.</h1><h5 class='errorMsg'>Please visit Plugin Manager for further details.</h5>";
}
?>