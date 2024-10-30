<?php
/*
Plugin Name: Comicsgonebad
Plugin URI: http://comicsgonebad.com/
Description: Downloads and embeds the daily comics from ComicsGoneBad where you want it
Version: 1.1
Author: Piyush Mishra
Author URI: http://www.piyushmishra.com/
*/
define(__CGBDIR__,WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)));
add_action('admin_menu', 'piyush_admin_menu');
add_filter('the_content','myparse');
add_filter('http_headers_useragent','piyush_header');
add_filter('http_request_timeout','mytimeout');
register_activation_hook(__FILE__,'piyush_install');
register_deactivation_hook(__FILE__,'piyush_uninstall');
register_sidebar_widget('ComicsGoneBad','piyush_widget');
register_widget_control ( 'ComicsGoneBad','piyush_widget_ctrl' );
function piyush_install()
{
	add_option('cgbsize',300);
	add_option('cgbwidth',200);
	add_option('cgbchecktime200',0);
	add_option('cgbchecktime250',0);
	add_option('cgbchecktime300',0);
	add_option('cgbdefault','default');
	add_option('cgbfile','default');
	chmod(__CGBDIR__.'comic/',0777);
	downcomic();
}

function piyush_uninstall()
{
	$path=get_option('cgbfile');
	if($path!='default')
	{
		@unlink(__CGBDIR__.'comic/'.$path.'-200.jpg');
		@unlink(__CGBDIR__.'comic/'.$path.'-250.jpg');
		@unlink(__CGBDIR__.'comic/'.$path.'-300.jpg');
	}
	delete_option('cgbsize');
	delete_option('cgbwidth');
	delete_option('cgbchecktime200');
	delete_option('cgbdefault');
	delete_option('cgbfile');
	delete_option('cgbchecktime250');
	delete_option('cgbchecktime300');
	
}
function piyush_widget()
{?>
<li class="widget widget_text"><h2 class="widgettitle">Comics Gone Bad</h2><div class="widget-text"><center><?php echo gencode('w'); ?></center></div></li>
<?php
}

function piyush_widget_ctrl()
{?>
<?php
if(isset($_REQUEST['cgbaction']))
{
	$cwidth=$_REQUEST['cwidth'];
	if(ctype_digit($cwidth) && $cwidth<=200)
	{
	if(strlen(get_option('cgbwidth')))
		update_option('cgbwidth',$cwidth);
	else
		add_option('cgbwidth',$cwidth);
	}
	
}
$csize=get_option('cgbsize');
?>
	Enter the width of image you want on the sidebar. 
    You can reload the blog page and see if the image fits your screen.
    <br/><b>Width : </b> <input type="text" name="cwidth" value="<?php echo get_option('cgbwidth'); ?>" /><input type="hidden" name="cgbaction" value="add" /><br />
(max 200) and the image will be downgraded and may look jarred. we recommend using 200 default value. 
<?php
}
function mytimeout()
{

	return 30;
}
function piyush_header()
{
	return"Piyush Mishra";
}
function piyush_admin_menu() 
{
	add_options_page('options-general.php', 'ComicsGoneBad', 'administrator', 'cgbadmin', 'piyush_admin_page');
	
}
function dwnl($sizes)
{
	update_option('cgbchecktime'.$sizes,time());
	$fnm=gmdate('mdY');
	$file="http://comicsgonebad.com/distr/".$fnm."-".$sizes.".jpg";
	$myfile=__CGBDIR__.'comic/'.$fnm."-".$sizes.".jpg";
	$options =array('method'=>'HEAD','redirect'=>1);
	$response=wp_remote_request($file,$options);
	if($response['response']['code']==200)
	{
		$b=wp_remote_get($file);
		if(!is_wp_error($b))
		{
			$fp=fopen($myfile,"w");
			fwrite($fp,$b['body'],$b['headers']['content-length']);
			fclose($fp);
			$old=get_option('cgbfile');
			if($old!='default' && $old!=$fnm)
			{
			@unlink(__CGBDIR__.'comic/'.$old.'-'.$sizes.'.jpg');
			}
			update_option('cgbfile',$fnm);
			return true;
		}
	}
	return false;	
}
function downcomic()
{
	if(!is_file(__CGBDIR__.'comic/'.gmdate('mdY')."-200.jpg"))
	dwnl(200);
	if(!is_file(__CGBDIR__.'comic/'.gmdate('mdY')."-250.jpg"))
	dwnl(250);
	if(!is_file(__CGBDIR__.'comic/'.gmdate('mdY')."-300.jpg"))
	dwnl(300);
}

function gencode($type)
{	
	$file=get_option('cgbfile');
	$size=get_option('cgbsize');
	if(!is_file(__CGBDIR__.'comic/'.gmdate('mdY')."-".$size.".jpg"))
	{
		$lastcheck1=get_option('cgbchecktime200');
		if(time()-$lastcheck >3600)
		dwnl(200);
		$lastcheck1=get_option('cgbchecktime250');
		if(time()-$lastcheck >3600)
		dwnl(250);
		$lastcheck1=get_option('cgbchecktime300');
		if(time()-$lastcheck >3600)
		dwnl(300);
		$file=get_option('cgbfile');
		
	}
	if(!is_file(__CGBDIR__.'comic/'.$file.'-'.$size.'.jpg'))
	$file="default";
	$a= plugins_url('comic/'.$file.'-'.$size.'.jpg', __FILE__);
	$b= plugins_url('comic/'.$file.'-200.jpg', __FILE__);
	if($type=='c')
	return '<center><a href="http://www.comicsgonebad.com" target="_blank"  title="WebComics and Jokes"><img style="border:none; padding:10px;" src="'.$a.'"  alt="Comics Gone Bad" /></a></center>';
	elseif($type=='w')
	return '<a href="http://www.comicsgonebad.com" target="_blank"  title="WebComics and Jokes"><img style="border:none; padding:10px;" src="'.$b.'" width="'.get_option('cgbwidth').'" alt="Comics Gone Bad" /></a>';
}
function myparse($content)
{
	$code=gencode('c');
	$ret=str_replace('[cgb]',$code,$content);
	return $ret;
}

function piyush_admin_page() {
?>
<div class="wrap">
<h2>ComicsGoneBad Admin</h2>
<?php
if(isset($_REQUEST['cgbaction']))
{
	if(strlen(get_option('cgbsize')))
		update_option('cgbsize',$_REQUEST['csize']);
	else
		add_option('cgbsize',$_REQUEST['csize']);	
}
$csize=get_option('cgbsize');
?>
<form method="post" action="options-general.php?page=cgbadmin">

    <table class="form-table"><tr valign="top">
        <th scope="row">Description</th>
        <td>This plugin will download, store and embed the latest daily comics from ComicsGoneBad main site and allow you to add the comics to your post by a simple tag ( [cgb] ) and also allow adding of the latest image on the sidebar as a widget<br />
Please note that only the latest issue of daily comics will be downloaded and shown.<br />
To insert the comics in your post just add the tag [cgb] wherever you want the comics to appear on your blog...
<br />
To report any error please visit this link and post a comment there about the error/problem you face while using this plugin...
<a href="http://www.piyushmishra.com/plugins/cgb-plugin-test.html">http://www.piyushmishra.com/plugins/cgb-plugin-test.html</a>
</td>
        </tr>
        <tr valign="top">
        <th scope="row">Size of image in posts</th>
        <td><input type="radio"  name="csize" <?php if($csize==200) echo 'checked="checked"'; ?> value="200" /> : 200 by 200 px<br />
<input type="radio" name="csize" <?php if($csize==250) echo 'checked="checked"'; ?> value="250" /> : 250 by 250 px<br />
<input type="radio" name="csize" <?php if($csize==300) echo 'checked="checked"'; ?> value="300" /> : 300 by 300 px</td>
        </tr>
        
    </table>
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php } ?>
