<?php
/**
 * Created by PhpStorm.
 * User: 012550044
 * Date: 5/29/2018
 * Time: 12:08 PM
 */

global $machinesdir;


$GLOBALS['config']='
{
"modes": {
  "ThinStation": "ThinStation",
  "disk": "default"
  },
"modedefault": "disk",
"pxedir": "/tftpboot.bkp/pxelinux.cfg/",
"labsdir": "/tftpboot.bkp/pxelinux.cfg/labs/",
"departmentsdir": "/tftpboot.bkp/pxelinux.cfg/depts/",
"machinesdir": "/tftpboot.bkp/pxelinux.cfg/",
"modesdir": "/tftpboot.bkp/pxelinux.cfg/modes/",
"isodir": "/tftpboot.bkp/",
"defaultmodepath": "/tftpboot.bkp/pxelinux.cfg/modes/default"
}
';

$GLOBALS['config'] = json_decode($GLOBALS['config'], true);
if ($GLOBALS['config'] == null) {
    echo "Unable to decode config file.\n";
    exit(1);
}

$config = $GLOBALS['config'];

foreach (glob($config["labsdir"]."{E*,IS*}",GLOB_BRACE) as $lab) {
    $array = (explode("/",$lab)); //splitting string containing full path to get lab name
    $lab = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
    $labs[] = $lab;
};


foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
    $array = (explode("/",$dept)); //splitting string containing full path to get lab name
    $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
    $depttmp["Name"] = $dept;

    //calculate number of labs associated with this department
    //$count = exec("ls -la ". $GLOBALS['config']['departmentsdir'] .$dept."/E*|wc -l");
    $count = count(array_diff(glob($GLOBALS['config']['departmentsdir'] .$dept."/"."{E*,IS*}",GLOB_BRACE), array('.', '..')));
    $depttmp["lab_count"] = $count;

    //calculate number of machines under this department
    $count = exec("ls -la ". $GLOBALS['config']['machinesdir'] ."01-*|grep ".$dept."|wc -l");
    $depttmp["machinecount"] = $count;


    $depts[] = $depttmp;
};

function ListMachines(){

    foreach (array_diff(glob($GLOBALS['config']["machinesdir"].'01-*'), array('.', '..')) as $id=>$machine) {
        $linkpath = $machine;
        $array = (explode("/",$machine)); //splitting string containing full path to get mac name
        $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

        $labpath = readlink($linkpath);
        $array = (explode("/",$labpath)); //splitting string containing full path to get lab name
        $deptname = array_values(array_slice($array, -2))[0]; //reading last index of the array which contains the mac name
        $labname = array_values(array_slice($array, -2))[1];

        $machinetmp["mac"] = pxetomac($machine);
        $machinetmp["dept"] = $deptname;
        $machinetmp["lab"] = $labname;
        $machines[] = $machinetmp;
    };
    return $machines;
}

function ListMachinesByFilter($filter){

    foreach (array_diff(glob($GLOBALS['config']["machinesdir"].'01-*'), array('.', '..')) as $id=>$machine) {
        $linkpath = $machine;
        $array = (explode("/",$machine)); //splitting string containing full path to get mac name
        $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

        $labpath = readlink($linkpath);
        $array = (explode("/",$labpath)); //splitting string containing full path to get lab name
        $deptname = array_values(array_slice($array, -2))[0]; //reading last index of the array which contains the mac name
        $labname = array_values(array_slice($array, -2))[1];

        if(stripos(pxetomac($machine),$filter) !==false || stripos($deptname,$filter) !==false || stripos($labname,$filter) !==false || $filter===""){
            $machinetmp["mac"] = pxetomac($machine);
            $machinetmp["dept"] = $deptname;
            $machinetmp["lab"] = $labname;
            $machines[] = $machinetmp;
        }
    };
    return $machines;
}

function ListMachine($mac){
    $pxemac = mactopxe($mac);

    foreach (array_diff(glob($GLOBALS['config']["machinesdir"].$pxemac), array('.', '..')) as $id=>$machine) {
        $linkpath = $machine;
        $array = (explode("/",$machine)); //splitting string containing full path to get mac name
        $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

        $labpath = readlink($linkpath);
        $array = (explode("/",$labpath)); //splitting string containing full path to get lab name
        $deptname = array_values(array_slice($array, -2))[0]; //reading last index of the array which contains the mac name
        $labname = array_values(array_slice($array, -2))[1];

        $machinetmp["mac"] = pxetomac($machine);
        $machinetmp["dept"] = $deptname;
        $machinetmp["lab"] = $labname;
        $machines[] = $machinetmp;
    };

    if(count($machines) >1){
        throw new Exception("fatal error: more than one machine exists with mac: ".$pxemac);
    }else{
        return $machines[0];
    }
}

function pxetomac($pxemac){
    $pxemac = substr($pxemac, 3);
    return str_replace('-',':',$pxemac);
}


function validate_keys($req,$keys){
    foreach ($keys as $key){
        $result = "";
        if( ! isset($req[$key]) ){
            $result = $result . $key ." not provided";
            continue;
        }

        $name = htmlspecialchars(strip_tags(trim($_POST[$key])));
        if(!($name && strpos($name, '.') == false && strpos($name, '..') == false && strpos($name, '/') == false)){
            $result = $result . "input value ". $name . " for field: " . $key . " is invalid, it must not contain dots or double dots";
        }
    }
    return $result;
}

function mactopxe($mac){
    return '01-' . str_replace(':','-',$mac);
}



function getRelativePath($from, $to)
{
    // some compatibility fixes for Windows paths
    $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
    $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
    $from = str_replace('\\', '/', $from);
    $to   = str_replace('\\', '/', $to);

    $from     = explode('/', $from);
    $to       = explode('/', $to);
    $relPath  = $to;

    foreach($from as $depth => $dir) {
        // find first non-matching dir
        if($dir === $to[$depth]) {
            // ignore this directory
            array_shift($relPath);
        } else {
            // get number of remaining dirs to $from
            $remaining = count($from) - $depth;
            if($remaining > 1) {
                // add traversals up to first matching dir
                $padLength = (count($relPath) + $remaining - 1) * -1;
                $relPath = array_pad($relPath, $padLength, '..');
                break;
            } else {
                $relPath[0] = './' . $relPath[0];
            }
        }
    }
    return implode('/', $relPath);
}