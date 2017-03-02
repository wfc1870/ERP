<?php
define('IN_ECS', true);
require_once('../../includes/init.php');
require_once ('../monitor_tools.php');

$monitor_header = new MonitorHeader("销售订单商品列表监控页",array('order_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_id或order_sn');
}else
{
	if(!empty($_REQUEST['order_id'])){
		$order_id = $_REQUEST['order_id'];
	}else{
		$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['order_sn']}'";
		$order_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GenerateOrderInfo($order_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateOrderInfo($order_id){
	global $db;
	$data_for_generate = array();

	//ecshop.ecs_order_info
	$sql = "SELECT order_type_id, pay_status, 
				goods_amount, shipping_fee, pack_fee, additional_amount, order_amount
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info', $sql, 'order_id');
	$data_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_order_goods
	$sql = "SELECT rec_id, goods_name, style_id, goods_price, goods_number
			from ecshop.ecs_order_goods
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_goods', $sql, 'rec_id');
	$data_for_generate[] = $result['monitor_info'];

	//romeo.order_inv_reserved
	$sql = "SELECT status
			from romeo.order_inv_reserved
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'romeo.order_inv_reserved', $sql, 'order_inv_reserved_id');
	$data_for_generate[] = $result['monitor_info'];

	require_once('order_action_basic.php');
	return array_merge($data_for_generate, GenerateOrderActionBasicData($order_id));
}
?>