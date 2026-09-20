<?php

function _bindDomain( $dir ) { $GLOBALS['mult-linguage']['path'] = $dir; }
function _setDomain( $domain ) { $GLOBALS['mult-linguage']['domain'] = $domain;  }
function _getDomain() { return $GLOBALS['mult-linguage']['domain'];  }
function _lang() {
    if( !empty( $_GET['translate'] ) ) {
        setcookie('TRANSLATE', $_GET['translate']);
    }
}
function _getLang() { return $_GET['translate'] ?? $_COOKIE['TRANSLATE'] ?? null; }
function _getTranslate( $domain = null) {
    $lang = _getLang();
    $domain = $domain ? $domain : _getDomain();
    $path  = $GLOBALS['mult-linguage']['path'] ?? null;
    if( $lang && $domain && $path ) {
        $file = "{$path}/{$lang}/{$domain}.php";
        if( file_exists($file) ) {
            return include $file;
        }
        return null;
    }
    return null;
}

function __t( $mesage, $domain = null )  {
    $translate = _getTranslate($domain);
    if ($translate) {
        return $translate[$mesage] ?? $mesage;
    } else {
        return  $mesage;
    }
}
