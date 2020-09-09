<?php
namespace Lightroom\Templates\Happy\Interfaces;

/**
 * @package CommonInterface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface CommonInterface
{
    /**
     * @method CommonInterface lockCalls
     * @return void
     * 
     * This lock calls to class methods
     */
    public function lockCalls() : void;

    /**
     * @method CommonInterface unlockCalls
     * @return void
     * 
     * This unlockss calls to class methods
     */
    public function unlockCalls() : void;

    /**
     * @method CommonInterface notLockedOut
     * @return bool
     * 
     * This would return true if calls to class methods hasn't been locked
     */
    public function notLockedOut() : bool;

    /**
     * @method CommonInterface lockedOut
     * @return bool
     * 
     * This would return true if calls to class methods has been locked
     */
    public function lockedOut() : bool;

    /**
     * @method CommonInterface getBase
     * @return bool
     * 
     * This method returns the base directory of the class
     */
    public function getBase() : string;
}