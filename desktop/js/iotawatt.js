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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('.eqLogicAttr[data-l1key="configuration"][data-l2key="group"]').on('change', function(){
    if ($(this).find('option:selected').value() === 'manual') {
        $('#idResolution').hide();
        $('#idManualGroup').show();
    } else if ($(this).find('option:selected').value() === 'auto') {
        $('#idResolution').show();
        $('#idManualGroup').hide();
    } else if ($(this).find('option:selected').value() === 'all') {
        $('#idResolution').hide();
        $('#idManualGroup').hide();
    } else {
        $('#idResolution').hide();
        $('#idManualGroup').hide();
    }
});

 function addCmdToTable(_cmd) {
   if (!isset(_cmd)) {
     var _cmd = {configuration: {}};
   }
   if (!isset(_cmd.configuration)) {
     _cmd.configuration = {};
   }

  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '    <span class="cmdAttr" data-l1key="id" style="display:none;"></span>'
  tr += '    <span class="cmdAttr" data-l1key="logicalId" style="display:none;"></span>'
  tr += '    <div class="input-group">'
  tr += '        <input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '        <span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '        <span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;background:var(--btn-default-color) !important;width:2%;"></span>'
  tr += '    </div>'
  tr += '    <select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;float:right;margin-top:5px;max-width:50%" title="{{Commande info liée}}">'
  tr += '        <option value="">{{Aucune}}</option>'
  tr += '    </select>'
  tr += '</td>'

  tr += '<td>';
  tr += '    <span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '    <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
  tr += '</td>';

  tr += '<td>';
  if (init(_cmd.type) == 'info') {
    tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="type" title="{{Entrée/Sortie}}" disabled>';
    tr += '        <option value="input">{{Entrée}}</option>';
    tr += '        <option value="output">{{Sortie}}</option>';
    tr += '    </select>';
    if (init(_cmd.configuration.type) == 'input') {
      tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="channel" title="{{Canal d\'entrée}}" disabled>';
      tr += '        <option value="0">0</option>';
      tr += '        <option value="1">1</option>';
      tr += '        <option value="2">2</option>';
      tr += '        <option value="3">3</option>';
      tr += '        <option value="4">4</option>';
      tr += '        <option value="5">5</option>';
      tr += '        <option value="6">6</option>';
      tr += '        <option value="7">7</option>';
      tr += '        <option value="8">8</option>';
      tr += '        <option value="9">9</option>';
      tr += '        <option value="10">10</option>';
      tr += '        <option value="11">11</option>';
      tr += '        <option value="12">12</option>';
      tr += '        <option value="13">13</option>';
      tr += '        <option value="14">14</option>';
      tr += '    </select>';
    }
  }
  tr += '</td>';

  tr += '<td>';
  if (init(_cmd.type) == 'info') {
    tr += '    <span class="cmdAttr input-group-addon roundedLeft roundedRight" data-l1key="configuration" data-l2key="serie" style="font-size:15px;padding:0 5px 0 0!important;background:var(--btn-default-color) !important;" title="{{Nom de série}}" ></span>';
    tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="valueType" title="{{Type de valeur}}">';
    tr += '        <option value="">{{Aucun}}</option>';
    tr += '        <optgroup label="{{Tension entrée ou sortie}}" id="group"></optgroup>';
    tr += '        <option value="Volts">{{Volts (V)}}</option>';
    tr += '        <option value="Hz">{{Hertz (Hz)}}</option>';
    tr += '        <optgroup label="{{Puissance entrée ou sortie}}" id="group"></optgroup>';
    tr += '        <option value="Watts">{{Watts (W)}}</option>';
    tr += '        <option value="Amps">{{Ampères (A)}}</option>';
    tr += '        <option value="Wh">{{Watt-heure (Wh)}}</option>';
    tr += '        <option value="VA">{{Voltampère (VA)}}</option>';
    tr += '        <option value="VAR">{{Voltampère réactif (VAr)}}</option>';
    tr += '        <option value="VARh">{{Voltampère-heure réactif (VAhr)}}</option>';
    tr += '        <option value="PF">{{Facteur de puissance (cos phi)}}</option>';
    tr += '    </select>';
    tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="round" title="{{Arrondi}}">';
    tr += '        <option value="0">0</option>';
    tr += '        <option value="1">1</option>';
    tr += '        <option value="2">2</option>';
    tr += '        <option value="3">3</option>';
    tr += '        <option value="4">4</option>';
    tr += '        <option value="5">5</option>';
    tr += '        <option value="6">6</option>';
    tr += '        <option value="7">7</option>';
    tr += '        <option value="8">8</option>';
    tr += '        <option value="9">9</option>';
    tr += '    </select>';
  }
  tr += '</td>';

  tr += '<td>';
  if (init(_cmd.type) == 'info') {
    tr += '<span class="cmdAttr" data-l1key="htmlstate" style="display:block;text-align:center;"></span>';
  }
  if (init(_cmd.subType) == 'select') {
    tr += '    <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste de valeur|texte séparé par ;}}" title="{{Liste}}">';
  }
  if (['select', 'slider', 'color'].includes(init(_cmd.subType)) || init(_cmd.configuration.updateCmdId) != '') {
    tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" title="{{Commande d\'information à mettre à jour}}">';
    tr += '        <option value="">{{Aucune}}</option>';
    tr += '    </select>';
    tr += '    <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="{{Valeur de l\'information}}">';
  }
  tr += '</td>';

  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'

  tr += '<td style="min-width:80px;width:200px;">';
  tr += '<div class="input-group">';
  if (is_numeric(_cmd.id) && _cmd.id != '') {
    tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
    tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
  }
  tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
  tr += '</tr>';

   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();

   jeedom.eqLogic.buildSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {

       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });
}

$(document).on("change",'.cmdAttr[data-l1key="configuration"][data-l2key="valueType"]',function(e) {
    if (e.isTrigger === undefined) {
        var value = $(this).val();
        var trUnit = $(this).parents('tr.cmd').find('.cmdAttr[data-l1key="unite"]');
        var trSerie = $(this).parents('tr.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="serie"]');
        var trDecimal = $(this).parents('tr.cmd').find('.cmdAttr[data-l1key="configuration"][data-l2key="round"]');

        $.ajax({
          async: true,
          type: "POST",
          url: "plugins/iotawatt/core/ajax/iotawatt.ajax.php",
          data: {
              action: 'getUnite',
              unit: value
          },
          dataType: 'json',
          global: false,
          error: function(request, status, error) {
            handleAjaxError(request, status, error);
          },
          success: function(data) {
            if (data.state != 'ok') {
              $('#div_alert').showAlert({
                message: data.result,
                level: 'danger'
              });
              return;
            }
            if (data.result) {
                if (data.result.unit && data.result.unit != trUnit.value()) {
                    trUnit.value(data.result.unit);
                }
                if (data.result.decimals && data.result.decimals != trDecimal.value()) {
                    trDecimal.value(data.result.decimals);
                }
            }
          }
       });
    }
});

$('.cmdAction[data-action=addCommand]').on('click', function() {
    $('#md_modal').dialog({
        title: "{{Ajout de commande}}"
    });
    $('#md_modal').load('index.php?v=d&plugin=iotawatt&modal=addCommand&eqLogic_id='+$('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$('.eqLogicAction[data-action=backup]').on('click', function() {
    $('#md_modal').dialog({
        title: "{{Backup}}"
    });
    $('#md_modal').load('index.php?v=d&plugin=iotawatt&modal=backup&eqLogic_id='+$('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});

$('#bt_healthiotawatt').off('click').on('click', function() {
  $('#md_modal').dialog({
    title: "{{Santé IotaWatt}}"
  });
  $('#md_modal').load('index.php?v=d&plugin=iotawatt&modal=health').dialog('open');
});
