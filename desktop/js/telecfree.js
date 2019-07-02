
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

$(function() {
    $("#table_cmd tbody").delegate(".listCmdtelecfree", 'click', function(event) {
        $('.description').hide();
        $('.version').hide();
        $('.required').hide();
        $('.description.' + $('#sel_addPreConfigCmdtelecfree').value()).show();
        $('.version.' + $('#sel_addPreConfigCmdtelecfree').value()).show();
        $('.required.' + $('#sel_addPreConfigCmdtelecfree').value()).show();
        $('#md_addPreConfigCmdtelecfree').modal('show');
        $('#bt_addPreConfigCmdtelecfreeSave').undelegate().unbind();
        var tr = $(this).closest('tr');
        $("#div_mainContainer").delegate("#bt_addPreConfigCmdtelecfreeSave", 'click', function(event) {
            var cmd_data = json_decode($('.json_cmd.' + $('#sel_addPreConfigCmdtelecfree').value()).value());
            tr.setValues(cmd_data, '.cmdAttr');
            $('#md_addPreConfigCmdtelecfree').modal('hide');
        });
    });

    $("#sel_addPreConfigCmdtelecfree").on('change', function(event) {
        $('.description').hide();
        $('.version').hide();
        $('.required').hide();
        $('.description.' + $(this).value()).show();
        $('.version.' + $(this).value()).show();
        $('.required.' + $(this).value()).show();
    });

    $("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
});

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="name">';
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">   ';
	tr += '<span class="cmdAttr" data-l1key="id"></span>   </td>';
    tr += '<td class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType();
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span></td>';
    //tr += '<td ><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="request" style="margin-top : 5px;" />';
    tr += '<td ><textarea class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="parameters" style="margin-top : 5px;" placeholder="{{Parametres (JSON)}}" ></textarea>';
    tr += '<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="group">';
    //tr += '<a class="btn btn-default listCmdtelecfree form-control input-sm" style="margin-top : 5px;"><i class="fa fa-list-alt cursor"></i> {{Ajouter un raccourci vers une chaine TV}}</a>';
	tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}