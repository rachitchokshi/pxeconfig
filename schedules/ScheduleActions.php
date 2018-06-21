<?php
include "../config.php";
try
{
    //Getting records (listAction)
    if($_GET["action"] == "list")
    {
        $result = validate_keys($_POST,['lab','dept']);
        if ($result !== ""){
            throw new Exception($result);
        }else{
            $lab = $_POST['lab'];
            $dept = $_POST['dept'];
            //check if data file exists for this department
            $schedulepath = $GLOBALS['config']['departmentsdir'].$dept."/.schedules";

            if(!file_exists($schedulepath)){
                //create empty json file to write to
                $json = '{}';
                $handler = fopen($schedulepath,'w');
                fwrite($handler,$json);
                fclose($handler);
                chmod($schedulepath,0755);
            }

            //read file and return dataset
            $json = json_decode(file_get_contents($schedulepath),true);

            foreach ($json[$lab] as $id => $sched){
                $json[$lab][$id]['id'] = $id;
            }

            $jTableResult = array();
            $jTableResult['Result'] = "OK";
            $jTableResult['Records'] = $json[$lab];
            print json_encode($jTableResult);
        }
    }
    //Creating a new record (createAction)
    else if($_GET["action"] == "create")
    {
        $result = validate_keys($_POST,['lab','dept','start','end','mode']);
        if ($result !== ""){
            throw new Exception($result);
        }else {

            $lab = $_POST['lab'];
            $dept = $_POST['dept'];
            //check if data file exists for this department
            $schedulepath = $GLOBALS['config']['departmentsdir'].$dept."/.schedules";

            if(!file_exists($schedulepath)){
                //create empty json file to write to
                $json = '{}';
                $handler = fopen($schedulepath,'w');
                fwrite($handler,$json);
                fclose($handler);
                chmod($schedulepath,0755);
            }

            $json = json_decode(file_get_contents($schedulepath),true);
            if(isset($json[$lab][$_POST['Name']])){
                throw new Exception("Schedule with given name already exists");
            }
            $sched["mode"] = $_POST['mode'];
            $sched["start"] = $_POST['start'];
            $sched["end"] = $_POST['end'];

            $json[$lab][] = $sched;

            //prepare record to send back to client and write json to file
            file_put_contents($schedulepath,json_encode($json));
            $jTableResult['Result'] = "OK";
            $jTableResult['Record'] = $sched;
            print json_encode($jTableResult);

        }
    }
    //Updating a record (updateAction)
    else if($_GET["action"] == "update")
    {
        //Update schedule
        $result = validate_keys($_POST,['lab','dept','start','end','mode']);
        if ($result !== ""){
            throw new Exception($result);
        }else{
            $id = $_POST['id'];
            $lab = $_POST['lab'];
            $dept = $_POST['dept'];
            $mode = $_POST['mode'];

            $schedulepath = $GLOBALS['config']['departmentsdir'].$dept."/.schedules";

            if(!file_exists($schedulepath)){
                //create empty json file to write to
                $json = '{}';
                $handler = fopen($schedulepath,'w');
                fwrite($handler,$json);
                fclose($handler);
                chmod($schedulepath,0755);
            }
            $json = json_decode(file_get_contents($schedulepath),true);

            $sched["mode"] = $mode;
            $sched["start"] = $_POST['start'];
            $sched["end"] = $_POST['end'];

            $json[$lab][$id] = $sched;

            //write JSON back to file system
            //prepare record to send back to client and write json to file
            file_put_contents($schedulepath,json_encode($json));
            $jTableResult['Result'] = "OK";
            $jTableResult['Record'] = $sched;
            print json_encode($jTableResult);
        }
    }
    //Deleting a record (deleteAction)
    else if($_GET["action"] == "delete")
    {
        $result = validate_keys($_POST,['lab','dept']);
        if ($result !== ""){
            throw new Exception($result);
        }else{
            $id = $_POST['id'];
            $lab = $_POST['lab'];
            $dept = $_POST['dept'];
            $schedulepath = $GLOBALS['config']['departmentsdir'].$dept."/.schedules";

            if(!file_exists($schedulepath)){
                //create empty json file to write to
                $json = '{}';
                $handler = fopen($schedulepath,'w');
                fwrite($handler,$json);
                fclose($handler);
                chmod($schedulepath,0755);
            }
            $json = json_decode(file_get_contents($schedulepath),true);
            unset($json[$lab][$id]);
            file_put_contents($schedulepath,json_encode($json));
            $jTableResult['Result'] = "OK";
            print json_encode($jTableResult);
    }

    }
    //Code to return list of modes configured in config.php
    else if($_GET["action"] == "listlabs")
    {
        if(isset($_POST['dept']) && $_POST['dept'] !== ''){
            $result = validate_keys($_POST,['dept']);
            if($result !== ''){
                ?>
                <option value="">ERROR</option>
                <?php
            }else{
                $labs = array_diff(glob($GLOBALS['config']["departmentsdir"].$_POST['dept'].'/*'), array('.', '..'));
                $text = 'Select Lab';
                if(count($labs) === 0){
                    $text = 'No Labs Available';
                }
                ?>
                <option value=""><?php echo $text ?></option>
                <?php
                foreach ($labs as $lab) {
                    $array = (explode("/",$lab)); //splitting string containing full path to get mac name
                    $lab = array_values(array_slice($array, -1))[0];
                    ?>
                    <option value="<?php echo $lab; ?>"><?php echo $lab; ?></option>
                    <?php
                };
            }
        }else{
            ?>
            <option value="">Select Department</option>
            <?php
        }

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