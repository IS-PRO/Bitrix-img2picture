<?php

namespace IS_PRO\img2picture;

class CacheProvider extends \Bitrix\Main\Data\StaticCacheProvider
{

    /**
     * @return string
     */
    public function getCachePrivateKey()
    {
        return self::getCachePrefix();
    }

    /**
     *
     */
    public function setUserPrivateKey()
    {
        \CHTMLPagesCache::setUserPrivateKey(self::getCachePrefix(), 0);
    }

    public function isCacheable()
    {
        return true;
    }

    public static function getCachePrefix()
    {
        return '';
    }

    public function onBeforeEndBufferContent()
    {
        \CHTMLPagesCache::onBeforeEndBufferContent();
        //$content = \IS_PRO\img2picture::doIt($content);
    }

    public static function getObject($SITE_ID = SITE_ID)
    {
        return new self();
    }
}
