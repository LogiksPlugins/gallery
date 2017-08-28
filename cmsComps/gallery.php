<?php
//printArray($gallery);

if(SITENAME!="cms") {
	echo("ONLY CMS can access this service.");
	return;
}

$noEditCols=["blocked","created_by","created_on","edited_by","edited_on"];

$cfg=getDefaultGalleryParams();
$gallery['config']=array_merge($cfg,$gallery['config']);
?>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active"><a href="#gallery" aria-controls="gallery" role="tab" data-toggle="tab">Gallery <i class='fa fa-retweet refreshPhotos actionCMD'></i></a></li>
	<li role="presentation"><a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">Settings</a></li>
	<li class='pull-right' role="presentation"><a href="#upload" aria-controls="upload" role="tab" data-toggle="tab">Upload</a></li>
</ul>

<div class="tab-content">
	<div role="tabpanel" class="tab-pane active" id="gallery">
		<div id='galleryBox' class='galleryBox row'>
			<?php
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
			?>
		</div>
	</div>
	<div role="tabpanel" class="tab-pane" id="settings">
		<div style='max-width: 80%;margin: auto;margin-top:10px;'>
			<form class="form-horizontal">
				<div class="form-group">
					<label for="slug" class="col-sm-3 control-label">Gallery Code</label>
					<div class="col-sm-9">
						<p class="form-control-static"><?=$_POST['slug']?></p>
					</div>
				</div>
				<?php
					foreach($gallery['config'] as $key=>$val) {
						if(in_array($key,$noEditCols)) continue;
				?>
					<div class="form-group">
						<label for="<?=$key?>" class="col-sm-3 control-label"><?=toTitle($key)?></label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="<?=$key?>" placeholder="<?=toTitle($key)?>" value='<?=$val?>' />
						</div>
					</div>
				<?php
					}
				?>
				<div class="form-group">
					<label for="blocked" class="col-sm-3 control-label">Blocked</label>
					<div class="col-sm-9">
						<select class="form-control" name="blocked">
							<?php
								if($gallery['config']['blocked']=="true") {
									echo "<option value='false'>False</option><option value='true' selected>True</option>";
								} else {
									echo "<option value='false' selected>False</option><option value='true'>True</option>";
								}
							?>
						</select>
					</div>
				</div>
				<br>
				<div class="form-group">
					<div class="col-sm-offset-3 col-sm-9">
						<button onclick='saveGalleryProperties(this)' type="button" class="btn btn-default btn-success pull-right">Submit</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<div role="tabpanel" class="tab-pane" id="upload">
		<div class="uploadForm upload-drop-zone" id="drop-zone">
			<form id="uploadForm" action="<?=_service("gallery","uploadPhoto")?>" method="POST" enctype="multipart/form-data" target="uploadFrame">
				<input type='hidden' name='slug' value='<?=$_POST['slug']?>' />
				<input type='hidden' name='forsite' value='<?=$_GET['forsite']?>' />
				<div class="form-group">
					<input type="file" name="files[]" id="js-upload-files" multiple="" style="display: none;">
				</div>
				<div id="uploadMaskIcon">
					<i class="uploadMaskIcon glyphicon glyphicon-plus"></i>
				</div>
			</form>
			<div id="uploadLoader" class="ajaxloading ajaxloading5 hidden">UPLOADING ...</div>
		</div>
	</div>
</div>
<div tabindex="-1" class="modal fade" id="myModal" role="dialog">
  <div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header">
			<button class="close" type="button" data-dismiss="modal">×</button>
			<h3 class="modal-title">Preview</h3>
		</div>
		<div class="modal-body">

		</div>
   </div>
  </div>
</div>
<iframe id='uploadFrame' name='uploadFrame' style='display:none'></iframe>