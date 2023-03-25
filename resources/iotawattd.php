#!/usr/
<?php

require_once dirname(__FILE__) . '/../core/class/iotawatt.class.php';

log::add('iotawatt', 'info', __('Activation du service IoTaWatt', __FILE__));

$eqLogics = eqLogic::byType('iotawatt', true);
$delay = config::byKey('deamonRefresh', 'iotawatt', 0);
log::add('iotawatt', 'debug', __('Intervalle du démon :', __FILE__) . $delay);
if (!$delay) {
    log::add('iotawatt', 'error', __('Veuillez choisir un intervalle ', __FILE__));
    iotawatt::deamon_stop();
}
try {
    while (true) {
        foreach ($eqLogics as $iotawatt) {
            if (count($iotawatt->getCmd('info')) == 0) {
                $iotawatt->setStatus('lastValueUpdate', 0);
            }
            $iotawatt->updateStatus($iotawatt->getIotaWattStatus(array('stats' => true, 'inputs' => true, 'outputs' => true)));
        }
        sleep($delay);
    }
} catch (Exception $e) {
    log::add('iotawatt', 'info', __('Erreur rencontrée ', __FILE__). json_encode(utils::o2a($e)));
}

?>
