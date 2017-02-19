<?php
    
    
    namespace Think\Cache;
    
    
    use Psr\SimpleCache\CacheInterface;

    /**
     * 预留接口，兼容PSR-16，用于重写
     * Interface ICache
     * @package Think\Cache
     */
    interface ICache extends CacheInterface
    {
        
    }