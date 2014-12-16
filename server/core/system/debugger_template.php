<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * System debugger result template
 * 
 * @package  Seezoo-Framework
 * @category system
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

?>

<!-- Dog debug profiler section -->
<style type="text/css">
#<?php echo $id;?>{padding:15px;border:solid 1px #ccc;margin:50px 10px 10px 10px;font-size:16px;-webkit-border-radius:5px;
						-moz-border-radius:5px;-ms-border-radius:5px;-o-border-radius:5px;border-radius:5px;
						-webkit-box-shadow:5px 5px 5px #bbb;font-family:sans-serif;}
#<?php echo $id;?> h2{margin:15px 0;border-bottom:solid 1px #187FC4;padding-bottom:10px;text-align:left;}
#<?php echo $id;?> ol{padding:0px;margin:15px 0;}
#<?php echo $id;?> ol li{list-style:none inside;border-bottom:dotted 1px #ccc;padding:3px 10px;margin:0 5px;}
#<?php echo $id;?> ol li:hover, table tr:hover td{background:#eee;}
#<?php echo $id;?> table{width:100%;border:solid 1px #ccc;border-collapse:collapse;}
#<?php echo $id;?> table th{padding:10px;border:solid 1px #ccc;background:#003856;color:#fff;}
#<?php echo $id;?> table td{text-align:center;border:solid 1px #ccc;padding:8px;}
#<?php echo $id;?> table td span{font-weight:bold;font-size:20px;}
#<?php echo $id;?> table th span{font-weight:bold;font-size:20px;}
#<?php echo $id;?> table td p{margin:5px 0;}
</style>
<div id="<?php echo $id;?>">

<h2>Debug variables:</h2>
<ol id="debugger_toggle">
<?php if ( count($this->_storedVars) > 0 ):?>
<?php foreach ( $this->_storedVars as $var ):?>
<li><pre><?php echo prep_str($this->_dump_var($var));?></pre></li>
<?php endforeach;?>
<?php else:?>
<li>No debug vars.</li>
<?php endif;?>
</ol>

<h2>System processes:</h2>
<table>
<tbody>
<tr>
<th>process number</th>
<th>process info</th>
<th>execution time</th>
</tr>
<?php foreach ( array_reverse(Seezoo::getInstancesForDebug()) as $key => $process ):?>
<tr>
<td width="33%"><?php echo $key + 1;?></td>
<td width="34%">
<?php if ( $process->mode == SZ_MODE_MVC ):?>
<p><span><?php echo prep_str(ucfirst($process->router->getInfo('class')));?></span> class loaded.</p>
<p><span><?php echo prep_str($process->router->getInfo('execMethod'));?></span> method executed.</p>
<?php if ( count($process->router->getInfo('arguments')) > 0 ):?>
<p><span><?php echo implode(', ', $process->router->getInfo('arguments'));?></span> arguments passed.</p>
<?php else:?>
<p>no arguments passed.</p>
<?php endif;?>
<?php elseif ( $process->mode == SZ_MODE_ACTION ):?>
<p><span><?php echo prep_str($process->pathInfo);?></span> action executed.</p>
<?php endif;?>
</td>
<td width="33%"><span><?php echo $marks['process:' . $process->level . ':end'];?></span> (sec)</td>
</tr>
<?php endforeach;?>
<tr>
<th colspan="3" style="background:#0075A9">
Total execution time : <span><?php echo $marks['final'];?></span>&nbsp;(sec),&emsp;Memory uses : <span><?php echo round($memory_usage / 1024, 3);?>&nbsp;</span>(KB)
</th>
</tr>
</tbody>
</table>

<h2>Request parameters:</h2>
<table>
<tbody>
<tr><th>parameter name</th><th>variable info</th></tr>
<tr>
<td width="30%">POST</td><td width="70%" style="text-align:left;padding:10px 20px;"><pre><?php echo prep_str($this->_dump_var($_POST));?></pre></td>
</tr>
<tr>
<td width="30%">GET</td><td width="70%" style="text-align:left;padding:10px 20px;"><pre><?php echo prep_str($this->_dump_var($_GET));?></pre></td>
</tr>
</tbody>
</table>

<h2>Execute queries:</h2>
<table>
<tbody>
<?php $dbs = Seezoo::getDB();?>
<?php if ( count($dbs) > 0 ):?>
  <?php foreach ( $dbs as $group => $db ):?>
<tr>
<th colspan="2"><?php echo prep_str($group);?></th>
</tr>
    <?php if ( count($db->getQueryLogs()) > 0 ):?>
    <?php foreach ( $db->getQueryLogs() as $sql ):?>
<tr>
<td width="80%"><?php echo prep_str($sql['query']);?></td>
<td width="20%"><?php echo prep_str($sql['exec_time']);?> (sec)</td>
</tr>
    <?php endforeach;?>
    <?php else:?>
<tr>
<td colspan="2">No SQLs.</td>
</tr>
    <?php endif;?>
  <?php endforeach;?>
<?php else:?>
<tr>
<td colspan="2">Database not used.</td>
</tr>
<?php endif;?>
</tbody>
</table>
</div>