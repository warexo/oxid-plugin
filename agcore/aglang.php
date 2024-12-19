<?php

class agLang extends oxLang
{
    public static function getInstance() {
        return AGF::getLang();
    }
}