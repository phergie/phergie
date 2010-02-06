@echo off
REM  Phergie 
REM 
REM  PHP version 5
REM 
REM  LICENSE
REM 
REM  This source file is subject to the new BSD license that is bundled
REM  with this package in the file LICENSE.
REM  It is also available through the world-wide-web at this URL:
REM  http://phergie.org/license

set PHPBIN="@php_bin@"
%PHPBIN% "@bin_dir@\phergie" %*
