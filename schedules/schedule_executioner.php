<?php require_once __DIR__.'/../vendor/autoload.php';
use GO\Scheduler;
include '../config.php';
/**
 * Created by PhpStorm.
 * User: 012550044
 * Date: 6/21/2018
 * Time: 2:03 PM
 */

//function to execute changes
function execute($args){
    $dept = $args['dept'];
    $lab = $args['lab'];
    $mode = $args['mode'];
    $startend = $args['startend'];

    $labpath = $GLOBALS['config']['departmentsdir'].$dept.'/'.$lab;
    $modepath = $GLOBALS['config']['modesdir'].$mode;
    $defaultpath = $GLOBALS['config']['defaultmodepath'];
    if(file_exists($labpath) && file_exists($modepath)){
        if($startend === 'start'){
            unlink($labpath);
            symlink($modepath,$labpath);
        }else if($startend === 'end'){
            unlink($labpath);
            symlink($defaultpath,$labpath);
        }
    }
}

