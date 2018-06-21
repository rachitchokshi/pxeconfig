<?php
include "../config.php";
try
{
	//Getting records (listAction)
	if($_GET["action"] == "list")
	{
		//Get records from configured departments directory
        if(isset($_POST['Name'])){
            foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
                $array = (explode("/",$dept)); //splitting string containing full path to get lab name
                $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
                $depttmp["Name"] = $dept;

                //calculate number of labs associated with this department
                $count = exec("ls -la ". $GLOBALS['config']['departmentsdir'] .$dept."/E*|wc -l");
                $depttmp["lab_count"] = $count;

                //calculate number of machines under this department
                $count = exec("ls -la ". $GLOBALS['config']['machinesdir'] ."01-*|grep ".$dept."|wc -l");
                $depttmp["machinecount"] = $count;


                if(stripos($dept,$_POST['Name'])!== false || $_POST['Name']===''){
                    $deptsfilter[] = $depttmp;
                }

            };
            $rows = $deptsfilter;
        }else{
            $rows = $depts;
        }


		//Return result to jTable
		$jTableResult = array();
		$jTableResult['Result'] = "OK";
		$jTableResult['Records'] = $rows;
		print json_encode($jTableResult);
	}
	//Creating a new record (createAction)
	else if($_GET["action"] == "create")
	{
        $jTableResult = array();

		//create new directory for new department

        if( isset($_POST['Name']) ){
            $name = htmlspecialchars(strip_tags(trim($_POST['Name'])));
            if($name && strpos($name, '.') == false && strpos($name, '..') == false && strpos($name, '/') == false){
                $path = $config["departmentsdir"] . $name;
                if(!is_dir($path)){
                    $status = mkdir($path, 0775);
                    if($status){
                        $jTableResult['Result'] = "OK";

                        //rescanning directory to get new department
                        foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
                            $array = (explode("/",$dept)); //splitting string containing full path to get lab name
                            $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
                            $depttmp["Name"] = $dept;

                            //assigning new department to return result
                            if($depttmp["Name"] == $name){
                                $jTableResult['Record'] = $depttmp;
                            }
                            $depts[] = $depttmp;
                        };
                    }
                }else{
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "Department \"{$name}\" already exists.";
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "The name Provided could could not be used in creating a department.";
            }
        }else{
            $jTableResult['Result'] = "ERROR";
            $jTableResult['Message'] = "No Department name provided";
        }
		//Return result to jTable
		print json_encode($jTableResult);
	}
	//Updating a record (updateAction)
	else if($_GET["action"] == "update")
	{
		//Update folder name in filesystem

        if( isset($_POST['Name']) ){
            $name = htmlspecialchars(strip_tags(trim($_POST['Name'])));
            if($name && strpos($name, '.') == false && strpos($name, '..') == false && strpos($name, '/') == false){

                //rescanning directory to find old department path
                foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
                    $array = (explode("/",$dept)); //splitting string containing full path to get lab name
                    $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
                    $depttmp["Name"] = $dept;

                    //assigning new department to return result
                    if($depttmp["Name"] == $_POST['jtRecordKey']){
                       $oldpath = $config["departmentsdir"] . $dept;
                    }
                    $depts[] = $depttmp;
                };

                $newpath = $config["departmentsdir"] . $name;
                if(!is_dir($newpath)){

                    $status = rename($oldpath, $newpath);

                    if($status){
                        //modifying all machines that contain link to oldpath
                        //using scandir instead of glob because glob desnt return invalid links

                        foreach (scandir($GLOBALS['config']["machinesdir"]) as $id=>$machine) {
                            if(fnmatch('01-*',$machine)){

                                $machine = $GLOBALS['config']["machinesdir"] . $machine; //beacasue we are using scandir

                                $linkpath = $machine;
                                $array = (explode("/",$machine)); //splitting string containing full path to get mac name
                                $machine = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the mac name

                                if(strpos(readlink($linkpath), $oldpath) !== false ){
                                    $target = str_replace($oldpath,$newpath,readlink($linkpath));
                                    unlink($linkpath);
                                    symlink($target,$linkpath);
                                };
                            }
                        };
                        $jTableResult['Result'] = "OK";
                    }
                }else{
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "Department \"{$name}\" already exists.";
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "The name Provided could could not be used in creating a department.";
            }
        }else{
            $jTableResult['Result'] = "ERROR";
            $jTableResult['Message'] = "Blank Department name provided";
        }
		//Return result to jTable
		print json_encode($jTableResult);
	}
	//Deleting a record (deleteAction)
	else if($_GET["action"] == "delete")
	{
		//Delete Department from filesystem

            //rescanning directory to find department path
            foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
                $array = (explode("/",$dept)); //splitting string containing full path to get lab name
                $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
                $depttmp["Name"] = $dept;

                //assigning new department to return result
                if($depttmp["Name"] == $_POST['Name']){
                    $deletepath = $config["departmentsdir"] . $dept;
                }
                $depts[] = $depttmp;
            };

            if($deletepath){

                $status = rmdir($deletepath);
                if($status){
                    $jTableResult['Result'] = "OK";
                }else{
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "There is atleast one LAB assigned to these department, please delete the LABs or assign them to other department";
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "Could not find department to delete, try refreshing page";
            }

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