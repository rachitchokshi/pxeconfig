<html>
<head>
    <link href="../themes/jquery-ui.min.css" rel="stylesheet" type="text/css" />
    <link href="../scripts/jtable/themes/lightcolor/blue/jtable.css" rel="stylesheet" type="text/css" />
    <script src="../scripts/jquery-3.3.1.min.js" type="text/javascript"></script>
    <script src="../scripts/jquery-ui.min.js" type="text/javascript"></script>
    <script src="../scripts/jtable/jquery.jtable.js" type="text/javascript"></script>
    <script src="../scripts/jquery.csv.min.js"></script>
    <script type="text/javascript" src="../scripts/async.min.js"></script>

</head>
<body>
<div id="BulkUploadContainer">
    <script type="text/javascript">
        function process_file(tableid){
            if(document.getElementById("source-file").value !== ""){
                let files = document.getElementById("source-file").files; // FileList object
                let file = files[0];

                // read the file contents
                let reader = new FileReader();
                reader.readAsText(file);
                reader.onload = function(event){
                    let csv = event.target.result;
                    let data = $.csv.toObjects(csv);
                    let series = [];
                    for(let i=0;i<data.length;i++) {
                        series.push(function (cb) {
                            document.getElementById("process-status").innerHTML = "row number "+i+" ... processing";
                            $('#'+tableid).jtable('addRecord', {
                                record: data[i],
                                success: function (arg) {
                                    document.getElementById("process-status").innerHTML = document.getElementById("process-status").innerHTML.replace('processing','success');
                                    cb()
                                },
                                error: function (err) {
                                    document.getElementById("process-status").innerHTML = document.getElementById("process-status").innerHTML.replace('processing','error: ') + err["Message"];
                                    cb(new Error(err["Message"]));
                                }
                            });
                        });
                    }
                    async.series(series,function (err) {
                        if(err){
                            document.getElementById("process-status").innerHTML = document.getElementById("process-status").innerHTML.replace('processing','error');
                            return;
                        }
                        document.getElementById("process-status").innerHTML = series.length + " records processed succesfully";
                    });
                };
                reader.onerror = function(){ document.getElementById("process-status").innerHTML = 'Unable to read file';}
            }else{
                alert("no file selected")
            }
        }

        function isAPIAvailable() {
            // Check for the various File API support.
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                // Great success! All the File APIs are supported.
                return true;
            } else {
                // source: File API availability - http://caniuse.com/#feat=fileapi
                // source: <output> availability - http://html5doctor.com/the-output-element/
                document.writeln('The HTML5 APIs used in this form are only available in the following browsers:<br />');
                // 6.0 File API & 13.0 <output>
                document.writeln(' - Google Chrome: 13.0 or later<br />');
                // 3.6 File API & 6.0 <output>
                document.writeln(' - Mozilla Firefox: 6.0 or later<br />');
                // 10.0 File API & 10.0 <output>
                document.writeln(' - Internet Explorer: Not supported (partial support expected in 10.0)<br />');
                // ? File API & 5.1 <output>
                document.writeln(' - Safari: Not supported<br />');
                // ? File API & 9.2 <output>
                document.writeln(' - Opera: Not supported');
                return false;
            }
        }
    </script>

    <h3>For Bulk Creation of records</h3>
    <h4>Provide a csv file, click process button and monitor status field</h4>
    <label>File: </label><input type="file" id="source-file" accept=".csv"><button onclick="process_file('MachinesTableContainer')">Process</button>
    <br>
    <label>Status: </label> <output id="process-status">Initial</output>

</div>

<div id="free-search">
    <script type="text/javascript">


        $(document).ready(function () {
            document.getElementById('search-text').addEventListener("keyup", function(event) {
                //            // Cancel the default action, if needed
                event.preventDefault();
                // Number 13 is the "Enter" key on the keyboard
                if (event.which === 13) {
                    // Trigger the button element with a click
                    $('#MachinesTableContainer').jtable('load',{
                        mac: document.getElementById('search-text').value
                    });
                }
            });
        });
    </script>
    <br>
    <label>Free Search: </label><input type="text" id="search-text">
</div>

<div id="MachinesTableContainer" style="width: 60%">

</div>

<script type="text/javascript">

    $(document).ready(function () {
        if(isAPIAvailable()){
            //Prepare jTable
            $('#MachinesTableContainer').jtable({
                title: 'Manage Machines',
                actions: {
                    listAction: 'MachineActions.php?action=list',
                    createAction: 'MachineActions.php?action=create',
                    updateAction: 'MachineActions.php?action=update',
                    deleteAction: 'MachineActions.php?action=delete'
                },
                selecting: true,
                selectingCheckboxes: true,
                multiselect: true,
                toolbar: {
                    items: [{
                        text: 'Delete records',
                        click: function () {
                            let $selectedRows = $('#MachinesTableContainer').jtable('selectedRows');
                            $('#MachinesTableContainer').jtable('deleteRows',$selectedRows)
                        }
                    }]
                },
                fields: {
                    mac: {
                        title: 'mac',
                        key:true,
                        create:true,
                        edit:true,
                        list:true
                    },
                    dept: {
                        title: 'dept',
                        create:true,
                        edit:true,
                        list:true,
                        options: 'MachineActions.php?action=listdepartments'
                    },
                    lab: {
                        title: 'lab',
                        dependsOn: 'dept',
                        options: (data)=>{
                            return 'MachineActions.php?action=listlabsfordept&dept=' + data.dependedValues.dept;
                        },
                        list:true
                    }
                }
            });
            //Load person list from server
            $('#MachinesTableContainer').jtable('load');
        }
    });
</script>

</body>
</html>