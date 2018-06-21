<?php
/**
 * Created by PhpStorm.
 * User: 012550044
 * Date: 6/1/2018
 * Time: 3:21 PM
 */

include '../config.php';

try
{
    //Getting records (listAction)
    if($_GET["action"] == "list")
    {
        //Get records from configured departments directory
        if(isset($_POST['mac'])){
            $rows = ListMachinesByFilter($_POST['mac']);
        }else{
            $rows = ListMachines();
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

        //create new link for new machine
        $result = validate_keys($_POST,['mac','dept','lab']);
        if ($result !== ""){
            $jTableResult['Result'] = "ERROR";
            $jTableResult['Message'] = $result;
        }else{
            $_POST['mac'] = strtolower($_POST['mac']);
            if(preg_match('/([a-fA-F0-9]{2}[:]?){6}/', $_POST['mac']) == 1){
                $mac = mactopxe($_POST['mac']);
                $machinepath = $config['machinesdir'] . $mac;
                $labpath = $config['departmentsdir'] . $_POST['dept'] . '/' . $_POST['lab'];

                if(is_link($machinepath)){
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "machine with given mac address already exists";
                }else{
                    if(!is_link($labpath)){
                        $jTableResult['Result'] = "ERROR";
                        $jTableResult['Message'] = "provided lab does not exists";
                    }else{
                        if(symlink($labpath,$machinepath)){
                            $jTableResult['Result'] = "OK";
                            $jTableResult['Record'] = ListMachine($_POST['mac']);
                        }else{
                            throw new Exception("Error occured while creating symlink: ".$machinepath . ' to target: '.$labpath);
                        }
                    }
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "Invalid mac format for value: ".$_POST['mac'] . " sample mac: dc:7b:83:a9:6b:00";
            }
            print json_encode($jTableResult);
        }
    }
    //Updating a record (updateAction)
    else if($_GET["action"] == "update")
    {
        //Update machine name in filesystem
        $jTableResult = array();
        $result = validate_keys($_POST,['mac','dept','lab']);
        if ($result !== ""){
            $jTableResult['Result'] = "ERROR";
            $jTableResult['Message'] = $result;
        }else{
            $_POST['mac'] = strtolower($_POST['mac']);
            if(preg_match('/([a-fA-F0-9]{2}[:]?){6}/', $_POST['mac']) == 1){
                $mac = mactopxe($_POST['mac']);
                $machinepath = $config['machinesdir'] . $mac;
                $oldmachinepath = $config['machinesdir'] . mactopxe($_POST['jtRecordKey']);
                $labpath = $config['departmentsdir'] . $_POST['dept'] . '/' . $_POST['lab'];

                if(is_link($oldmachinepath)){
                    if(!is_link($labpath)){
                        $jTableResult['Result'] = "ERROR";
                        $jTableResult['Message'] = "provided lab or department does not exists";
                    }else{
                        unlink($oldmachinepath);
                        if(symlink($labpath,$machinepath)){
                            $jTableResult['Result'] = "OK";
                        }else{
                            throw new Exception("Error occured while modifying symlink: ".$machinepath . ' to target: '.$labpath);
                        }
                    }
                }else{
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "machine with given mac address does not exists";
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "Invalid mac format for value: ".$_POST['mac'] . " sample mac: dc:7b:83:a9:6b:00";
            }
            print json_encode($jTableResult);
        }
    }
    //Deleting a record (deleteAction)
    else if($_GET["action"] == "delete")
    {
        //Delete Department from filesystem
        $jTableResult = array();
        $result = validate_keys($_POST,['mac']);
        if ($result !== ""){
            $jTableResult['Result'] = "ERROR";
            $jTableResult['Message'] = $result;
        }else{
            $_POST['mac'] = strtolower($_POST['mac']);
            if(preg_match('/([a-fA-F0-9]{2}[:]?){6}/', $_POST['mac']) == 1){
                $mac = mactopxe($_POST['mac']);
                $machinepath = $config['machinesdir'] . $mac;

                if(is_link($machinepath)){
                        if(unlink($machinepath)){
                            $jTableResult['Result'] = "OK";
                        }else{
                            throw new Exception("Error occured while deleting symlink: ".$machinepath);
                        }
                }
                else{
                    $jTableResult['Result'] = "ERROR";
                    $jTableResult['Message'] = "machine with given mac address does not exists";
                }
            }else{
                $jTableResult['Result'] = "ERROR";
                $jTableResult['Message'] = "Invalid mac format for value: ".$_POST['mac'] . " sample mac: dc:7b:83:a9:6b:00";
            }
            print json_encode($jTableResult);
        }
    }

    //returning departments for dropdown
    else if($_GET["action"] == "listdepartments"){

        foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $id=>$dept) {
            $array = (explode("/",$dept)); //splitting string containing full path to get lab name
            $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
            $deptforoptionstmp["DisplayText"] = $dept;
            $deptforoptionstmp["Value"] = $dept;
            $deptforoptions[] = $deptforoptionstmp;
        }

        $jTableResult['Result'] = "OK";
        $jTableResult['Options'] = $deptforoptions;
        print json_encode($jTableResult);
    }

    //returning labs for dropdown

    else if($_GET["action"] == "listlabsfordept"){
        $deptname = $_GET['dept'];
        foreach (array_diff(scandir($config["departmentsdir"].$deptname), array('.', '..')) as $id=>$lab) {
            $array = (explode("/",$lab)); //splitting string containing full path to get lab name
            $lab = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
            $labforoptionstmp["DisplayText"] = $lab;
            $labforoptionstmp["Value"] = $lab;
            $labforoptions[] = $labforoptionstmp;
        }

        $jTableResult['Result'] = "OK";

        if(is_null($labforoptions)){
            $labforoptions[] = [];
        }

        $jTableResult['Options'] = $labforoptions;
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