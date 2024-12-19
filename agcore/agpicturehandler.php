<?php

class agPictureHandler extends oxUtils
{
    public static function getInstance()
    {
        return AGF::get('oxpicturehandler');
    }
}