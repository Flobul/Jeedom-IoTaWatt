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

document.getElementById('table_cmd').addEventListener('click', function(event) {
  if (_target = event.target.closest('.cmdAction[data-action="reloadHistory"]')) {
    var id = event.target.closest('tr').getAttribute('data-cmd_id');
    console.log(id)
    var message = '{{Êtes-vous sûr de vouloir supprimer l\'historique de la commande et de le remplacer par celui de IoTaWatt ? Cette action est irréversible.}}</br></br>';
    const now = new Date();

    message += '{{Quelle date de début souhaitez-vous utiliser pour récupérer l\'historique ?}}';
    message += '<div id="md_history" class="md_history" data-modalType="md_history">';
    message += '    <div class="options col-lg-12" style="">';
    message += '        <input id="in_startDate" class="btn btn-default form-control input-sm in_datepicker btnStartDate roundedLeft" style="width: 150px;" value="'+now.toISOString().substring(0, 18).replace("T", " ")+'"/>';
    message += '        <a class="btn btn-default btn-sm btnStartDate roundedLeft" data-value="0" title="{{Maintenant (Ràz)}}">{{Zéro}}</a>';
    message += '        <a class="btn btn-default btn-sm btnStartDate" data-value="today" title="{{Aujourd\'hui}}">{{Aujourd\'hui}}</a>';
    message += '        <a class="btn btn-default btn-sm btnStartDate" data-value="week" title="{{La semaine}}">{{Semaine}}</a>';
    message += '        <a class="btn btn-default btn-sm btnStartDate" data-value="month" title="{{Début du mois}}">{{Mois}}</a>';
    message += '        <a class="btn btn-default btn-sm btnStartDate" data-value="year" title="{{Début de l\'année}}">{{Année}}</a>';
    message += '        <a class="btn btn-success btn-sm btnStartDate roundedRight" data-value="all" title="{{Toutes}}">{{Toutes}}</a>';
    message +=     '</div>';
    message += '</div>';

    message += '<script>';
    message += '    jeedomUtils.datePickerInit("Y-m-d H:i:00");';
    message += '</script>';
    bootbox.dialog({
        title: '{{Remplacer l\'historique de la commande}}',
        message: message,
        buttons: {
            "{{Annuler}}": {
              className: "btn-danger",
              callback: function() {}
            },
            success: {
                label: "{{Recharger}}",
                className: "btn-success",
                callback: function() {
                    const val = $('#md_history .btn-success.btnStartDate').data('value') ?? $('#md_history .btn-success.btnStartDate').value();
                    switch (val) {
                        case '0':
                            var value = 's';
                            break;
                        case 'today':
                            var value = 'd'
                            break;
                        case 'week':
                            var value = 'w';
                            break;
                        case 'month':
                            var value = 'M';
                            break;
                        case 'year':
                            var value = 'y';
                            break;
                        case 'all':
                        case undefined:
                            var value = 'y-4y';
                            break;
                        default:
                            var value = val.replace(" ", "T");
                            break;
                    }
                    $.ajax({
                        async: true,
                        type: "POST",
                        url: "plugins/iotawatt/core/ajax/iotawatt.ajax.php",
                        data: {
                            action: 'reloadHistory',
                            id: id,
                            begin: value
                        },
                        dataType: 'json',
                        global: false,
                        error: function(request, status, error) {
                            handleAjaxError(request, status, error);
                        },
                        success: function(data) {
                            if (data.state != 'ok') {
                                $.fn.showAlert({
                                    message: data.result,
                                    level: 'danger'
                                });
                                return;
                            }
                            if (data.result) {
                               $.fn.showAlert({
                                    message: '{{Historique remis à jour depuis IoTaWatt.}}',
                                    level: 'success'
                                });
                            }
                        }
                    });
                }
            }
        }
    });
}
});

$('body').delegate('#md_history .btnStartDate', 'click', function() {
    if ($(this).hasClass('btn-success')) {
        $('.btnStartDate').removeClass('btn-success');
        $(this).removeClass('btn-success');
    } else {
        $('.btnStartDate').removeClass('btn-success');
        $(this).addClass('btn-success');
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

$('#bt_poweriotawatt').off('click').on('click', function() {
  $('#md_modal').dialog({
    title: "{{Puissances IotaWatt}}"
  });
  $('#md_modal').load('index.php?v=d&plugin=iotawatt&modal=power').dialog('open');
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
  tr += '  <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '  <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '  <input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="showOnPanel"/>{{Afficher sur le panel}}</label> '
  tr += '</div>'
  tr += '</td>'

  tr += '<td style="min-width:80px;width:200px;">';
  tr += '<div class="input-group">';
  if (is_numeric(_cmd.id) && _cmd.id != '') {
    tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
    tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
  }
  if (init(_cmd.configuration.totalConsumption)) {
      tr += '<a class="btn btn-warning btn-xs cmdAction" data-action="reloadHistory" title="{{Recharger l\'historique}}"><i class="fas fa-history"></i></a>';
  }
  tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
  tr += '</tr>';

   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();

   jeedom.eqLogic.buildSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $.fn.showAlert({message: error.message, level: 'danger'});
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
              $.fn.showAlert({
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