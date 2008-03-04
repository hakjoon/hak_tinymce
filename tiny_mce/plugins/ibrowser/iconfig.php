<?php
include '../../../config.php';
include '../../../lib/txplib_db.php';
include '../../../lib/txplib_head.php';
include '../../../lib/txplib_misc.php';
extract(get_prefs());

$imagedirectory = $img_dir.'/';

// ================================================
// tinymce PHP WYSIWYG editor control
// ================================================
// Configuration file
// ================================================
// Developed: j-cons.com, mail@j-cons.com
// Copyright: j-cons (c)2004 All rights reserved.
// ------------------------------------------------
//                                   www.j-cons.com
// ================================================
// changed by Josef Klimosz, ok2wo@centrum.cz
// v.1.10, 2005-01-03
// ================================================


$root = $path_to_site . '/';
// base url for images - must match with base document root
//$tinyMCE_base_url = 'http://yoursite.com/';
$tinyMCE_base_url = 'http://'.$siteurl.'/';
  
// image library related config

// allowed extentions for uploaded image files
$tinyMCE_valid_imgs = array('gif', 'jpg', 'jpeg', 'png');

// allow upload in image library
$tinyMCE_upload_allowed = false;

// allow delete in image library
$tinyMCE_img_delete_allowed = false;

//examples of image folders setting
$tinyMCE_imglibs=array();

$tinyMCE_imglibs[]= array(
		'value'=>"$imagedirectory", //trailing slash
		'text' =>'Blog Images');


//  $tinyMCE_imglibs[] = array(
//                              'value'   => 'images/', //trailing slash
//                               'text'    => 'Pictures 2');
//                               
//  $tinyMCE_imglibs[] = array(
//                              'value'   => 'gallery/', //trailing slash
//                               'text'    => 'Pictures 3'); 
//
// file to include in img_library.php (useful for setting $tinyMCE_imglibs dynamically
// $tinyMCE_imglib_include = '';
?>
