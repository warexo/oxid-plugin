<?php

class agUtilsServer
{
    public static function getInstance()
    {
        return AGF::get('oxutilsserver');
    }
}