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

if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
$eqLogics = iotawatt::byType('iotawatt');
include_file('desktop', 'power', 'js', 'iotawatt');
    function getColorForTendance($tendance) {
        $tendance = max(-1.0, min(1.0, $tendance));
        $hue = 120 * (1 - $tendance);
        $hue = max(0, min(120, $hue));
        $color = "hsl(" . $hue . ", 100%, 30%)";
        return $color;
    }

?>

  <style>
    .scanHender{
        cursor: pointer !important;
        width: 100%;
    }
    .power, .conso {
        color: var(--linkHoverLight-color) !important;
    }
    .iconPowerConso {
        font-size: 24px;
    }
    .iconPowerConso i {
        display: inline-block;
        width: 2em;
        text-align: center;
        border: 1px solid silver;
        border-radius: 0.25em;
    }
</style>
<div id="totalPowerSum"></div>
<table class="table table-condensed tablesorter" id="table_poweriotawatt">
	<thead>
		<tr>
			<th><span class="scanHender">{{IoTaWatt}}</span></th>
			<th><span class="scanHender">{{Nom}}</span></th>
			<th class="string-max"><span class="scanHender">{{Puissance}}</span></th>
			<th><span class="scanHender">{{Consommation}}</span></th>
		</tr>
	</thead>
	<tbody>
      <?php

        $cmdArray = array();
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getIsEnable()) {
                foreach ($eqLogic->getCmd('info') as $cmd) {
                    if ($cmd->getConfiguration('type') == 'input') {
                        if ($cmd->getConfiguration('serie') == 'Tension') continue;
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['id'] = $eqLogic->getId();
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['eqName'] = $eqLogic->getConfiguration('name');
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['channel'] = $cmd->getConfiguration('channel');
					    $startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculTendance') . ' hour'));
                        if ($cmd->getConfiguration('totalConsumption', false)) {
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['conso'] = $cmd->execCmd();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoId'] = $cmd->getId();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoName'] = $cmd->getName();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoCollectDate'] = $cmd->getCollectDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoValueDate'] = $cmd->getValueDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoUnit'] = $cmd->getUnite();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoTendance'] = $cmd->getTendance($startHist, date('Y-m-d H:i:s'));
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoIcon'] = $cmd->getDisplay('icon', '');
                        } else {
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['power'] = $cmd->execCmd();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerId'] = $cmd->getId();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerName'] = $cmd->getName();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerCollectDate'] = $cmd->getCollectDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerValueDate'] = $cmd->getValueDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerUnit'] = $cmd->getUnite();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerTendance'] = $cmd->getTendance($startHist, date('Y-m-d H:i:s'));
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerIcon'] = $cmd->getDisplay('icon', '');
                        }
                    }
                }
            }
        }

        echo '<div class="eqLogic-widget">';
        foreach ($cmdArray as $id => $value) {
            echo '<tr>';

            echo '  <td>';
            echo '    [' . $value['eqName'] . ']';
            echo '  </td>';

            echo '  <td>';
            echo '    <span class="iconPowerConso">' . ($value['powerIcon']?:$value['consoIcon']) . '</span>';
            echo '    <span class="namePowerConso">' . str_replace(array(__('Consommation', __FILE__),__('Puissance', __FILE__)), '', $value['consoName']) . '</span>';
            echo '  </td>';

            echo '  <td>';
            $logoTendancePower = ($value['powerTendance'] > config::byKey('historyCalculTendanceThresholddMax')) ? 'fas fa-arrow-up' :
                                 (($value['powerTendance'] < config::byKey('historyCalculTendanceThresholddMin')) ? 'fas fa-arrow-down' : 'fas fa-minus');
            $logoTendanceConso = ($value['consoTendance'] > config::byKey('historyCalculTendanceThresholddMax')) ? 'fas fa-arrow-up' :
                                 (($value['consoTendance'] < config::byKey('historyCalculTendanceThresholddMin')) ? 'fas fa-arrow-down' : 'fas fa-minus');
            echo '    <div class="cmd label cursor history power" style="background-color:' . getColorForTendance($value['powerTendance']) . ' !important;" data-cmd_id="' . $value['powerId'] . '" title="{{Date de collecte : }}' . $value['powerCollectDate'] . '<br/>{{Date de valeur : }} ' . $value['powerValueDate'] . '">' . $value['power'] . ' ' . $value['powerUnit'] . '</div> <i class="'.$logoTendancePower.'"></i>';
            echo '  </td>';

            echo '  <td>';
            echo '    <div class="cmd label cursor history conso" style="background-color:' . getColorForTendance($value['consoTendance']) . ' !important;" data-cmd_id="' . $value['consoId'] . '" title="{{Date de collecte : }}' . $value['consoCollectDate'] . '<br/>{{Date de valeur : }} ' . $value['consoValueDate'] . '">' . $value['conso'] . ' ' . $value['consoUnit'] . '</div> <i class="' . $logoTendanceConso . '"></i>';
            echo '  </td>';

            echo '</tr>';
        }
        echo '</div>';
        ?>
	</tbody>
</table>