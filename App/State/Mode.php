<?php
/**
 *
 */
namespace FishPig\CriticalCss\App\State;

class Mode
{
    /**
     * 
     */
    private $mode = null;
    
    /**
     * 
     */
    public function setMode(?string $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * 
     */
    public function getMode(): ?string
    {
        return $this->mode;
    }
}
