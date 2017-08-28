<?php
if(!defined('ROOT')) exit('No direct script access allowed');

if(!function_exists("getGalleryList")) {
	
	function isGallery($galleryID,$galleryDir=null) {
		if($galleryDir==null) {
			$galleryDir=APPROOT."media/gallery/";//APPS_MEDIA_FOLDER.
		}
		$f=$galleryDir.$galleryID."/config.cfg";
		
		if(file_exists($f)) return dirname($f)."/";
		
		return false;
	}
	
	function getGalleryList($galleryDir=null) {
		if($galleryDir==null) {
			$galleryDir=APPROOT."media/gallery/";//APPS_MEDIA_FOLDER.
		}
		$data=[];
		
		$fs=scandir($galleryDir);
		$fs=array_splice($fs,2);
		foreach($fs as $ff) {
			$f=$galleryDir.$ff.'/';
			$cfg=$f."config.cfg";
			
			$gal=[];
			if(file_exists($cfg)) {
				$cfgArr=ConfigFileReader::LoadCfgFile($cfg);
				if(!isset($cfgArr['CONFIG'])) $cfgArr['CONFIG']=[];
				
				$gal=[
					"title"=>toTitle($ff),
					"config"=>$cfgArr['CONFIG']
				];
			} else {
				$gal=[
					"title"=>toTitle($ff),
					"error"=>"no_config",
				];
			}
			if(is_dir($f."thumbs/")) {
				$gal['photos']=count(scandir($f."thumbs/"))-2;
			} else {
				$gal['photos']=0;
			}
			
			if($gal['photos']<0) $gal['photos']=0;
			
			$data[$ff]=$gal;
		}
		
		return $data;
	}
	function getGalleryConfig($galleryID,$galleryDir=null) {
		if($galleryDir==null) {
			$galleryDir=APPROOT."media/gallery/";//APPS_MEDIA_FOLDER.
		}
		
		$cfg=$galleryDir.$galleryID."/config.cfg";
		
		$cfgArr=ConfigFileReader::LoadCfgFile($cfg);
		if(!isset($cfgArr['CONFIG'])) $cfgArr['CONFIG']=[];
		
		return array_merge(getDefaultGalleryParams(),$cfgArr['CONFIG']);
	}
	function getGallery($galleryID,$galleryDir=null) {
		if($galleryDir==null) {
			$galleryDir=APPROOT."media/gallery/";//APPS_MEDIA_FOLDER.
		}
		
		$dir=$galleryDir.$galleryID."/thumbs/";
		$data=$galleryDir.$galleryID."/data/";
		$cfg=$galleryDir.$galleryID."/config.cfg";
		$sort=$galleryDir.$galleryID."/sort.lst";
		
		$cfgArr=ConfigFileReader::LoadCfgFile($cfg);
		if(!isset($cfgArr['CONFIG'])) $cfgArr['CONFIG']=[];
		
		if(file_exists($sort)) {
			$sort=file_get_contents($sort);
			if(strlen($sort)>1) {
				$sort=explode("\n",$sort);
			} else {
				$sort=[];
			}
		} else {
			$sort=[];
		}
		
		$gal=["title"=>toTitle($galleryID),"photos"=>[],"config"=>$cfgArr['CONFIG']];
		if(is_dir($dir)) {
			$fs=scandir($dir);
			$fs=array_splice($fs,2);
			
			$photos=[];
			foreach($fs as $f) {
				$fCFG=$data.current(explode(".",$f)).".txt";
				$fCFGArr=ConfigFileReader::LoadCfgFile($fCFG);
				$hid=md5($f);
				$photos[$f]=[
									"fname"=>$f,
									"hash"=>$hid,
									"thumb"=>getWebPath($dir.$f),
									"large"=>getWebPath(dirname($dir)."/large/$f"),
									"props"=>array_merge(getDefaultPhotoParams(),$fCFGArr['CONFIG'])
							];
			}
			$finalPhotos=[];
			foreach($sort as $f) {
				if(isset($photos[$f])) {
					$finalPhotos[$f]=$photos[$f];
					unset($photos[$f]);
				}
			}
			foreach($photos as $f=>$p) {
				$finalPhotos[$f]=$p;
			}
			$gal['photos']=$finalPhotos;
		}
		
		return $gal;
	}
	
	function getDefaultGalleryParams() {
		$params=[
			"title"=>"New Gallery",
			"type"=>"cms",
			"thumb-width"=>200,
			"thumb-height"=>200,
		];
		
		return $params;
	}
	function getDefaultPhotoParams() {
		return [
			"title"=>"",
			"label"=>"",
			"descs"=>"",
			"link"=>"",
			"visible"=>true
		];
	}
}
?>