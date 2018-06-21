<?php
include "../config.php";
try
{

    //Getting records (listAction)
    if($_GET["action"] == "list")
    {
        if(isset($_POST['Name']) && $_POST['Name'] !== ''){
            foreach (array_diff(glob($GLOBALS['config']["modesdir"].'*'), array('.', '..')) as $id=>$mode) {
                $linkpath = $mode;

                $array = (explode("/",$mode)); //splitting string containing full path to get mac name
                //$dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $mode = array_values(array_slice($array, -1))[0];

                //finding active ISO configured for this mode
                $label = exec('grep -e "default " '.$GLOBALS['config']['modesdir'].$mode.' |awk \'{print $2}\'');
//                var_dump($label);
                $iso = exec("cat ".$GLOBALS['config']['modesdir'].$mode." |sed -n '/label ".$label."/{n;n;p}'|awk '{print $3}'");
                $array = (explode("=",$iso));
                $iso = array_values(array_slice($array, -1))[0];

                if($iso !== ""){
                    if(is_file($GLOBALS['config']['isodir'].$iso)){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                }else{
                    $status = 1;
                    $iso = 'N/A';
                }

                //do not show default boot mode
                $defaultmode = (explode("/",$GLOBALS['config']['defaultmodepath']));
                $defaultmode = array_values(array_slice($defaultmode, -1))[0];

                if($mode !== $defaultmode){
                    $modetmp["Name"] = $mode;
                    $modetmp["file"] = $iso;
                    $modetmp["status"] = $status;

                    if(stripos($modetmp["Name"],$_POST['Name'])!==false || stripos($modetmp["file"],$_POST['Name'])!==false || stripos($modetmp["status"],$_POST['Name'])!==false){
                        $modestmp[] = $modetmp;
                    }
                }

            };
        }else{
            foreach (array_diff(glob($GLOBALS['config']["modesdir"].'*'), array('.', '..')) as $id=>$mode) {
                $linkpath = $mode;

                $array = (explode("/",$mode)); //splitting string containing full path to get mac name
                //$dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $mode = array_values(array_slice($array, -1))[0];

                //finding active ISO configured for this mode
                $label = exec('grep -e "default " '.$GLOBALS['config']['modesdir'].$mode.' |awk \'{print $2}\'');
//                var_dump($label);
                $iso = exec("cat ".$GLOBALS['config']['modesdir'].$mode." |sed -n '/label ".$label."/{n;n;p}'|awk '{print $3}'");
                $array = (explode("=",$iso));
                $iso = array_values(array_slice($array, -1))[0];

                if($iso !== ""){
                    if(is_file($GLOBALS['config']['isodir'].$iso)){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                }else{
                    $status = 1;
                    $iso = 'N/A';
                }

                //do not show default boot mode
                $defaultmode = (explode("/",$GLOBALS['config']['defaultmodepath']));
                $defaultmode = array_values(array_slice($defaultmode, -1))[0];

                if($mode !== $defaultmode){
                    $modetmp["Name"] = $mode;
                    $modetmp["file"] = $iso;
                    $modetmp["status"] = $status;
                    $modestmp[] = $modetmp;
                }

            };
        }

        //Return result to jTable
        $jTableResult = array();
        $jTableResult['Result'] = "OK";
        $jTableResult['Records'] = $modestmp;
        print json_encode($jTableResult);
    }
    //Creating a new record (createAction)
    else if($_GET["action"] == "create")
    {

        $result = validate_keys($_POST,['Name']);
        if ($result !== ""){
            throw new Exception($result);
        }else if($_FILES['iso-file']['error']!==0){
            throw new Exception("could not process uploaded file, maximum allowed file size in 512MB");
        }else {
            $base_config = 'prompt 1
timeout 100
default config
display default.txt

label config
        kernel memdisk
        append iso initrd=placeholder.iso raw';

            $mode_name = $_POST['Name'];
            $file_name = $_FILES['iso-file']['name'];
            if(is_file($GLOBALS['config']['modesdir'].$mode_name) || is_file($GLOBALS['config']['isodir'].$file_name)){
                throw new Exception("mode ".$mode_name."or file ".$file_name." already exists on the server");
            }

            $file_location = $_FILES['iso-file']['tmp_name'];
            chmod($file_location,0755);
            //move isofile to correct location
            if(rename($file_location,$GLOBALS['config']['isodir'].$file_name) === false){
                throw new Exception("Could not process uploaded file");
            }


            $new_config = str_replace('placeholder.iso',$file_name,$base_config);

            //create config file
            $handler = fopen($GLOBALS['config']['modesdir'].$mode_name,'w');
            fwrite($handler,$new_config);
            fclose($handler);
            chmod($GLOBALS['config']['modesdir'].$mode_name,0755);

            $modetmp['Name'] = $mode_name;
            $modetmp['file'] = $file_name;
            if(is_file($GLOBALS['config']['isodir'].$file_name)){
                $status = 1;
            }else{
                $status = 0;
            }

            $jTableResult['Result'] = "OK";
            $jTableResult['Record'] = $modetmp;
        }
        print json_encode($jTableResult);
    }
    //Updating a record (updateAction)
    else if($_GET["action"] == "update")
    {
        //Update folder name in filesystem
        $result = validate_keys($_POST,['Name']);
        if ($result !== ""){
            throw new Exception($result);
        }else {
            $base_config = 'prompt 1
timeout 100
default config
display default.txt

label config
        kernel memdisk
        append iso initrd=placeholder.iso raw';

            $current_mode_name = $_POST['jtRecordKey'];
            $new_mode_name = $_POST['Name'];


            function fixbrokenlabs($oldpath,$newpath){

                //using scandir instead of glob because glob desnt return invalid links
                foreach (scandir($GLOBALS['config']["labsdir"]) as $id=>$lab) {

                    $linkpath = $GLOBALS['config']['labsdir'].$lab;
                        if(strpos(readlink($linkpath), $oldpath) !== false ){
                            $target = str_replace($oldpath,$newpath,readlink($linkpath));
                            unlink($linkpath);
                            symlink($target,$linkpath);
                        };
                };
            }



            if(isset($_FILES['iso-file']) && $_FILES['iso-file']['error'] !==4){
                if($_FILES['iso-file']['error']==0){
                    //find current file and replace it with new file
                    //finding active ISO configured for this mode
                    $label = exec('grep -e "default " '.$GLOBALS['config']['modesdir'].$current_mode_name.' |awk \'{print $2}\'');
                    $iso = exec("cat ".$GLOBALS['config']['modesdir'].$current_mode_name." |sed -n '/label ".$label."/{n;n;p}'|awk '{print $3}'");
                    $array = (explode("=",$iso));
                    $iso = array_values(array_slice($array, -1))[0];

                    unlink($GLOBALS['config']['isodir'].$iso);

                    $file_name = $_FILES['iso-file']['name'];
                    $file_location = $_FILES['iso-file']['tmp_name'];
                    chmod($file_location,0755);
                    //move isofile to correct location
                    if(rename($file_location,$GLOBALS['config']['isodir'].$file_name) === false){
                        throw new Exception("Could not process uploaded file");
                    }

                    //delete and create new config file with new iso name
                    unlink($GLOBALS['config']['modesdir'].$current_mode_name);
                    $new_config = str_replace('placeholder.iso',$file_name,$base_config);

                    //create config file
                    $handler = fopen($GLOBALS['config']['modesdir'].$new_mode_name,'w');
                    fwrite($handler,$new_config);
                    fclose($handler);
                    chmod($GLOBALS['config']['modesdir'].$new_mode_name,0755);

                    //fixbrokenlabs
                    fixbrokenlabs($GLOBALS['config']['modesdir'].$current_mode_name,$GLOBALS['config']['modesdir'].$new_mode_name);

                }else{
                    throw new Exception("Error code ".$_FILES['iso-file']['error']. " occurred while uploading file (google: php upload error code <number>)");
                }
            }else if($current_mode_name !== $new_mode_name){

                if(!rename($GLOBALS['config']['modesdir'].$current_mode_name,$GLOBALS['config']['modesdir'].$new_mode_name)){
                    throw new Exception("Failed to rename mode from ".$current_mode_name." to new mode name ".$new_mode_name);
                };
                fixbrokenlabs($GLOBALS['config']['modesdir'].$current_mode_name,$GLOBALS['config']['modesdir'].$new_mode_name);
            }



            //fetch new updated mode and return
            foreach (array_diff(glob($GLOBALS['config']["modesdir"].'*'), array('.', '..')) as $id=>$mode) {
                $linkpath = $mode;

                $array = (explode("/",$mode)); //splitting string containing full path to get mac name
                //$dept = array_values(array_slice($array, -2))[0]; //reading last 2 index of the array which contains the mac name
                $mode = array_values(array_slice($array, -1))[0];

                //finding active ISO configured for this mode
                $label = exec('grep -e "default " '.$GLOBALS['config']['modesdir'].$mode.' |awk \'{print $2}\'');
//                var_dump($label);
                $iso = exec("cat ".$GLOBALS['config']['modesdir'].$mode." |sed -n '/label ".$label."/{n;n;p}'|awk '{print $3}'");
                $array = (explode("=",$iso));
                $iso = array_values(array_slice($array, -1))[0];

                if($iso !== ""){
                    if(is_file($GLOBALS['config']['isodir'].$iso)){
                        $status = 1;
                    }else{
                        $status = 0;
                    }
                }else{
                    $status = 1;
                    $iso = 'N/A';
                }

                $modetmp["Name"] = $mode;
                $modetmp["file"] = $iso;
//                $modetmp["status"] = $status;

                if($modetmp["Name"] === $_POST["Name"]){
                    break;
                }
            };

            $jTableResult['Result'] = "OK";
            $jTableResult['Record'] = $modetmp;
        }
        print json_encode($jTableResult);
    }
    //Deleting a record (deleteAction)
    else if($_GET["action"] == "delete")
    {
        $result = validate_keys($_POST,['Name']);
        if ($result !== ""){
            throw new Exception($result);
        }else{
            $mode = $_POST['Name'];
            $modepath = $GLOBALS['config']['modesdir'].$mode;
            if(!is_file($modepath)){
                throw new Exception("given mode does not exists");
            }
            //check if mode is associated with any labs
            //using scandir instead of glob because glob desnt return invalid links
            foreach (scandir($GLOBALS['config']["labsdir"]) as $id=>$lab) {
                $linkpath = $GLOBALS['config']['labsdir'].$lab;
                if(strpos(readlink($linkpath), $modepath) !== false ){
                    throw new Exception("There is atleast one lab pointing to this mode, please update all labs asociated with this mode and try again");
                };
            };

            //find iso to delete it
            //finding active ISO configured for this mode
            $label = exec('grep -e "default " '.$GLOBALS['config']['modesdir'].$mode.' |awk \'{print $2}\'');
            $iso = exec("cat ".$GLOBALS['config']['modesdir'].$mode." |sed -n '/label ".$label."/{n;n;p}'|awk '{print $3}'");
            $array = (explode("=",$iso));
            $iso = array_values(array_slice($array, -1))[0];

            unlink($GLOBALS['config']['isodir'].$iso);

            //delete the modefile
            unlink($modepath);
            $jTableResult['Result'] = "OK";
        }
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