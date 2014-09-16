<?php
interface BidProductInterface {
	public function bidProcess($params);
	public function insertBidRecord($inParams);
	public function delBidRecord($in);
	public function readBidRank($params);
	public function getBidRankInfo($inParams);
	public function hasBid($params);
	public function getBidFinanceInfo($accountId);
	public function cutBidFinance($params);
	public function rollbackFinance($accountId, $curBidPrice, $lastBidPrice);
	public function getBidRecordInfoById($bidId);
	public function updateBidRecord($inParams);
	public function getRankIsChangeBidList($conParams);
	public function updateProduct($params);
}

?>