<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix" >
	 
	    <h2><?=$lang["compare_players"]?></h2>
		
		<?php if ( isset($_GET["empty"]) ) { ?>
		<h4><?=$lang["compare_empty_info"]?></h4>
		<div style="margin-bottom: 80px;">&nbsp;</div> <?php } ?>
		
		<?php foreach ($ComparePlayersData as $Player) { ?>
		<div class="comparePlayers">
		<table>
		    <tr>
			  <td align="center">
			  <div align="center">
			    <a href="<?=OS_HOME?>?u=<?=$Player["id"]?>"><?=$Player["player"]?></a>
			  </div>
			  </td>
			</tr>
		</table>
		<table>
			<tr>
			  <td><b><?=$lang["games"]?>:</b></td> <td><?=$Player["games"]?> 
			  <?=OS_CheckValue( $MostGames, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><b><?=$lang["win_percent"]?>:</b></td> <td><?=$Player["winslosses"]?> %
			  <?=OS_CheckValue( $MostWins, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><b><?=$lang["stay"]?>%:</b></td> <td><?=$Player["stayratio"]?> %
			  <?=OS_CheckValue( $MostStay, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["kd_ratio"], OS_HOME.'img/winner.png', 130, 32, 32)?>><b><?=$lang["kd"]?>:</b></span></td> <td><?=$Player["kd"]?>
			  <?=OS_CheckValue( $MostKD, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["kills_per_game"], OS_HOME.'img/winner.png', 130, 32, 32)?>><b><?=$lang["kpg"]?>:</b></span></td> <td><?=$Player["kpg"]?>
			  <?=OS_CheckValue( $MostKPG, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["assists_per_game"], OS_HOME.'img/winner.png', 180, 32, 32)?>><b><?=$lang["apg"]?>:</b></span></td> <td><?=$Player["apg"]?>
			  <?=OS_CheckValue( $MostAPG, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["creeps_per_game"], OS_HOME.'img/winner.png', 180, 32, 32)?>><b><?=$lang["ckpg"]?>:</b></span></td> <td><?=$Player["ckpg"]?>
			  <?=OS_CheckValue( $MostCK, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["denies_per_game"], OS_HOME.'img/winner.png', 180, 32, 32)?>><b><?=$lang["cdpg"]?>:</b></span></td> <td><?=$Player["cd"]?>
			  <?=OS_CheckValue( $MostCD, $Player["player"] )?>
			  </td>
			</tr>
			<tr>
			  <td><span <?=ShowToolTip($lang["neutrals_per_game"], OS_HOME.'img/winner.png', 180, 32, 32)?>><b><?=$lang["npg"]?>:</b></span></td> <td><?=$Player["ne"]?>
			  <?=OS_CheckValue( $MostNE, $Player["player"] )?>
			  </td>
			</tr>
		</table>
		</div>	
		<?php } ?>
	<div style="clear:both; width:100%">&nbsp;</div>	
	<script type="text/javascript">
      google.load('visualization', '1.0', {'packages':['corechart']});
      google.setOnLoadCallback(drawChart<?=$Player["id"]?>);
      function drawChart<?=$Player["id"]?>() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Topping');
        data.addColumn('number', 'Slices');
        data.addRows([
	  <?php foreach ($ComparePlayersData as $Player) { ?>
          ['<?=($Player["player"])?>', <?=($Player["points"])?> ],
	   	<?php } ?>
        ]);
        var options = {'title':'<?=$lang["overall"]?>',
                       'width':800,
                       'height':600};
        var chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script> 
	<div align="center" style="" id="chart_div"></div>

	   <div style="margin-bottom: 360px;">&nbsp;</div> 

</div>