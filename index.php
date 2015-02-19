<?php

/**
 * Simple Picture Manager
 *
 * @author Ibrar Turi
 * @version 1.1
 */

//turn on php error reporting
error_reporting(0);
//error_reporting(E_ALL);

$site_url                 = "http://localhost/simple-picture-manager/";  // url of this module
$site_image_url           = $site_url . "image";                  // url of the image folder
$site_thumb_url           = $site_url . "image/thumb";            // url of the thumb folder
$upload_url               = "./image/";                           // directory path of the image folder
$upload_thumb_url         = "./image/thumb/";                     // directory path of the thumb folder
$thumb_width              = 140;                                  // width of the thumb image
$thumb_height             = 140;                                  // height of the thumb image
$images_per_page          = 42;                                   // number of pictures to be displayed per page
$img_ext_arr              = array('jpg', 'jpeg', 'png', 'gif');   // extension of the images to be displayed only

$results                  = scandir_arr($upload_url, $img_ext_arr);


/**
 * function to get the alll images from above mentioned folder
 */
function scandir_arr($upload_url, $img_ext_arr) {
  $_arr = array_diff(scandir($upload_url), array('..', '.'));
  $res_arr = array();
  foreach ($_arr as $key => $value) {
    $path_parts = pathinfo($upload_url . '/' . $value);

    if (is_file($upload_url . '/' . $value) && in_array($path_parts['extension'], $img_ext_arr)) {
      $res_arr[$value] = filemtime($upload_url . '/' . $value);
    }
  }

  arsort($res_arr);
  $res_arr = array_keys($res_arr);

  return $res_arr;
}

/**
 * show human readable formate of the file size
 */
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

/**
 * creates a thumb for the image with defined width and height
 */
function createThumbnail($filename, $upload_url, $upload_thumb_url, $thumb_width, $thumb_height) {
  $final_width_of_image     = $thumb_width;
  $final_height_of_image    = $thumb_height;
  $path_to_image_directory  = $upload_url;
  $path_to_thumbs_directory = $upload_thumb_url;

  // check if thumbnail is already exits, if not then create on
  if ( !file_exists($path_to_thumbs_directory.$filename) ) {
    if(preg_match('/[.](jpg)$/', $filename)) {
      $im = imagecreatefromjpeg($path_to_image_directory . $filename);
    } else if (preg_match('/[.](gif)$/', $filename)) {
      $im = imagecreatefromgif($path_to_image_directory . $filename);
    } else if (preg_match('/[.](png)$/', $filename)) {
      $im = imagecreatefrompng($path_to_image_directory . $filename);
    }        

    $ox = imagesx($im);
    $oy = imagesy($im);
    $nx = $final_width_of_image;
    $ny = $final_height_of_image;
    $nm = imagecreatetruecolor($nx, $ny);
    imagecopyresized($nm, $im, 0,0,0,0,$nx,$ny,$ox,$oy);

    if(!file_exists($path_to_thumbs_directory)) {
      if(!mkdir($path_to_thumbs_directory)) {
        die("There was a problem. Please try again!");
      } 
    }

    imagejpeg($nm, $path_to_thumbs_directory . $filename);
  }
  $tn = $path_to_thumbs_directory . $filename;
  return $tn;
}

// for uploading image
$response = '';
if (isset($_FILES) && count($_FILES)>0) {
     
    $name     = $_FILES['file']['name'];
    $tmpName  = $_FILES['file']['tmp_name'];
    $error    = $_FILES['file']['error'];
    $size     = $_FILES['file']['size'];
    $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
   
    switch ($error) {
        case UPLOAD_ERR_OK:
            $valid = true;
            //validate file extensions
            if ( !in_array($ext, array('jpg','jpeg','png','gif')) ) {
                $valid = false;
                $response = 'Invalid file extension.';
            }
            //validate file size
            if ( ($size/1024/1024) > 2 ) {
                $valid = false;
                $response = 'File size is exceeding maximum allowed size.';
            }
            //upload file
            if ($valid) {
                $targetPath =  $upload_url . DIRECTORY_SEPARATOR. $name;
                move_uploaded_file($tmpName,$targetPath);
                header( 'Location: index.php' );
                exit;
            }
            break;
        case UPLOAD_ERR_INI_SIZE:
            $response = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $response = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $response = 'The uploaded file was only partially uploaded.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $response = 'No file was uploaded.';
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $response = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $response = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
            break;
        case UPLOAD_ERR_EXTENSION:
            $response = 'File upload stopped by extension. Introduced in PHP 5.2.0.';
            break;
        default:
            $response = 'Unknown error';
        break;
    }

    //echo $response;
}


// for deleting image
if(isset($_POST) && !empty($_POST['file_name']) && !empty($_POST['act']) && $_POST['act']==1) {

  $fileName = $_POST['file_name'];
  $uploadPath = $upload_url .'/'.$fileName;
  $thumbPath = $upload_url .'/thumbs/'.$fileName;
  
  if ( file_exists($uploadPath) ) {
    unlink($uploadPath);
    unlink($thumbPath);
    header('Location:index.php');
  }
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Picture Manager</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/blueimp-gallery.min.css">
    <link rel="stylesheet" href="css/bootstrap-image-gallery.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">

  </head>

  <body>

    <!-- Static navbar -->
    <div class="navbar navbar-default navbar-static-top">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="index.php">Picture Manager</a>
        </div>
      </div>
    </div>


    <div class="container">

      <?php if($response): ?>
        <div class="row">
          <div class="col-lg-12">
            <div class="alert alert-danger" role="alert">
              <span class="sr-only">Error:</span>
              <?php echo $response; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="row">
        <div class="col-lg-12">
           <form class="well" action="index.php" method="post" enctype="multipart/form-data">
              <div class="form-group">
                <label for="file">Select a file to upload</label>
                <input type="file" id="file" name="file" />
                <p class="help-block">Only jpg,jpeg,png and gif file with maximum size of 1 MB is allowed.</p>
              </div>
              <input type="submit" class="btn btn-lg btn-primary" value="Upload">
            </form>
        </div>
      </div>

      <div class="row">
		     <div id="links">
           <?php
           		
              $pages = array_chunk($results, $images_per_page);
              
              if(count($pages) > 0) {
                $pn = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;
                foreach ($pages[($pn-1)] as $result) {
                  if ($result === '.' or $result === '..' or $result === 'thumbs') continue;

                    list($width, $height, $type, $attr) = getimagesize($upload_url . '/' . $result);
                    $file_size = human_filesize(filesize($upload_url . '/' . $result));
                    $thumbnail = createThumbnail($result, $upload_url, $upload_thumb_url, $thumb_width, $thumb_height);
                    $tooltip_str = "<table class='table table-condensed table-hover'>
                                      <tr>
                                        <th width='25%'>Url:</th>
                                        <td>{$site_image_url}{$result}</td>
                                      </tr>
                                      <tr>
                                        <th>Width:</th>
                                        <td>{$width}px</td>
                                      </tr>
                                      <tr>
                                        <th>height:</th>
                                        <td>{$height}px</td>
                                      </tr>
                                      <tr>
                                        <th>Size:</th>
                                        <td>{$file_size}</td>
                                      </tr>
                                    </table>";
                    echo '
                    <div class="col-md-3">
                      <div class="thumbnail">
                        <a href="'.$site_image_url . '/' . $result.'" title="'.$site_image_url . '/' .  $result.'" data-gallery="">
                          <img src="'.$thumbnail.'" alt="...">
                        </a>
                        <div class="caption text-center">
                          <p>
                            <button type="button" class="btn btn-danger btn-xs" data-act="delete" data-file="'.$result.'">Delete</button>
                            <button type="button" class="btn btn-primary btn-xs" data-container="body" data-toggle="popover" data-placement="top" 
                              data-content="'.$tooltip_str.'">
                              Info
                            </button>
                          </p>
                        </div>
                      </div>
                    </div>';
                }
            }
  				
           ?>
        </div>
      </div>
			
			<div class="row">
  			<div class="col-lg-12">
    			<?php 
            $page_html = '';
            for ($i = 1; $i < count($pages)+1; $i++) {
              $sel = (isset($_GET['page']) && (int)$_GET['page']==$i) ? 'class="active"' : '';
              $page_html .= '<li><a '.$sel.' href='.$site_url .'index.php?page='.$i . '>'.$i.'</a></li>';
            }	
            if($page_html) {
              echo '<div class="text-center"><ul class="pagination">'.$page_html.'</ul></div>';       			
            }
          ?>
  			</div>
      </div>

    </div> <!-- /container -->




<!-- The Bootstrap Image Gallery lightbox, should be a child element of the document body -->
<div id="blueimp-gallery" class="blueimp-gallery">
    <!-- The container for the modal slides -->
    <div class="slides"></div>
    <!-- Controls for the borderless lightbox -->
    <h3 class="title"></h3>
    <a class="prev">‹</a>
    <a class="next">›</a>
    <a class="close">×</a>
    <a class="play-pause"></a>
    <ol class="indicator"></ol>
    <!-- The modal dialog, which will be used to wrap the lightbox content -->
    <div class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"></h4>
                </div>
                <div class="modal-body next"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left prev">
                        Previous
                    </button>
                    <button type="button" class="btn btn-primary next">
                        Next                     
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="js/jquery.min.js"></script>
<script src="js/jquery.blueimp-gallery.min.js"></script>
<script src="js/bootstrap-image-gallery.min.js"></script>

<script type="text/javascript" src="js/tooltip.js"></script>
<script type="text/javascript" src="js/popover.js"></script>

<script type="text/javascript">
  $(function () {
    $('[data-toggle="popover"]').popover({'html':'ture', 'trigger':'click'})
    .on('show.bs.popover', function () {
       $('[data-toggle="popover"]').popover('hide');
    });

    $('button[data-act=delete]').bind('click', function() {
      var file_name = $(this).attr('data-file');
      if (confirm("Are you sure want to delete this file?") == true) {
        $('#file_name').val(file_name);
        $('#act').val('1');
        $('#frm_file_delete').submit();
      }
    });

  });

</script>

  <form method="post" action="index.php" id="frm_file_delete">
    <input type="hidden" name="file_name" id="file_name">
    <input type="hidden" name="act" value="0" id="act">
  </form>
  </body>
</html>
