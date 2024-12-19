<?php

class agCounter extends oxCounter
{
    public static function getInstance()
    {
        return AGF::get('oxcounter');
    }
}