/**
 * Add Instance on Save
 * Author: Ekin Tertemiz
*/

var STPH_aios = STPH_aios || {};

// Debug logging
STPH_aios.log = function() {
    if (STPH_aios.enable_debug) {
        switch(arguments.length) {
            case 1: 
                console.log(arguments[0]); 
                return;
            case 2: 
                console.log(arguments[0], arguments[1]); 
                return;
            case 3: 
                console.log(arguments[0], arguments[1], arguments[2]); 
                return;
            case 4:
                console.log(arguments[0], arguments[1], arguments[2], arguments[3]); 
                return;
            default:
                console.log(arguments);
        }
    }
  };


// Initialization
STPH_aios.init = function() {
    STPH_aios.log("Add Instance On Save module has been initialized");
    STPH_aios.log(STPH_aios);
    //  Indicate trigger field with Icon
    var icon = '<span class="fa-stack" style="vertical-align: top; font-size:7px; margin-top:3px; margin-right:2px;"><i class="fas fa-circle fa-stack-2x"></i><i class="fas fa-clone fa-stack-1x fa-inverse"></i></span>';
    $('#'+STPH_aios.params.instruction['trigger-field']+'-tr .rc-field-icons').prepend("<a onclick='STPH_aios.showDetails();return false;' href='javascript:;'>"+icon+"</a>");
}

//  Show Details Dialog Pop-Up
STPH_aios.showDetails = function() {

    initDialog('aios-details');

    //  Prepare HTML Contents (paragraph, table header, table body)
    var paragraph = '<p>Listed below are the details of <b>Add Instance on Save</b> module instructions for trigger field <b>'+STPH_aios.params.instruction['trigger-field']+'</b></p>'
    var validation = 'Validation: <code>true</code>' ;
    var table_1_body = '<td class="label_header" style="padding:5px 8px;width:100px;">Destination Project</td><td class="label_header" style="padding:5px 8px;width:100px;">Destination Form</td><td class="label_header" style="padding:5px 8px;">Field Pipings</td><td class="label_header" style="padding:5px 8px;width:100px;">Instance ID target</td>'
    var table_1 = '<table class="form_border" style="table-layout:fixed;border:1px solid #ddd;width:100%;"><tbody><tr>'+ table_1_body + '</tr></tbody></table>';
    var aios_1 = '<div id="aios-details1" style="margin:15px 0px 0px;">'+table_1+'</div>';
    
    var field_pipings = "";
    STPH_aios.params.instruction['field-pipings'].forEach(piping => {
        field_pipings += "<li><pre>["+piping['source-field']+"] => ["+ (piping['destination-field'] == null ? piping['source-field'] : piping['destination-field'])+"]</pre></li>"
    });
    field_pipings = "<ul style='text-align:left;list-style:none;font-size:12px;padding-left:0;margin-bottom:0;'>"+field_pipings+"</ul>";

    var table_2_body = '<td class="data" style="border:1px solid #ccc;padding:3px 8px;text-align:center;width:100px;background:#ddd;">'+STPH_aios.params.instruction['destination-project']+'</td><td class="data" style="border:1px solid #ccc;padding:3px 8px;text-align:center;background:#ddd;width:100px;">'+STPH_aios.params.instruction['destination-form']+'</td><td class="data" style="border:1px solid #ccc;padding:3px 3px;background:#ddd;">'+field_pipings+'</td><td class="data" style="border:1px solid #ccc;padding:3px 8px;text-align:center;background:#ddd;width:100px;">'+STPH_aios.params.instruction['instance-id-target']+'</td>';
    var table_2 = '<table class="form_border" style="table-layout:fixed;width:100%;" cellspacing="0"><tbody><tr>'+ table_2_body + '</tr></tbody></table>';
    var aios_2 = '<div id="aios-details2" style="margin:0px 0px 20px;">'+table_2+'</div>';

    //  Set Dialog HTML Contents
    var html = paragraph + validation + aios_1 + aios_2;
    $('#aios-details').html(html);

    //  Call dialog
    $('#aios-details').dialog({ bgiframe: true, title: 'Trigger details for field "'+STPH_aios.params.instruction['trigger-field']+'"', modal: true, width: 750, zIndex: 3999, buttons: {
        Close: function() { $(this).dialog('destroy'); } }
    });
}
