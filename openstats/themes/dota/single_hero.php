<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

//Get data from playdota website
if ($PlayDotaHeroes == 1) {
  if (file_exists( "inc/cache/pdheroes/".$pdHero.".html" ) )
  include( "inc/cache/pdheroes/".$pdHero.".html" );
} else {

//Get hero data from database
if ( isset($HeroData) AND !empty($HeroData) ) {

foreach ( $HeroData as $Hero) {
?>
<div align="center">
<table>
  <tr>
   <td width="100"><img class="imgvalign" src="<?=$website?>img/heroes/<?=$Hero["original"]?>.gif" alt="" /></td>
   <td width="400"><h2><?=$Hero["description"]?></h2></td>
  </tr>
</table>

<div class="padTop"></div>
<table>
   <tr>
     <th width="500" class="padLeft"><?=$lang["description"]?></th>
   </tr>
   <tr>
     <td width="500" class="padLeft"><?=$Hero["summary"]?></td>
   </tr>
</table>


<div class="padTop"></div>
<table>
   <tr>
     <th width="500" class="padLeft"><?=$lang["description"]?></th>
   </tr>
   <tr>
     <td width="500" class="padLeft"><?=$Hero["stats"]?></td>
   </tr>
</table>
<div class="padTop"></div>
<table>
   <tr>
     <th width="500" class="padLeft"><?=$lang["skills"]?></th>
   </tr>
   <tr>
     <td width="500" class="padLeft"><?=$Hero["skills"]?></td>
   </tr>
</table>

<?php
  if ( $GuidesPage == 1 AND isset($HeroDataGuides) AND !empty($HeroDataGuides) ) {
  ?>
  <table>
    <tr>
      <th class="padLeft"><?=$lang["guides"]?></th>
    </tr>
  <?php
  foreach ( $HeroDataGuides as $Guide) {
  ?>
    <tr>
       <td class="padLeft">&raquo; <a href="<?=$Guide["link"]?>" target="_blank"><?=$Guide["title"]?></a></td>
    </tr>
  <?php } ?>
  </table>
  <?php
  }
?>
</div>
<?php 
      }
   } 
}
?>