<?php
/**
 * Coypright © 2012 Tuniu Inc. All rights reserved.
 * Author: chenjinlong
 * Date: 12/9/12
 * Time: 2:48 PM
 * Description: AbsRelease.php
 */
abstract class AbsRelease
{
    abstract function runRelease();

    abstract function runDeduct();

    abstract function runRefund();

    /**
     * 执行收客宝产品推广动作之流程约定
     */
    public function run()
    {
        $this->runRelease();
        $this->runDeduct();
        $this->runRefund();
    }

}
