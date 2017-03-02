<?php
define('IN_ECS', true);
require('../includes/init.php');
include_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/lib_common.php');
do{
	$facility_id = $_REQUEST['facility_id'];

//	if(!isset($facility_id) || $facility_id ==""){
//	    $final['message'] =  '请选择仓库';
//		break;
//	}

	$json = new JSON();
	$fileElementName = 'fileToUpload';
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
    $final = array();
	$final['message'] = '';	
    $final['data'] = array();
	$config = array( 0=>array('barcode'=>'商品条码', 'goods_number'=>'数量', 'facility_status'=>'库存状态','batch_sn'=>'批次号','facility_name'=>'仓库','comment'=>'备注'));
				
	if (!$uploader->existsFile ('fileToUpload')) {
		$final['message'] =  '没有选择上传文件，或者文件上传失败';
		break;
	}	
    //取得要上传的文件句柄
	$file = $uploader->file ( 'fileToUpload' );
	// 检查上传文件
	if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
		$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
		break;
	}
	// 读取excel
	if($final['message'] == ""){
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
			break;
		}
	}
	if($final['message'] == ""){
		$rowset = $result [0];
		if (empty ( $rowset )) {
			$final['message'] = "excel文件中没有数据,请检查文件";
		}
	}

	if($final['message'] == ""){
		foreach ($rowset as $row) {
			$added_good = array();
	        if($row['barcode'] == ''){
	        	 $final['message'] = "excel文件中商品条码不能为空,请检查文件！";
            	  break;
	        }
            // 通过商品条码查询商品的样式和商品名称以及ID
            
            $barcode = trim($row['barcode']);
            
            $sql = "
	                SELECT g.goods_name as goods_name,g.goods_id as goods_id,IFNULL(gs.style_id, 0) as style_id,IFNULL(s.color,'无颜色') as style_name
	                FROM {$ecs->table('goods')} AS g
	                LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = g.goods_id  
	                LEFT JOIN {$ecs->table('style')} AS s ON s.style_id = gs.style_id
	                WHERE IFNULL(gs.barcode,g.barcode) = '{$barcode}' and g.is_delete = 0
	                 AND   g.goods_party_id = '{$_SESSION['party_id']}' limit 1
	               ";
            $result =  $GLOBALS['db']->getRow($sql);
            if($result){
                 $added_good[3] = $result['style_id'];  //样式ID
                 $added_good[2] = $result['style_name'];//样式value
                 $added_good[0] = $result['goods_name']; //商品name
                 $added_good[1] = $result['goods_id'];   //商品ID
                 $goods_id = $result['goods_id'];
                 $style_id = $result['style_id']; 
            }
            else {
            	  $final['message'] = "excel文件中商品条码:【"."{$row['barcode']}"."】不存在,请检查文件！";
            	  break;
            }
            if( $row['goods_number'] != 0 && $row['goods_number'] != ''){
               $added_good[4] = $row['goods_number'];//数量
            }else {
                 $final['message'] = "excel文件中申请数量不能为0！";
            	 break;
            }
            $added_good[5] = $row['barcode'];//商品条码
            
            if($row['facility_status']=='正式库' || $row['facility_status'] =='二手库' ){
                 $added_good[7] = $row['facility_status'];//仓库状态
			    if($row['facility_status'] == '正式库'){
				    $added_good[8] = 'INV_STTS_AVAILABLE';
			    }else {
				$added_good[8] = 'INV_STTS_USED';
			    }
            }else {
            	 $final['message'] = "excel文件中仓库状态只能为【正式库】或【二手库】！";
            	 break;
            }
          
            $batch_sn = (string)$row['batch_sn'];

            $batch = getBatchSn($goods_id,$style_id,$facility_id,$_SESSION['party_id']);
            if(count($batch) > 0 ){
            	if( !isset($batch_sn) || trim($batch_sn) ==""){
            		$final['message'] = "商品条码 {$row['barcode']} 需要填写批次号：".implode(",",$batch);
            		break;
            	}else{
            		if(!in_array(trim($batch_sn),$batch)){
            			$final['message'] = "商品条码 {$row['barcode']} 批次号填写错误 可用批次号：".implode(",",$batch);
            		    break;
            		}
            	}
            }
//            else {
//            	if(strlen(trim($batch_sn)) > 0){
//            		$final['message'] = "商品条码 {$row['barcode']} 不是批次号维护的商品 ！";
//            		break;
//            	}
//            }
            $added_good[9] = $batch_sn;
            
           if($row['facility_name'] != ''){
              
               $facility = trim($row['facility_name']);
               $sql  = "SELECT facility_id from romeo.facility where FACILITY_NAME = '{$facility}' and IS_CLOSED = 'N' limit 1";
               $facility_id_t =  $GLOBALS['db']->getOne($sql);
                             
               if(empty($facility_id_t)){
                 	$final['message'] = "请确认填写的仓库是否正确！".$row['facility_name'];
            	    break;
               }
               $added_good[10] = $facility_id_t;
               $added_good[12] = trim($row['facility_name']);
               
            }else {
                 $final['message'] = "请填写仓库！";
            	 break;
            }
            
            if( $row['comment'] != ''){
               $added_good[11] = $row['comment'];//数量
            }else {
                 $final['message'] = "请填写调整原因";
            	 break;
            }           
			array_push($final['data'],$added_good);
		}
	}
}while (false);
    
echo $json->encode($final);

 // 获取 batch sn 
function getBatchSn($goods_id,$style_id,$facility_id,$party_id){
	 
    $sql = "SELECT DISTINCT(ii.batch_sn) 
        FROM romeo.inventory_item ii 
        INNER JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
        WHERE  pm.ecs_goods_id = '{$goods_id}' AND  pm.ecs_style_id = '{$style_id}' 
        AND ii.facility_id ='{$facility_id}' and ii.batch_sn is not null " ;
    $data = array();
    $result  =  $GLOBALS['db']->getAll($sql);
    foreach ($result as $key => $value) {
      if(isset($value['batch_sn']) && trim($value['batch_sn'])!=""){
          $data[] = $value['batch_sn']; 
      }
    }
    return $data;
}
    
?>

