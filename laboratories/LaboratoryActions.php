<?php
include "../config.php";
try
{
	//Getting records (listAction)
	if($_GET["action"] == "list")
	{
	    if(isset($_POST['name']) && $_POST['name'] !== ''){
            foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
                $linkpath = $lab;

                $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $lab = array_values(array_slice($array, -1))[0];

                $bootmode = readlink($linkpath);
                $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
                $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                $labtmp["id"] = $dept.$lab;
                $labtmp["name"] = $lab;
                $labtmp["dept"] = $dept;
                $labtmp["mode"] = $bootmode;

                //calculate number of machines associated with this lab
                $count = exec("ls -la ". $GLOBALS['config']['machinesdir'] ."01-*|grep ".$lab."|wc -l");
                $labtmp["machinecount"] = $count;

                if(stripos($labtmp["name"],$_POST['name'])!==false || stripos($labtmp["dept"],$_POST['name'])!==false || stripos($labtmp["mode"],$_POST['name'])!==false){
                    $labstmp[] = $labtmp;
                }
            };
        }else{
            foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
                $linkpath = $lab;

                $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $lab = array_values(array_slice($array, -1))[0];

                $bootmode = readlink($linkpath);
                $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
                $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                $labtmp["id"] = $dept.$lab;
                $labtmp["name"] = $lab;
                $labtmp["dept"] = $dept;
                $labtmp["mode"] = $bootmode;

                //calculate number of machines associated with this lab
                $count = exec("ls -la ". $GLOBALS['config']['machinesdir'] ."01-*|grep ".$lab."|wc -l");
                $labtmp["machinecount"] = $count;

                $labstmp[] = $labtmp;
            };
        }


		//Return result to jTable
		$jTableResult = array();
		$jTableResult['Result'] = "OK";
		$jTableResult['Records'] = $labstmp;
		print json_encode($jTableResult);
	}
	//Creating a new record (createAction)
	else if($_GET["action"] == "create")
	{
        $result = validate_keys($_POST,['name','dept','mode']);
        if ($result !== ""){
            throw new Exception($result);
        }else {
            $_POST['name'] = strtoupper($_POST['name']);

            $labpath = $GLOBALS['config']['departmentsdir'] . $_POST['dept'] .'/'. $_POST['name'];
            $modepath = $GLOBALS['config']['modesdir'] . $_POST['mode'];

            if (symlink($modepath,$labpath)) {

                foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
                    $linkpath = $lab;

                    $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                    $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                    $lab = array_values(array_slice($array, -1))[0];

                    $bootmode = readlink($linkpath);
                    $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
                    $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                    if($lab === $_POST['name'] && $dept === $_POST['dept']){
                        $labtmp["id"] = $dept.$lab;
                        $labtmp["name"] = $lab;
                        $labtmp["dept"] = $dept;
                        $labtmp["mode"] = $bootmode;

                        //calculate number of machines associated with this lab
                        $count = exec("ls -la ". $GLOBALS['config']['machinesdir'] ."01-*|grep ".$lab."|wc -l");
                        $labtmp["machinecount"] = $count;

                        break;
                    }
                };

                $jTableResult['Result'] = "OK";
                $jTableResult['Record'] = $labtmp;

            } else {
                throw new Exception("Error occured while creating symlink: " . $labpath . ' to target: ' . $modepath);
            }
            print json_encode($jTableResult);
        }
	}
	//Updating a record (updateAction)
	else if($_GET["action"] == "update")
	{
		//Update folder name in filesystem
        $result = validate_keys($_POST,['name','dept','mode']);
        if ($result !== ""){
            throw new Exception($result);
        }else{

            foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
                $linkpath = $lab;

                $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $lab = array_values(array_slice($array, -1))[0];

                $bootmode = readlink($linkpath);
                $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
                $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                $id = $dept.$lab;
                //settype($_POST['id'],"int");
                if($id === $_POST['id']){
                    $oldpath = $GLOBALS['config']['departmentsdir'].$dept.'/'.$lab;
                    $labpath = $GLOBALS['config']['departmentsdir'] . $_POST['dept'] .'/'. $_POST['name'];
                    $modepath = $GLOBALS['config']['modesdir'] . $_POST['mode'];

                    break;
                }
            };



            if($oldpath && $labpath && $modepath){

                //find machines linked to the oldpath and re-link them to the new path
                foreach (array_diff(glob($GLOBALS['config']["machinesdir"].'01-*'), array('.', '..')) as $id=>$machine) {
                    $linkpath = $machine;
                    $array = (explode("/",$machine)); //splitting string containing full path to get mac name
                    $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name



                    if(readlink($linkpath) === $oldpath){
                        unlink($linkpath);
                        symlink($labpath,$linkpath);
                    };
                };
                unlink($oldpath);

                if (symlink($modepath,$labpath)) {

                    foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
                        $linkpath = $lab;

                        $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                        $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                        $lab = array_values(array_slice($array, -1))[0];

                        $bootmode = readlink($linkpath);
                        $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
                        $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                        if($lab === $_POST['name'] && $dept === $_POST['dept']){
                            $labtmp["id"] = $dept.$lab;
                            $labtmp["name"] = $lab;
                            $labtmp["dept"] = $dept;
                            $labtmp["mode"] = $bootmode;
                            break;
                        }
                    };
                    //check if reverting to default mode is requested, if yes then schedule one time job to revert back to default mode
                    if(isset($_POST['revert_minutes'])&& $_POST['revert_minutes'] !== '0' && is_numeric($_POST['revert_minutes'])){
                        $minutes = $_POST['revert_minutes'];
                        $sched_minutes = date('H:i',strtotime('+'.$minutes.' minutes',strtotime(date("H:i"))));
                        exec('echo ln -sf '.$GLOBALS['config']['defaultmodepath'].' '.$labpath.' | at '.$sched_minutes);
                    }
                    $jTableResult['Result'] = "OK";
                    $jTableResult['Record'] = $labtmp;

                } else {
                    throw new Exception("Error occured while creating symlink: " . $labpath . ' to target: ' . $modepath);
                }
            }else{
                throw new Exception('Could not find original record to update, try refreshing page');
            }
            print json_encode($jTableResult);
        }
	}
	//Deleting a record (deleteAction)
	else if($_GET["action"] == "delete")
	{
		//Delete laboratory from filesystem
        foreach (array_diff(glob($GLOBALS['config']["departmentsdir"].'*/*'), array('.', '..')) as $id=>$lab) {
            $linkpath = $lab;

            $array = (explode("/",$lab)); //splitting string containing full path to get mac name
            $dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
            $lab = array_values(array_slice($array, -1))[0];

            $bootmode = readlink($linkpath);
            $array = (explode("/",$bootmode)); //splitting string containing full path to get lab name
            $bootmode = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

            //settype($_POST['id'],"int");
            $id = $dept.$lab;
            if($id === $_POST['id']){
                $labpath = $GLOBALS['config']['departmentsdir'].$dept.'/'.$lab;
                $modepath = $GLOBALS['config']['modesdir'] . $bootmode;

                break;
            }
        };

        if($labpath && $modepath){

            //find machines linked to the oldpath and re-link them to the new path
            foreach (array_diff(glob($GLOBALS['config']["machinesdir"].'01-*'), array('.', '..')) as $id=>$machine) {
                $linkpath = $machine;
                $array = (explode("/",$machine)); //splitting string containing full path to get mac name
                $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                if(readlink($linkpath) === $labpath){
                    throw new Exception("There is atleast one machine belonging to this lab, please re-link the machines to another lab or delete them");
                };
            };

            //check if the lab has any schedules assigned to it
            $schedulepath = $GLOBALS['config']['departmentsdir'].$dept."/.schedules";
            if(file_exists($schedulepath)){
                $json = json_decode(file_get_contents($schedulepath),true);
                if(isset($json[$lab]) || count($json[$lab])!== 0){
                    throw new Exception("There is atleast one schedule associated with this lab, please remove the schedule before deleting this lab");
                }
            }

            unlink($labpath);
            $jTableResult['Result'] = "OK";

        }else{
            throw new Exception('Could not find lab to delete, try refreshing page');
        }

        print json_encode($jTableResult);
	}
	//Code to return list of modes configured in config.php
    else if($_GET["action"] == "listmodes")
    {
        foreach (array_diff(glob($GLOBALS['config']["modesdir"].'*'), array('.', '..')) as $id=>$mode) {

            $array = (explode("/",$mode)); //splitting string containing full path to get mac name
            $mode = array_values(array_slice($array, -1))[0];

            $modetmp["DisplayText"] = $mode;
            $modetmp["Value"] = $mode;
            $modeforoptions[] = $modetmp;
        };

        $jTableResult['Result'] = "OK";
        $jTableResult['Options'] = $modeforoptions;

        //Return result to jTable
        print json_encode($jTableResult);
    }
}
catch(Exception $ex)
{
    //Return error message
	$jTableResult = array();
	$jTableResult['Result'] = "ERROR";
	$jTableResult['Message'] = $ex->getMessage();
	print json_encode($jTableResult);
}