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
function getColorForTendance($tendance) {
    $tendance = max(-1.0, min(1.0, $tendance));
    $hue = 120 * (1 - $tendance);
    $hue = max(0, min(120, $hue));
    $color = "hsl(" . $hue . ", 100%, 30%)";
    return $color;
}
function getColorForPourcentage($pourcent) {
    if (is_nan($pourcent)) {
        return "hsl(0, 0%, 50%)";
    }
    $light = 30;
    $currentTimestamp = time();
    $startOfDayTimestamp = strtotime('today', $currentTimestamp);
    $endOfDayTimestamp = strtotime('tomorrow', $startOfDayTimestamp) - 1;
    $percentOfDay = (($currentTimestamp - $startOfDayTimestamp) / ($endOfDayTimestamp - $startOfDayTimestamp)) * 100;
    $percentOfDay = max(0, min(100, $percentOfDay));
    $pourcent = max(-200, min(400, $pourcent)); // Ajusté à -200 à 400 pour la transition complète

    if ($pourcent < 0) {
        // Pourcentage négatif : du vert au rouge
        $hue = 120 - $percentOfDay * 1.2; // Soustrayez du vert en fonction du pourcentage de la journée
    } else {
        if ($pourcent > 200) {
            // Pourcentage supérieur à 200 : du rouge au noir
            $hue = 0; // Rouge à 0 degrés
            $light -= $pourcent / 10;
        } else {
            // Pourcentage positif : du rouge au noir
            $hue = 0 + $percentOfDay * 1.2; // Ajoutez du rouge en fonction du pourcentage de la journée
        }
    }

    $hue = max(0, min(120, 120 - $hue));
    $color = "hsl(" . $hue . ", 100%, " . $light . "% )";
    return $color;
}

?>

  <style>
    .tablesorter-resizable-container {
        display: none;
    }
    .scanHender{
        cursor: pointer !important;
        width: 100%;
    }
    .power,
    .conso,
    .consoTotY,
    .consoTotT,
    .consoTotPourcent,
    .consoSumPourcent {
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
<table class="table table-condensed tablesorter" id="table_poweriotawatt">
	<thead>
		<tr>
			<th><span class="scanHender">{{Appareil IoTaWatt}}</span></th>
			<th><span class="scanHender">{{Nom}}</span></th>
			<th class="string-max"><span class="scanHender">{{Puissance}}</span></th>
			<th><span class="scanHender">{{Consommation Totale}}</span></th>
			<th><span class="scanHender">{{Consommation hier (0h-24h)}}</span></th>
			<th><span class="scanHender">{{Consommation du jour (0h-24h)}}</span></th>
		</tr>
	</thead>
	<tbody>
      <?php
        $cmdArray = array();
        $totalPower = 0;
        foreach ($eqLogics as $eqLogic) {
            if ($eqLogic->getIsEnable()) {
                foreach ($eqLogic->getCmd('info') as $cmd) {
                    if ($cmd->getConfiguration('type') == 'input') {
                        //if ($cmd->getConfiguration('serie') == 'Tension') continue;
                        if (!$cmd->getDisplay('showOnPanel')) continue;
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['id'] = $eqLogic->getId();
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['eqName'] = $eqLogic->getConfiguration('name');
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['channel'] = $cmd->getConfiguration('channel');
					    $startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculTendance') . ' hour'));
                        $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['eqLink'] = '<a href="' . $eqLogic->getLinkToConfiguration() . '" class="btn btn-xs btn-primary">' . $eqLogic->getHumanName(true,false,true) . '</a><br/>';

                        if ($cmd->getConfiguration('totalConsumption', false)) {
                          	$valueInfo = cmd::autoValueArray($cmd->execCmd(), 2, $cmd->getUnite());
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['conso'] = $valueInfo[0];
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoUnit'] = $valueInfo[1];
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoOldUnit'] = $cmd->getUnite();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoId'] = $cmd->getId();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoName'] = $cmd->getName();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoCollectDate'] = $cmd->getCollectDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoValueDate'] = $cmd->getValueDate();
                            $consoTendance = $cmd->getTendance($startHist, date('Y-m-d H:i:s'));
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoTendance'] = $consoTendance;
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['logoTendanceConso'] = ($consoTendance > config::byKey('historyCalculTendanceThresholddMax')) ? '<i class="fas fa-arrow-up"></i>' : (($consoTendance < config::byKey('historyCalculTendanceThresholddMin')) ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-minus"></i>');                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoIcon'] = $cmd->getDisplay('icon', '');
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['consoStats'] = $cmd->getStatistique(date('Y-m-d 00:00:00', strtotime('- 1 day')), date('Y-m-d 00:00:00'));
                        } else {
                          	$valueInfo = cmd::autoValueArray($cmd->execCmd(), 2, $cmd->getUnite());
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['power'] = $valueInfo[0];
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerUnit'] = $valueInfo[1];
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerOldUnit'] = $cmd->getUnite();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerId'] = $cmd->getId();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerName'] = $cmd->getName();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerCollectDate'] = $cmd->getCollectDate();
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerValueDate'] = $cmd->getValueDate();
                            $powerTendance = $cmd->getTendance($startHist, date('Y-m-d H:i:s'));
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerTendance'] = $powerTendance;
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['logoTendancePower'] = ($powerTendance > config::byKey('historyCalculTendanceThresholddMax')) ? '<i class="fas fa-arrow-up"></i>' : (($powerTendance < config::byKey('historyCalculTendanceThresholddMin')) ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-minus"></i>');
                            $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['powerIcon'] = $cmd->getDisplay('icon', '');
                            $totalPower += $cmdArray[$eqLogic->getId().'::'.$cmd->getConfiguration('serie')]['power'];
                        }
                    }
                }
            }
        }

        echo '<div id="totalSum">';
        echo '    <span class="powerSum">';
        echo '        {{Puissance totale}} : <span class="label label-info">' . round($totalPower,2) . ' W</span>';
        echo '    </span>';
        echo '    <span class="consoSumY">';
        echo '    </span>';
        echo '    <span class="consoSumT">';
        echo '    </span>';
        echo '    <span class="consoSumPourcent">';
        echo '    </span>';
        echo '</div>';
        echo '<div class="eqLogic-widget">';
        foreach ($cmdArray as $id => $value) {
             echo '<tr>';

            echo '  <td>';
            echo '    <a class="btn btn-default btn-xs tooltipstered eqLogicAction roundedLeft" data-id="' . $value['id'] . '" data-action="configureEqLogic" title="{{Configuration de l\'équipement}}"><i class="fas fa-cogs"></i></a>';
            echo $value['eqLink'];
            //echo ' [' . $value['eqName'] . ']</span>';
            echo '  </td>';

            echo '  <td>';
            echo '    <div class="input-group" style="display:inline-flex;">';
            echo '      <a class="btn btn-default btn-xs tooltipstered cmdAction roundedLeft" data-cmd_id="' . $value['powerId'] . '" data-action="configure" title="{{Configuration de la commande de puissance}}"><i class="icon kiko-lightning"></i></a>';
            echo '      <a class="btn btn-default btn-xs tooltipstered cmdAction roundedRight" data-cmd_id="' . $value['consoId'] . '" data-action="configure" title="{{Configuration de la commande de consommation}}"><i class="icon kiko-electricity"></i></a>';
            echo '    </div>';
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

            echo '  <td>';
            $consoYesterday = ($value['consoStats']['max'] - $value['consoStats']['min']);
            $valueInfoYest = cmd::autoValueArray($consoYesterday, 2, $value['consoOldUnit']);
            echo '    <div class="cmd label label-info cursor history consoTotY" data-cmd_id="' . $value['consoId'] . '">' . $valueInfoYest[0] . ' ' . $valueInfoYest[1] . '</div>';

            echo '  </td>';

            echo '  <td>';

            $valueInfoDiff = ($value['consoUnit']=='kWh'?($value['conso']*1000):$value['conso']) - $value['consoStats']['max'];
            $valueInfoT = cmd::autoValueArray($valueInfoDiff, 2, $value['consoOldUnit']);
            $consoTodayVal = round($valueInfoT[0], 2);
            $consoPourcent = round(100 * $valueInfoDiff / ($consoYesterday),2) - 100;
            $posConsoPourcent = $consoPourcent > 0 ? '+' . $consoPourcent : $consoPourcent;
            echo '    <div class="cmd label label-info cursor history consoTotT" data-cmd_id="' . $value['consoId'] . '">' . $consoTodayVal . ' ' . $valueInfoT[1] . '</div>';
            echo '    <div class="cmd label label-info cursor history consoTotPourcent" style="background-color:' . getColorForPourcentage($consoPourcent) . ' !important;" data-cmd_id="' . $value['consoId'] . '">' . $posConsoPourcent .  ' %</div>';

            echo '  </td>';
            echo '</tr>';
        }
        echo '</div>';
        ?>
	</tbody>
</table>
<?php
include_file('desktop', 'power', 'js', 'iotawatt');
?>
