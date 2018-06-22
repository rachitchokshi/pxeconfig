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

//scan for schedules and schedule function calls for each of them
$scheduler = new Scheduler();

foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept){
    $schedulefile = $GLOBALS['config']['departmentsdir'].$dept . '/.schedules';

    if(file_exists($schedulefile)){
        $json = json_decode(file_get_contents($schedulefile),true);

        //loop through all labs
        foreach ($json as $labname => $lab){
            //loop through all schedules of each lab
            foreach ($lab as $id => $sched){
                $args['dept'] = $dept;
                $args['lab'] = $lab;
                $args['mode'] = $sched['mode'];

                //schedule for start expression
                $args['startend'] = 'start';
                $scheduler->call('execute',$args,$dept.$labname.$id)->at($sched['start']);

                //schedule for end expression
                $args['startend'] = 'end';
                $scheduler->call('execute',$args,$dept.$labname.$id)->at($sched['end']);

            }
        }
    }
}

$scheduler->run();
