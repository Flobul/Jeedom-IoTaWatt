
<?php
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

   require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
   include_file('core', 'authentification', 'php');
   if (!isConnect()) {
       include_file('desktop', '404', 'php');
       die();
   }
   $plugin = plugin::byId('iotawatt');
   sendVarToJS('version', iotawatt::$_pluginVersion);
   ?>

<form class="form-horizontal">
   <fieldset>
      <legend>
         <i class="fa fa-list-alt"></i> {{Général}}
      </legend>
      <div class="form-group">
         <?php
            $update = $plugin->getUpdate();
            if (is_object($update)) {
                echo '<div class="col-lg-3">';
                echo '<div>';
                echo '<label>{{Branche}} :</label> '. $update->getConfiguration('version', 'stable');
                echo '</div>';
                echo '<div>';
                echo '<label>{{Source}} :</label> ' . $update->getSource();
                echo '</div>';
                echo '<div>';
                echo '<label>{{Version}} :</label> v' . ((iotawatt::$_pluginVersion)?iotawatt::$_pluginVersion:' '). ' (' . $update->getLocalVersion() . ')';
                echo '</div>';
                echo '</div>';
            }
            ?>
         <div class="col-lg-5">
            <div>
               <i><a class="btn btn-primary btn-xs" target="_blank" href="https://flobul-domotique.fr/presentation-du-plugin-iotawatt-pour-jeedom/"><i class="fas fa-book"></i><strong> {{Présentation du plugin}}</strong></a></i>
               <i><a class="btn btn-success btn-xs" target="_blank" href="<?=$plugin->getDocumentation()?>"><i class="fas fa-book"></i><strong> {{Documentation complète du plugin}}</strong></a></i>
            </div>
            <div>
               <i> {{Les dernières actualités du plugin}} <a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/t/plugin-iotawatt-documentation-et-actualites/39994"><i class="icon jeedomapp-home-jeedom icon-iotawatt"></i><strong>{{sur le community}}</strong></a>.</i>
            </div>
            <div>
               <i> {{Les dernières discussions autour du plugin}} <a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/tags/plugin-iotawatt"><i class="icon jeedomapp-home-jeedom icon-iotawatt"></i><strong>{{sur le community}}</strong></a>.</i></br>
               <i> {{Pensez à mettre le tag}} <b><font font-weight="bold" size="+1">#plugin-iotawatt</font></b> {{et à fournir les log dans les balises préformatées}}.</i>
            </div>
            <style>
               .icon-iotawatt {
                   font-size: 1.3em;
                   color: #94CA02;
               }

               :root{
                 --background-color: #1987ea;
                }
            </style>
         </div>
      </div>
        <legend>
		<i class="fas fa-cogs"></i> {{Paramètres}}
		</legend>
          <div class="form-group">
              <label class="col-lg-4 control-label">{{Intervalle de rafraîchissement de toutes les informations (cron)}}
      <sup><i class="fas fa-question-circle" title="{{Sélectionnez l'intervalle auquel le plugin ira récupérer les informations de iotawatt.}}</br>{{En fonction des commandes infos de l'équipement, les valeurs des unités Volts, Watts, Wh, Amps, VA, Hz, PF, VAR, VARh seront mises à jour.}}"></i></sup>
              </label>
              <div class="col-lg-4">
                  <select class="configKey form-control" data-l1key="autorefresh" >
                      <option value="* * * * *">{{Toutes les minutes}}</option>
                      <option value="*/2 * * * *">{{Toutes les 2 minutes}}</option>
                      <option value="*/3 * * * *">{{Toutes les 3 minutes}}</option>
                      <option value="*/4 * * * *">{{Toutes les 4 minutes}}</option>
                      <option value="*/5 * * * *">{{Toutes les 5 minutes}}</option>
                      <option value="*/10 * * * *">{{Toutes les 10 minutes}}</option>
                      <option value="*/15 * * * *">{{Toutes les 15 minutes}}</option>
                      <option value="*/30 * * * *">{{Toutes les 30 minutes}}</option>
                      <option value="*/45 * * * *">{{Toutes les 45 minutes}}</option>
                      <option value="">{{Jamais}}</option>
                  </select>
              </div>
          </div>
          <div class="form-group">
              <label class="col-lg-4 control-label">{{Intervalle de rafraîchissement des informations de puissance/tension (démon)}}
      <sup><i class="fas fa-question-circle" title="{{Sélectionnez l'intervalle auquel le plugin ira récupérer les informations de iotawatt.}}</br>{{Seules les valeurs d'entrées (0=Volts, 1-14=Watts) et sorties (Watts) sont mises à jour.}}"></i></sup>
              </label>
              <div class="col-lg-4">
                  <select class="configKey form-control" data-l1key="deamonRefresh" >
                      <option value="1">{{Toutes les secondes}}</option>
                      <option value="2">{{Toutes les 2 secondes}}</option>
                      <option value="3">{{Toutes les 3 secondes}}</option>
                      <option value="4">{{Toutes les 4 secondes}}</option>
                      <option value="5">{{Toutes les 5 secondes}}</option>
                      <option value="6">{{Toutes les 6 secondes}}</option>
                      <option value="7">{{Toutes les 7 secondes}}</option>
                      <option value="8">{{Toutes les 8 secondes}}</option>
                      <option value="9">{{Toutes les 9 secondes}}</option>
                      <option value="10">{{Toutes les 10 secondes}}</option>
                      <option value="15">{{Toutes les 15 secondes}}</option>
                      <option value="20">{{Toutes les 20 secondes}}</option>
                      <option value="25">{{Toutes les 25 secondes}}</option>
                      <option value="30">{{Toutes les 30 secondes}}</option>
                      <option value="35">{{Toutes les 35 secondes}}</option>
                      <option value="40">{{Toutes les 40 secondes}}</option>
                      <option value="45">{{Toutes les 45 secondes}}</option>
                      <option value="50">{{Toutes les 50 secondes}}</option>
                      <option value="55">{{Toutes les 55 secondes}}</option>
                      <option value="">{{Jamais}}</option>
                  </select>
              </div>
          </div>
      </div>
   </fieldset>
</form>
<?php include_file('desktop', 'configuration', 'js', 'iotawatt'); ?>