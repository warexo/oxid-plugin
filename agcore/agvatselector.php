<?php

class agVatSelector
{
    public static function getInstance()
    {
        return AGF::get('oxvatselector');
    }
}