<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\Flag;

class IsOriginal
{
    /**
     *
     */
    private bool $flag = false;

    /**
     * 
     */
    public function __invoke(?bool $flag = null): ?bool
    {
        if ($flag !== null) {
            $this->flag = $flag;
            return null;
        } else {
            return $this->flag;
        }
    }
}