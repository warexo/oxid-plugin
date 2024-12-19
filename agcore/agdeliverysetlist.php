<?php

class agDeliverySetList extends oxDeliverySetList
{
    public static function getInstance()
    {
        return AGF::get('oxdeliverysetlist');
    }
}