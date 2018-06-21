<?php
include "../config.php";
if ( isset($_GET["file"]) ) {
    if(stripos($_GET["file"],'..')=== false && is_file($GLOBALS['config']['isodir'] . $_GET['file'])){
        $file_name = $_GET["file"];
        header("Content-Disposition: attachment; filename=\"$file_name\"");
        echo readfile($GLOBALS['config']['isodir'] . $_GET['file']);
    }
}
?>
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

<div id="free-search">
    <script type="text/javascript">
        $(document).ready(function () {
            document.getElementById('search-text').addEventListener("keyup", function(event) {
                //            // Cancel the default action, if needed
                event.preventDefault();
                // Number 13 is the "Enter" key on the keyboard
                if (event.which === 13) {
                    // Trigger the button element with a click
                    $('#ModesTableContainer').jtable('load',{
                        Name: document.getElementById('search-text').value
                    });
                }
            });
        });
    </script>
    <br>
    <label>Free Search: </label><input type="text" id="search-text">
</div>

<div id="ModesTableContainer" style="width: 60%"></div>
<script type="text/javascript">

    $(document).ready(function () {
        let customsend = function (data,id,action) {
            var deferred = $.Deferred();

            // Capture form submit result from the hidden iframe
            $("#postiframe").on('load',function () {
                iframeContents = $("#postiframe").contents().find("body").text();
                var result = $.parseJSON(iframeContents);
                deferred.resolve(result);
            });

            //Submit form with file upload settings
            var form = $('#jtable-'+id+'-form');
            form.unbind("submit");
            form.attr("action",'ModeActions.php?action='+action);
            form.attr("method", "post");
            form.attr("enctype", "multipart/form-data");
            form.attr("encoding", "multipart/form-data");
            form.attr("target", "postiframe");
            form.submit();

            return deferred;
        };

        //Prepare jTable
        $('#ModesTableContainer').jtable({
            title: 'Manage Modes',
            actions: {
                listAction: 'ModeActions.php?action=list',
                createAction: function (data) {
                    return customsend(data,'create','create');
                },
                updateAction: function (data) {
                    return customsend(data,'edit','update');
                },
                deleteAction: 'ModeActions.php?action=delete'
            },
            selecting: true,
            selectingCheckboxes: true,
            multiselect: true,
            toolbar: {
                items: [{
                    text: 'Delete records',
                    click: function () {
                        let $selectedRows = $('#ModesTableContainer').jtable('selectedRows');
                        $('#ModesTableContainer').jtable('deleteRows',$selectedRows)
                    }
                }]
            },
            fields: {
                Name: {
                    key: true,
                    title: 'Name',
                    create:true,
                    edit:true,
                    list:true
                },
                file: {
                    title: 'Boot from ISO',
                    create:true,
                    edit:true,
                    list:true,
                    input:function(data) {
                        var htmlString = '<input id="iso-file" type="file" accept=".iso" name="iso-file" /><iframe name="postiframe" id="postiframe" style="display: none" />';
                        //if (typeof data.record !== "undefined") htmlString='<input type="hidden" value="'+data.record.documents+'" name="docFilename" /><div style="cursor:hand;cursor:pointer;" onclick="openModalDocuments(\''+divId+'Modal\',\'./api/documents/'+user_id+'/esuc/'+data.record._id+'\');">Current documents: <br>'+data.record.documents+'</div>Replace by:<br>'+htmlString;
                        // return '<div>'+htmlString+'</div>';
                        return htmlString;
                    },
                    display:function(data){
                        if(data.record.file.indexOf('.iso')!==-1){
                            return '<a href=' + '"?file=' + data.record.file + '">' + data.record.file + '</a>'
                        }else{
                            return data.record.file;
                        }

                    }
                }
            }
        });

        //Load person list from server
        $('#ModesTableContainer').jtable('load');

    });

</script>

</body>
</html>