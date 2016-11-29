<?php
$res_array=array(
	'num-nodes'=>0,
	'online-nodes'=>0,
	'offline-nodes'=>0,
	'num-jails'=>0,
	'num-cores'=>0,
	'average'=>0,
	'sum-ram'=>0,
	'sum-storage'=>'Unknown',
	'error'=>false,
	'error_message'=>'',
);

$nodenames=array('local');
$db=new Db('base','nodes');
$nodes=$db->select('select nodename,ip from nodelist');
if(!empty($nodes))foreach($nodes as $node)
{
	$idle=$clonos->check_locktime($node['ip']);
	if($idle==0) $res_array['offline-nodes']++; else $res_array['online-nodes']++;
	
	$nodenames[]=$node['nodename'];
}

$res_array['num-nodes']=count($nodes);

if(!empty($nodenames))foreach($nodenames as $name)
{
	$ndb=new Db('base',trim($name));
	if($ndb===false)
	{
		echo json_encode(array('error'=>true,'error_message'=>$ndb->error_message));
		exit;
	}
	
	$jcounts=$ndb->selectAssoc('SELECT COUNT(*) as count FROM jails;');
	$res_array['num-jails']+=$jcounts['count'];
	
	$counts=$ndb->select('SELECT ncpu,physmem,cpufreq FROM local;');
	if(!empty($counts))foreach($counts as $cel)
	{
		$res_array['num-cores']+=$cel['ncpu'];
		$res_array['sum-ram']+=$cel['physmem'];
		$res_array['average']+=$cel['cpufreq'];
	}
}

if($res_array['average']>0)
{
	$res_array['average']=$clonos->GhzConvert($res_array['average']/($res_array['num-nodes']?:1));
}

$res_array['sum-ram']=$clonos->fileSizeConvert($res_array['sum-ram']*1024);

echo json_encode($res_array);