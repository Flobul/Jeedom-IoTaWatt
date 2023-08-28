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
    function updateTendanceClass(element) {
        var nextSibling = element.nextElementSibling;
        var previousSibling = element.previousElementSibling;
        if (nextSibling) {
            element.classList.remove('redTendance', 'blueTendance', 'greenTendance');

            if (nextSibling.classList.contains('fa-minus')) {
                element.classList.add('blueTendance');
            } else if (nextSibling.classList.contains('fa-arrow-down')) {
                element.classList.add('greenTendance');
            } else if (nextSibling.classList.contains('fa-arrow-up')) {
                element.classList.add('redTendance');
            }
        } else if (previousSibling) {
            if (previousSibling.querySelector('i').classList.contains('fa-minus')) {
                element.classList.add('blueTendance');
            } else if (previousSibling.querySelector('i').classList.contains('fa-arrow-down')) {
                element.classList.add('greenTendance');
            } else if (previousSibling.querySelector('i').classList.contains('fa-arrow-up')) {
                element.classList.add('redTendance');
            }
        }
    }

    function getColorForPourcentage(pourcent) {
        if (isNaN(pourcent)) {
            // Si NaN, renvoyer une couleur grise par défaut
            return "hsl(0, 0%, 50%)"; // Gris
        }
        var currentTimestamp = Math.floor(Date.now() / 1000);
        var startOfDayTimestamp = new Date().setHours(0, 0, 0, 0) / 1000;
        var endOfDayTimestamp = new Date().setHours(23, 59, 59, 999) / 1000;
        var percentOfDay = ((currentTimestamp - startOfDayTimestamp) / (endOfDayTimestamp - startOfDayTimestamp)) * 100;
        percentOfDay = Math.max(0, Math.min(100, percentOfDay));
        pourcent = Math.max(-200, Math.min(400, pourcent));
        let hue;
        let light = '30%';
        if (pourcent < 0) {
            // Pourcentage excessif : plus proche du noir
            hue = 120 - percentOfDay * 1.2;
            light = 0;
        } else {
            if (pourcent > 200) {
                // Pourcentage supérieur à 200 : du rouge au noir
                hue = 0; // Rouge à 0 degrés
                light -= pourcent / 10;
            } else {
                // Pourcentage positif : du rouge au noir
                hue = 0 + percentOfDay * 1.2; // Ajoutez du rouge en fonction du pourcentage de la journée
            }
        }
        hue = Math.max(0, Math.min(120, 120 - hue));
        var color = `hsl(${hue}, 100%, ${light}%)`;
        return color;
    }

    function sortIotawattTable() {
        var table = document.getElementById('table_poweriotawatt');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));

        // Fonction pour extraire la valeur numérique depuis la cellule de la colonne "Puissance"
        function extractNumericValue(cell) {
            var valueStr = cell.textContent.trim();
            var parts = valueStr.split(' ');
            var value = parseFloat(parts[0]);
            var unit = parts[1];
            if (unit === 'kWh' || unit === 'kW') {
                value *= 1000;
            }
            return value;
        }

        // Fonction pour comparer les lignes basées sur la colonne "Puissance"
        function compareRows(rowA, rowB) {
            var cellA = rowA.cells[2];
            var cellB = rowB.cells[2];
            var valueA = extractNumericValue(cellA);
            var valueB = extractNumericValue(cellB);
            return valueB - valueA;
        }
        // Trier les lignes
        rows.sort(compareRows);
        // Réorganiser les lignes dans le tableau
        rows.forEach(function (row) {
            tbody.appendChild(row);
        });
        updateTableSort(); // Appel manuel pour mettre à jour le tri
    }

    function updateTableSort() {
     //   $("#table_poweriotawatt").trigger("updateAll"); // Met à jour le tri
    }

    // Fonction pour mettre à jour la somme des valeurs de puissance
    function updateTotalPowerSum() {
        var totalPower = 0;
        var eachPower = document.querySelectorAll('.cmd.power');
        eachPower.forEach(function(a) {
            if (a.getAttribute('data-cmd_id') != null && a.getAttribute('data-linky') != 1) {
                var valueStr = a.innerText;
                var value = parseFloat(valueStr);
                var unit = valueStr.split(' ')[1];
                if (unit === 'kWh' || unit === 'kW') {
                    value *= 1000;
                }
                totalPower += value;
            }
        });
        // Met à jour le contenu du div avec la somme calculée
        var totalPowerSum = document.querySelector('.cmd.power[data-action="powerSum"]');
        if (totalPowerSum) {
            totalPowerSum.innerHTML = totalPower.toFixed(0) + ' W';
        }
    }

    // Fonction pour mettre à jour la somme des valeurs de consommation
    function updateTotalConsoSum(_eachConso = '.cmd.consoTotY', _divConso = 'totalYesterday') {
        var totalConso = 0;
        var eachConso = document.querySelectorAll(_eachConso);
        eachConso.forEach(function(a) {
            if (a.getAttribute('data-cmd_id') != null && a.getAttribute('data-linky') != 1) {
                var valueStr = a.innerText;
                var value = parseFloat(valueStr);
                var unit = valueStr.split(' ')[1];
                if (unit === 'kWh' || unit === 'kW') {
                    value *= 1000;
                }
                totalConso += value;
            }
        });
        var totalConsoUnit = 'Wh';
        if (totalConso > 1000) {
            totalConso /= 1000;
            totalConsoUnit = 'kWh';
        }
        // Met à jour le contenu du div avec la somme calculée
        var totalConsoSum = document.querySelector(_eachConso + '[data-action=' + _divConso + ']');
        if (totalConsoSum) {
            totalConsoSum.innerHTML = totalConso.toFixed(2) + ' ' + totalConsoUnit;
        }
    }

    function updateTotalConsoPourcent() {
        var consoYesterday = parseFloat(document.querySelector('.cmd.consoTotY[data-action="totalYesterday"]').innerText);
        var consoToday = parseFloat(document.querySelector('.cmd.consoTotT[data-action="totalDay"]').innerText);
        var consoSumPourcent = (100 * consoToday / consoYesterday) - 100;
        var posConsoSumPourcent = consoSumPourcent > 0 ? '+' + consoSumPourcent.toFixed(2) : consoSumPourcent.toFixed(2);
        var spanConsoSumPourcent = document.querySelector('.consoTotPourcent[data-action="sum"]');
        if (spanConsoSumPourcent) {
            spanConsoSumPourcent.innerHTML = "<span class='label' style='background-color:"+getColorForPourcentage(consoSumPourcent)+" !important;'>" + posConsoSumPourcent + " %</span>";
        }
    }

    function updateLinkyConsoPourcent() {
        var consoYesterday = parseFloat(document.querySelector('.consoTotY[data-linky="1"]').innerText);
        var consoToday = parseFloat(document.querySelector('.consoTotT[data-linky="1"]').innerText);
        var consoLinkyPourcent = (100 * consoToday / consoYesterday) - 100;
        var posConsoLinkyPourcent = consoLinkyPourcent > 0 ? '+' + consoLinkyPourcent.toFixed(2) : consoLinkyPourcent.toFixed(2);
        var spanConsoLinkyPourcent = document.querySelector('.consoTotPourcent[data-linky="1"]');
        if (spanConsoLinkyPourcent) {
            spanConsoLinkyPourcent.innerHTML = posConsoLinkyPourcent + ' %';
            spanConsoLinkyPourcent.style.backgroundColor = getColorForPourcentage(consoLinkyPourcent) + ' !important';
        }
    }

    $(document).ready(function() {
        // Ajoutez une fonction de mise à jour à chaque commande de puissance
        var powerElements = document.querySelectorAll(".cmd.power");
        if (powerElements) {
            powerElements.forEach(function (element) {
                var cmdId = element.getAttribute('data-cmd_id');
                jeedom.cmd.addUpdateFunction(cmdId, function (_options) {
                    element.setAttribute('title', '{{Date de collecte : }}' + _options.collectDate + '<br/>{{Date de valeur : }}' + _options.valueDate);
                    if ((_options.unit === 'Wh' || _options.unit === 'W') && _options.display_value > 1000 ) {
                        _options.display_value /= 1000;
                        _options.unit = 'k' + _options.unit;
                    }
                    element.textContent = _options.display_value + " " + _options.unit;
                    updateTendanceClass(element); // Met à jour la couleur des badge des puissances
                    updateTableSort();
                    updateTotalPowerSum(); // Met à jour la somme des puissances
                    updateTotalConsoSum('.cmd.consoTotT','totalDay'); // Met à jour la somme des conso d'hier
                    updateTotalConsoSum('.cmd.consoTotY','totalYesterday'); // Met à jour la somme des conso du jour
                    updateTotalConsoPourcent(); // Met à jour le pourcentage de conso d'hier/aujourdhui
                });
                updateTendanceClass(element); // Met à jour la couleur des badge des puissances
            });
        }

        // Ajoute une fonction de mise à jour à chaque commande de consommation
        var consoElements = document.querySelectorAll(".cmd.conso");
        if (consoElements) {
            consoElements.forEach(function (element) {
                var cmdId = element.getAttribute("data-cmd_id");
                jeedom.cmd.addUpdateFunction(cmdId, function (_options) {
                    element.setAttribute('title', '{{Date de collecte : }}' + _options.collectDate + '<br/>{{Date de valeur : }}' + _options.valueDate);
                    element.textContent = _options.display_value + " " + _options.unit;
                    updateTableSort();
                });
            });
        }

        sortIotawattTable(); // Initialisez le tri de la table
        updateTotalPowerSum(); // Mettez à jour la somme initiale
        updateTotalConsoSum('.cmd.consoTotT','totalDay'); // Met à jour la somme des conso d'hier
        updateTotalConsoSum('.cmd.consoTotY','totalYesterday'); // Met à jour la somme des conso du jour
        updateTotalConsoPourcent(); // Met à jour le pourcentage de conso d'hier/aujourdhui

        var table = document.getElementById("table_poweriotawatt");
        if (table) {
            table.addEventListener('click', function (event) {
                // gére le clic sur les valeurs pour afficher les historiques
                if (event.target.matches('.history') || event.target.closest('.history') != null ) {
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
                // gére le clic sur les boutons de configuration de commandes
                if (_target = event.target.closest('.btn.cmdAction[data-action="configure"]')) {
                    $('#md_modal2').dialog({title: '{{Historique}}'}).load('index.php?v=d&modal=cmd.configure&cmd_id=' + event.target.closest('.btn.cmdAction[data-action="configure"]').getAttribute('data-cmd_id')).dialog('open')
                    return
                }
                // gére le clic sur les boutons de configuration des équipements
                if (_target = event.target.closest('.btn.eqLogicAction[data-action="configureEqLogic"]')) {
                    $('#md_modal2').dialog({title: '{{Historique}}'}).load('index.php?v=d&modal=eqLogic.configure&eqLogic_id=' + event.target.closest('.btn.eqLogicAction[data-action="configureEqLogic"]').getAttribute('data-id')).dialog('open')
                    return
                }
            });
        }

        var totalConsoLinky = document.querySelector('.cmd.consoTotY[data-linky="1"]');
        if (totalConsoLinky) {
            var linkyConsoId = totalConsoLinky.getAttribute('data-cmd_id');
            if (linkyConsoId != '') {
                var currentDate = moment().format('YYYY-MM-DD HH:mm:ss');
                var todayMidnight = moment().startOf('day').format('YYYY-MM-DD HH:mm:ss');
                var yesterdayMidnight = moment().subtract(1, 'days').startOf('day').format('YYYY-MM-DD HH:mm:ss');

                jeedom.cmd.addUpdateFunction(linkyConsoId, function (_options) {
                    jeedom.history.get({ // historique d'hier
                        cmd_id: linkyConsoId,
                        dateStart: yesterdayMidnight,
                        dateEnd: todayMidnight,
                        success: function(result) {
                            totalConsoLinky.innerHTML = (result.maxValue - result.minValue).toFixed(2) + ' ' + result.unite;
                        }
                    });
                    jeedom.history.get({ // historique d'aujourd'hui
                        cmd_id: linkyConsoId,
                        dateStart: todayMidnight,
                        dateEnd: currentDate,
                        success: function(result) {
                          document.querySelector('.cmd.consoTotT[data-linky="1"]').innerHTML = (result.maxValue - result.minValue).toFixed(2) + ' ' + result.unite;
                          updateLinkyConsoPourcent(); // Met à jour le pourcentage de conso d'hier/aujourdhui
                        }
                    });
                });
                jeedom.cmd.refreshValue([{cmd_id: linkyConsoId}]);
            }
        }
        var totalPowerLinky = document.querySelector('.cmd.power[data-linky="1"]');
        if (totalPowerLinky) {
            var linkyPowerId = totalPowerLinky.getAttribute('data-cmd_id');
            if (linkyPowerId != '') {
                jeedom.cmd.addUpdateFunction(linkyPowerId, function (_options) {
                    totalPowerLinky.innerHTML = _options.display_value + ' ' + _options.unit;
                    totalPowerLinky.setAttribute('title', '{{Date de collecte : }}' + _options.collectDate + '<br/>{{Date de valeur : }}' + _options.valueDate);
                })
            }
        }
    });
