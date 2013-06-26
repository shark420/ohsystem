<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( isset($_POST["about_us_text"]) ) {
   $text = $_POST["about_us_text"];
   
   file_put_contents("../inc/about_us.php", $text);
}

$content = file_get_contents("../inc/about_us.php");

?>
<div align="center"> 
<h2>About Us</h2>
<form action="" method="post">
<table> 
 <tr>
  <td>
 <textarea class="ckeditor" cols="90" id="editor1" name="about_us_text" rows="20"><?=$content?></textarea>
 </td>
 </tr>
 <tr>
 <td>
 <div class="padTop"></div>
 <div>
   <input type="submit" value="Submit" name="about" class="menuButtons" /> 
   <a href="<?=$website?>?about_us" class="menuButtons">View</a>
 </div>
 <div class="padTop"></div>
 </td>
 </tr>
 </table>
 <script type="text/javascript" src="<?php echo $website;?>adm/editor.js"></script>
</form>
 </div>