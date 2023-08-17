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

    function sortIotawattTable() {
      $("#table_poweriotawatt").tablesorter({
        textExtraction: function(node) {
          var cmd = node.querySelector('.cmd');
          if (cmd != null) {
            var valueStr = cmd.innerHTML;
            var value = parseFloat(valueStr);
            var unit = valueStr.split(' ')[1]; // Extrait l'unité
            if (unit === 'kWh' || unit === 'kW') {
              value *= 1000; // Convertit en W ou Wh
            }
            return value;
          }
        },
        headers: {
          0: { sorter: "string" }, // Nom column (string)
          1: { sorter: "string" }, // Nom column (string)
          2: { sorter: "digit" }, // Puissance column (numeric)
          3: { sorter: "digit" }, // Consommation column (numeric)
        },
        sortList: [[2,1], [3,1]],
        cssIconAsc: 'fa fa-caret-up',
        cssIconDesc:'fa fa-caret-down',
        cssIconNone:'fa fa-sort',
        headerTemplate:'{content} {icon}'
      });
      updateTableSort(); // Appel manuel pour mettre à jour le tri
    }

    function updateTableSort() {
        $("#table_poweriotawatt").trigger("updateAll"); // Met à jour le tri
    }

    // Fonction pour mettre à jour la somme des valeurs de consommation
    function updateTotalPowerSum() {
        totalPower = 0;
        $(".cmd.power").each(function() {
            var valueStr = $(this).text();
            var value = parseFloat(valueStr);
            var unit = valueStr.split(' ')[1];
            if (unit === 'kWh' || unit === 'kW') {
                value *= 1000;
            }
            totalPower += value;
        });
        // Met à jour le contenu du div avec la somme calculée
        var totalPowerSum = document.getElementById("totalPowerSum");
        totalPowerSum.innerHTML = "{{Puissance totale}} : <span class='label label-info'>" + totalPower.toFixed(2) + " W</span>";
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Ajoutez une fonction de mise à jour à chaque commande de puissance
        var powerElements = document.querySelectorAll(".cmd.power");
        powerElements.forEach(function (element) {
            var cmdId = element.getAttribute("data-cmd_id");
            jeedom.cmd.addUpdateFunction(cmdId, function (_options) {
                element.setAttribute('title', '{{Date de collecte : }}' + _options.collectDate + '<br/>{{Date de valeur : }}' + _options.valueDate);
                element.textContent = _options.display_value + " " + _options.unit;
                updateTableSort();
                updateTotalPowerSum(); // Met à jour la somme
            });
        });

        // Ajoute une fonction de mise à jour à chaque commande de consommation
        var consoElements = document.querySelectorAll(".cmd.conso");
        consoElements.forEach(function (element) {
            var cmdId = element.getAttribute("data-cmd_id");
            jeedom.cmd.addUpdateFunction(cmdId, function (_options) {
                element.setAttribute('title', '{{Date de collecte : }}' + _options.collectDate + '<br/>{{Date de valeur : }}' + _options.valueDate);
                element.textContent = _options.display_value + " " + _options.unit;
                updateTableSort();
            });
        });

        // gére le clic sur les valeurs pour afficher les historiques
        var table = document.getElementById("table_poweriotawatt");
        table.addEventListener("click", function (event) {
            if (event.target.matches('.history') || event.target.closest('.history') != null ) { //history in summary modal
            event.stopImmediatePropagation()
            event.stopPropagation()
            if (event.ctrlKey || event.metaKey) {
              var cmdIds = []
              event.target.closest('div.eqLogic-widget').querySelectorAll('.history[data-cmd_id]').forEach(function(cmd) {
                cmdIds.push(cmd.getAttribute('data-cmd_id'))
              })
              cmdIds = cmdIds.join('-')
            } else {
              var cmdIds = event.target.closest('.history[data-cmd_id]').getAttribute('data-cmd_id')
            }
            $('#md_modal2').dialog({title: '{{Historique}}'}).load('index.php?v=d&modal=cmd.history&id=' + cmdIds).dialog('open')
          }
        }, {capture: false})

        sortIotawattTable(); // Initialisez le tri de la table
        updateTotalPowerSum(); // Mettez à jour la somme initiale
});
