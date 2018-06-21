<html>
<head>

    <link href="../themes/jquery-ui.min.css" rel="stylesheet" type="text/css" />
    <link href="../scripts/jtable/themes/lightcolor/blue/jtable.css" rel="stylesheet" type="text/css" />
    <script src="../scripts/jquery-3.3.1.min.js" type="text/javascript"></script>
    <script src="../scripts/jquery-ui.min.js" type="text/javascript"></script>
    <script src="../scripts/jtable/jquery.jtable.js" type="text/javascript"></script>
    <script src="../scripts/jquery.csv.min.js"></script>
    <script type="text/javascript" src="../scripts/async.min.js"></script>

    <script type="text/javascript" src="../scripts/moment.min.js"></script>
    <script type="text/javascript" src="../scripts/later.min.js"></script>
    <script type="text/javascript" src="../scripts/prettycron.min.js"></script>

</head>
<body>

<div id="dept-lab">
        <label>Department:</label>
        <select name="dept" id="dept-list" onChange="getLabs(this.value);">
            <option value="">Select Department</option>
            <?php
            include '../config.php';

            foreach (array_diff(scandir($config["departmentsdir"]), array('.', '..')) as $dept) {
                $array = (explode("/",$dept)); //splitting string containing full path to get lab name
                $dept = array_values(array_slice($array, -1))[0]; //reading last index of the array which contains the Lab name
		    ?>
                <option value="<?php echo $dept; ?>"><?php echo $dept; ?></option>
            <?php
            };
		    ?>
        </select>

        <label>Lab:</label>
        <select name="lab" id="lab-list">
            <option value="">Select Lab</option>
        </select>
</div>
<script>
    $('#lab-list').change(function(){
        let lab = document.getElementById("lab-list").options[document.getElementById("lab-list").selectedIndex].value;
        let dept = document.getElementById("dept-list").options[document.getElementById("dept-list").selectedIndex].value;
        if(lab !== undefined && lab !== ''){
            $('#SchedulesTableContainer').jtable('load',{
                dept: dept,
                lab: lab
            });
        }
    });

    function getLabs(val) {
        $.ajax({
            type: "POST",
            url: "ScheduleActions.php?action=listlabs",
            data:'dept='+val,
            success: function(data){
                $("#lab-list").html(data);
            }
        });
    }
</script>
<div id="SchedulesTableContainer" style="width: 60%"></div>
<script type="text/javascript">



    $(document).ready(function () {
        //Prepare jTable
        $('#SchedulesTableContainer').jtable({
            title: 'Manage Schedules for Lab',
            actions: {
                listAction: 'ScheduleActions.php?action=list',
                createAction: 'ScheduleActions.php?action=create',
                updateAction: 'ScheduleActions.php?action=update',
                deleteAction: function (postData, jtParams) {
                    return $.Deferred(function ($dfd) {
                        postData["lab"] = document.getElementById("lab-list").options[document.getElementById("lab-list").selectedIndex].value;
                        postData['dept'] = document.getElementById("dept-list").options[document.getElementById("dept-list").selectedIndex].value;

                        $.ajax({
                            url: 'ScheduleActions.php?action=delete',
                            type: 'POST',
                            dataType: 'json',
                            data: postData,
                            success: function (data) {
                                $dfd.resolve(data);
                            },
                            error: function () {
                                $dfd.reject();
                            }
                        });
                    });
                }
            },
            selecting: true,
            selectingCheckboxes: true,
            multiselect: true,
            toolbar: {
                items: [{
                    text: 'Delete records',
                    click: function () {
                        let $selectedRows = $('#SchedulesTableContainer').jtable('selectedRows');
                        $('#SchedulesTableContainer').jtable('deleteRows',$selectedRows)
                    }
                }]
            },
            fields:{
                id:{
                    key: true,
                    create:false,
                    edit:false,
                    list:false
                },
                mode:{
                    title: 'mode',
                    create:true,
                    edit:true,
                    list:true,
                    options: '../laboratories/LaboratoryActions.php?action=listmodes'
                },
                start:{
                    title: 'Start Schedule',
                    create: true,
                    edit:true,
                    list: true,
                    display: function (data) {
                        return data.record.start + '     (' + prettyCron.toString(data.record.start) + ')';
                    }
                },
                end: {
                    title: 'End Schedule',
                    create: true,
                    edit:true,
                    list: true,
                    display: function (data) {
                        return data.record.end + '      (' + prettyCron.toString(data.record.end) + ')';
                    }
                }
            },
            formSubmitting: function (event, data) {
                if(prettyCron.toString(data.form.find('input[name="start"]').val()) !== "Every minute" || prettyCron.toString(data.form.find('input[name="end"]').val()) !== "Every minute"){
                    $('<input />').attr('type', 'hidden')
                        .attr('name', "dept")
                        .attr('value', document.getElementById("dept-list").options[document.getElementById("dept-list").selectedIndex].value)
                        .appendTo(data.form);

                    $('<input />').attr('type', 'hidden')
                        .attr('name', "lab")
                        .attr('value', document.getElementById("lab-list").options[document.getElementById("lab-list").selectedIndex].value)
                        .appendTo(data.form);

                    return true;
                }else{
                    alert("atleast one invalid cron expression passed");
                    return false;
                }
            },
        });

        //Load person list from server
        // $('#SchedulesTableContainer').jtable('load');

    });

</script>

</body>
</html>