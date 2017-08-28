<?php
if(!defined('ROOT')) exit('No direct script access allowed');
checkServiceSession();

if(SITENAME!="cms") {
	printServiceMSG("ONLY CMS can access this service.");
	return;
}

$galleryDir=CMS_APPROOT."media/gallery/";

include_once __DIR__."/api.php";

switch($_REQUEST["action"]) {
	case "list":
		printServiceMSG(getGalleryList($galleryDir));
		break;
	case "fetch":
		if(isset($_POST['slug'])) {
			$gallery=getGallery($_POST['slug'],$galleryDir);
			
			//printArray($gallery['photos']);exit();
			
			if(!isset($gallery)) echo "error: Sorry, Gallery Not Found";
			else {
				include __DIR__."/cmsComps/gallery.php";
			}
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "fetchPhotos":
		if(isset($_REQUEST['slug'])) {
			$gallery=getGallery($_REQUEST['slug'],$galleryDir);
			
			if(!isset($gallery)) echo "<h2 class='errorMsg'>Sorry, Gallery Not found.</h2>";
			else {
				if(count($gallery['photos'])>0) {
					foreach($gallery['photos'] as $photo) {
						$hx=[];
						foreach($photo['props'] as $a=>$b) {
							$hx[]="data-{$a}='$b'";
						}
						$hx=implode(" ",array_reverse($hx));
						echo "<div class='col-lg-3 col-sm-4 col-xs-6 thumb' data-hash='{$photo['hash']}' data-fname='{$photo['fname']}'>
											<i class='photoCMD fa fa-times' cmd='remove' data-hash='{$photo['hash']}' data-fname='{$photo['fname']}'></i>
											<i class='photoCMD fa fa-pencil' cmd='edit' data-hash='{$photo['hash']}' data-fname='{$photo['fname']}' style='margin-right: 25px;'></i>
											<a class='thumbnail' href='#' $hx >
												<img class='img-responsive' src='{$photo['thumb']}' large='{$photo['large']}' alt='{$photo['fname']}' />
											</a>
									</div>";
					}
				} else {
					echo "<h2 align=center>Sorry, no photo in this gallery.</h2>";
				}
			}
		} else {
			echo "<h2 class='errorMsg'>Reference Missing</h2>";
		}
		break;
	case "create":
		if(isset($_POST['slug'])) {
			$slug=_slugify($_POST['slug']);
			
			$dirArr=[
				"{$galleryDir}{$slug}/",
				"{$galleryDir}{$slug}/thumbs/",
				"{$galleryDir}{$slug}/large/",
				"{$galleryDir}{$slug}/data/",
			];
			$cfgCheck=$dirArr[0]."config.cfg";
			if(file_exists($cfgCheck)) {
				echo "error: Gallery with the given name exists already.";
				return;
			}
			foreach($dirArr as $dirX) {
				if(!is_dir($dirX)) {
					mkdir($dirX,0777,true);
				}
			}
			
			$dirX=$dirArr[0];
			
			if(is_dir($dirX)) {
				$data=getDefaultGalleryParams();
				
				$data['blocked']="false";
				$data['created_by']=$_SESSION['SESS_USER_ID'];
				$data['created_on']=date("Y-m-d H:i:s");
				$data['edited_by']=$_SESSION['SESS_USER_ID'];
				$data['edited_on']=date("Y-m-d H:i:s");
				
				$q=[];
				foreach($data as $a=>$b) $q[]="$a=$b";
				$q=implode("\n",$q);
				
				file_put_contents($dirX."config.cfg",$q);
				file_put_contents($dirX."sort.lst","");
				echo "Gallery created successfully.";
			} else {
				echo "error: Sorry, could not create gallery. '/media/gallery' might be readonly.";
			}
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "rename":
		if(isset($_POST['slug']) && isset($_POST['newname'])) {
			$galOld=$galleryDir.$_POST['slug']."/";
			$galNew=$galleryDir.$_POST['newname']."/";
			
			$a=rename($galOld,$galNew);
			
			if($a) {
				echo "Successfully renamed the gallery.";
			} else {
				echo "error: Sorry, could not rename gallery '{$_POST['slug']}'.";
			}
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "save":
		if(isset($_POST['slug'])) {
			$slug=$_POST['slug'];
			unset($_POST['slug']);
			
			$_POST['edited_by']=$_SESSION['SESS_USER_ID'];
			$_POST['edited_on']=date("Y-m-d H:i:s");
			
			$cfg=getGalleryConfig($slug,$galleryDir);
			$data=array_merge($cfg,$_POST);
			
			$q=[];
			foreach($data as $a=>$b) $q[]="$a=$b";
			$q=implode("\n",$q);
			
			$cfg=$galleryDir.$slug."/config.cfg";
			
			$ans=file_put_contents($cfg,$q);
			if($ans>2) echo "Successfully updated the properties.";
			else echo "error: Update failed. Try again later.";
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "saveSort":
		if(isset($_POST['slug']) && isset($_POST['photos'])) {
			$slug=$_POST['slug'];
			unset($_POST['slug']);
			
			$photos=$_POST['photos'];
			$photos=explode(",",$photos);
			$photos=implode("\n",$photos);
			
			$sortCfg=$galleryDir.$slug."/sort.lst";
			
			file_put_contents($sortCfg,$photos);
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "delete"://Delete Gallery
		if(isset($_POST['slug']) && strlen($_POST['slug'])>1) {
			$slug=$_POST['slug'];
			unset($_POST['slug']);
			$slug=explode(",",$slug);
			
			$res=[];
			foreach($slug as $gal) {
				$f=$galleryDir.$gal."/";
				recursiveRemoveDirectory($f);
			}
			echo "Successfully deleted the requested galleries.";
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "uploadPhoto":
		if(isset($_POST['slug']) && strlen($_POST['slug'])>1 && isset($_FILES['files'])) {
			
			loadHelpers("imageprops");
			
			$galleryPath=isGallery($_POST['slug'],$galleryDir);
			
			if($galleryPath) {
				$galConfig=getGalleryConfig($_POST['slug'],$galleryDir);
				$thumbWidth=$galConfig["thumb-width"];
				$thumbHeight=$galConfig["thumb-height"];
				
				foreach($_FILES['files']['name'] as $key=>$fName) {
					$ext=explode(".",$fName);
					$ext=strtolower(end($ext));
					
					if($_FILES['files']['error'][$key]==0) {
						$photoName=time()."-".md5($fName);
						$originalPath="{$galleryPath}large/{$photoName}.{$ext}";
						
						$thumbPath="{$galleryPath}thumbs/{$photoName}.{$ext}";
						$dataPath="{$galleryPath}data/{$photoName}.txt";
						
						move_uploaded_file($_FILES['files']['tmp_name'][$key],$originalPath);
						
						$img=new ImageProps();
						$img->load($originalPath);
						$img->resize($thumbWidth,$thumbHeight);
						$imageType = $img->getImageType($thumbPath);
						$img->save($thumbPath,$imageType);
						
						file_put_contents($dataPath,"filename={$fName}\n");
					}
				}
				echo "DONE<script>parent.uploadMsg('DONE');</script>";
			} else {
				echo "error: Gallery Not Found<script>parent.uploadMsg('error: Gallery Not Found');</script>";
			}
		} else {
			echo "error: Reference Not Found<script>parent.uploadMsg('error: Reference Not Found');</script>";
		}
		break;
	case "deletePhoto":
		if(isset($_POST['photo']) && isset($_POST['gallery'])) {
			$photos=$_POST['photo'];
			$gallery=$_POST['gallery'];
			
			$photos=explode(",",$photos);
			
			foreach($photos as $p) {
				$ext=explode(".",$p);
				$ext=strtolower(end($ext));
				
				$pArr=[
					"{$galleryDir}{$gallery}/thumbs/{$p}",
					"{$galleryDir}{$gallery}/large/{$p}",
					"{$galleryDir}{$gallery}/data/".str_replace(".{$ext}",".txt",$p),
				];
				foreach($pArr as $f) {
					if(file_exists($f)) {
						unlink($f);
					}
				}
			}
			echo "Delete Successfull.";
		} else {
			echo "error: Reference Not Found";
		}
		break;
	case "updatePhoto":
		if(isset($_POST['photo']) && isset($_POST['gallery'])) {
			$photo=$_POST['photo'];
			unset($_POST['photo']);
			$gallery=$_POST['gallery'];
			unset($_POST['gallery']);
			unset($_POST['photohash']);
			
			$ext=explode(".",$photo);
			$ext=strtolower(end($ext));
			$dataFile="{$galleryDir}{$gallery}/data/".str_replace(".{$ext}",".txt",$photo);
			//printArray($dataFile);
			
			$def=getDefaultPhotoParams();
			$data=array_merge($def,$_POST);
			
			$q=[];
			foreach($data as $a=>$b) $q[]="$a=".str_replace("'","`",$b);
			$q=implode("\n",$q);
			
			$ans=file_put_contents($dataFile,$q);
			if($ans>2) echo "Successfully updated photo properties.";
			else echo "error: Update failed. Try again later.";
		} else {
			echo "error: Reference Not Found";
		}
		break;
}
function recursiveRemoveDirectory($directory) {
    foreach(glob("{$directory}/*") as $file) {
        if(is_dir($file)) { 
            recursiveRemoveDirectory($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}
function repairGallery() {
	
}
?>