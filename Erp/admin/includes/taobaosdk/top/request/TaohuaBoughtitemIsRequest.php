<?php
/**
 * TOP API: taobao.taohua.boughtitem.is request
 * 
 * @author auto create
 * @since 1.0, 2011-02-15 11:12:44.0
 */
class TaohuaBoughtitemIsRequest
{
	/** 
	 * 商品ID
	 **/
	private $itemId;
	
	private $apiParas = array();
	
	public function setItemId($itemId)
	{
		$this->itemId = $itemId;
		$this->apiParas["item_id"] = $itemId;
	}

	public function getItemId()
	{
		return $this->itemId;
	}

	public function getApiMethodName()
	{
		return "taobao.taohua.boughtitem.is";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
