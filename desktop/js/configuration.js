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
    function addCondition(_expression, _idElementLinky) {
        jeedom.cmd.byHumanName({
            humanName: _expression,
            error: function (error) {
                $.fn.showAlert({message: error.message, level: 'danger'});
            },
            success: function (data) {
              var html = data.isHistorized?'<span class="label label-success">{{Commande historisée}}</span>':'<span class="label label-danger">{{Commande non historisée}}</span>';
              document.getElementById(_idElementLinky).innerHTML = html;
              console.log(data)
            }
        });
    }
   document.querySelector('.configKey[data-l1key=linky]').addEventListener('change', function (event) {
       addCondition(document.querySelector('.configKey[data-l1key=linky]').value, 'infoCmdLinky');
   })
   document.querySelector('.configKey[data-l1key=powerLinky]').addEventListener('change', function (event) {
       addCondition(document.querySelector('.configKey[data-l1key=powerLinky]').value, 'infoCmdPowerLinky');
   })
   // afficher juste avant la version, la véritable version contenue dans le plugin
   var dateVersion = $("#span_plugin_install_date").html();
   $("#span_plugin_install_date").empty().append("v" + version + " (" + dateVersion + ")");

   $('.bt_refreshPluginInfo').after('<a class="btn btn-success btn-sm" target="_blank" href="https://market.jeedom.com/index.php?v=d&p=market_display&id=4099"><i class="fas fa-comment-dots "></i> Donner mon avis</a>');

  document.querySelector('.formIotawatt .cmdPowerLinky').addEventListener('click', function(event) {
      jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
          document.querySelector('.configKey[data-l1key=powerLinky]').value = result.human;
          addCondition(result.human, 'infoCmdPowerLinky');
      });
  });
  document.querySelector('.formIotawatt .cmdLinky').addEventListener('click', function(event) {
      jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
          document.querySelector('.configKey[data-l1key=linky]').value = result.human;
          addCondition(result.human, 'infoCmdLinky');
      });
  });