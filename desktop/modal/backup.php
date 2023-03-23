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
    throw new Exception('{{401 - Accès non autorisé}}');
}
if (init('eqLogic_id') == '') {
    throw new Exception('{{L\'id de l\'équipement ne peut être vide : }}' . init('eqLogic_id'));
}
$eqLogic = eqLogic::byId(init('eqLogic_id'));
if (!is_object($eqLogic)) {
    throw new Exception('{{Aucun équipement associé à l\'id : }}' . init('eqLogic_id'));
}
$path = 'plugins/iotawatt/core/data/';
$file = 'config.txt';
$config = $eqLogic->request('/' . $file);
$old = json_decode(file_get_contents($path . $file), TRUE);

function arrayDiffRecursive(array $firstArray,array  $secondArray, bool $reverse = false): array
{
    $first = 'old';
    $second = 'new';
    if ($reverse) {
        $first = 'new';
        $second = 'old';
    }
    $diff = [];
    foreach ($firstArray as $k => $value) {
        if (!is_array($value)) {
            if (!array_key_exists($k, $secondArray) || $secondArray[$k] != $value) {
                $diff[$first][$k] = $value;
                $diff[$second][$k] = $secondArray[$k] ?? null;
            }
            continue;
        }
        if (!array_key_exists($k, $secondArray) || !is_array($secondArray[$k])) {
            $diff[$first][$k] = $value;
            $diff[$second][$k] = $secondArray[$k] ?? null;
            continue;
        }
        $newDiff = arrayDiffRecursive($value, $secondArray[$k], $reverse);
        if (!empty($newDiff)) {
            $diff[$first][$k] = $newDiff[$first];
            $diff[$second][$k] = $newDiff[$second];
        }
    }
    return $diff;
}
//{"format":2,"timezone":"1","update":"ALPHA","device":{"name":"IotaWatt","version":3,"channels":"15","burden":[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0]},"inputs":[{"channel":0,"name":"Tension","type":"VT","model":"Ideal 77DE-06-09-VI(EU)","cal":18.98},{"channel":1,"name":"PriseSalonVeranda","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":2,"name":"PriseBoxInternet","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":3,"name":"PriseVideoproj","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":4,"name":"RadFlorine","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":5,"name":"RadVerandaHomeC","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":6,"name":"iPadSonnette","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":7,"name":"ChauffeEau","type":"CT","model":"SCT013-030","phase":3.8,"cal":30},{"channel":8,"name":"LumCuisineWCFrigo","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":9,"name":"inconnu","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":10,"name":"LumiereEntreePoele","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":11,"name":"RadSdBChFlo","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":12,"name":"LumSalonEscalier","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":13,"name":"Inconnu2A","type":"CT","model":"generic","phase":"3.8","cal":"20"},{"channel":14,"name":"LumEtageRadServiette","type":"CT","model":"generic","phase":"3.8","cal":"20"}],"outputs":[{"name":"IntensitePrisVer","units":"Amps","script":"@1"},{"name":"PuissanceTotale","units":"Watts","script":"@1+@2+@3+@4+@5+@6+@7+@8+@9+@10+@11+@12+@13+@14"},{"name":"toto","units":"Watts","script":"!+BoxInternet"},{"name":"tutu","units":"Watts","script":"!-BoxInternet|"}],"integrators":[{"name":"BoxInternet","units":"Wh","script":"@2"}]}

?>
<div role="tabpanel">
  <div class="tab-content" id="div_displayBackup" style="overflow-x:hidden">
  <div class="input-group pull-right" style="display:inline-flex">
    <span class="input-group-btn">
      </a><a class="btn btn-success btn-sm roundedRight roundedLeft" id="bt_cmdCreateSave"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
    </span>
  </div>
    <?php
      $update = false;
      if (is_array($config) && $config['device']) {
          if (is_array($old) && $old['device']) {
              $diff = arrayDiffRecursive($old, $config);
              if (count($diff) > 0) {
                  if (isset($diff['new'])) {
                      echo '<span class="label label-warning">{{Valeurs changées, fichier mis à jour.}}</span>';
                      echo '<pre class="configTxt">' . json_encode($diff['new'], JSON_PRETTY_PRINT) . '</pre>';
                      $update = true;
                  }
              } else {
                  echo '<span class="label label-info">{{Fichier déjà à jour.}}</span>';
              }
          } else {
              echo '<span class="label label-success">{{Fichier}} ' . $path . $file . ' {{ inexistant, il vient d\'être ajouté.}}</span>';
              echo '<pre class="configTxt">' . json_encode($config, JSON_PRETTY_PRINT) . '</pre>';
              $update = true;
          }
      }
      if ($update) {
          if (!is_dir($path)){
              mkdir($path, 0700);
          }
          file_put_contents($path . $file, json_encode($config, JSON_PRETTY_PRINT));
      }
    ?>
  </div>
</div>

<script>

</script>
