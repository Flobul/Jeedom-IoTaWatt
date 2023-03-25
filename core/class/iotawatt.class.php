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

/* * ***************************Includes********************************* */
require_once __DIR__ . "/../../../../core/php/core.inc.php";

class iotawatt extends eqLogic
{
    /*     * *************************Attributs****************************** */
    public static $_widgetPossibility = array('custom' => true, 'custom::layout' => false);
    public static $_pluginVersion = '0.60';

    /*     * ***********************Methode statique*************************** */
    public function getParamUnits($_unit, $_param) {
        $array = array(
            'Volts' => array(
                'name' => __('Tension', __FILE__),
                'unit' => __('V', __FILE__),
                'decimals' => 1,
                'minValue' => 0,
                'generic' => 'VOLTAGE'
            ),
            'Watts' => array(
                'name' => __('Puissance', __FILE__),
                'unit' => __('W', __FILE__),
                'decimals' => 1,
                'generic' => 'POWER'
            ),
            'Wh' => array(
                'name' => __('Consommation', __FILE__),
                'unit' => __('Wh', __FILE__),
                'decimals' => 2,
                'minValue' => 0,
                'generic' => 'CONSUMPTION'
            ),
            'Amps' => array(
                'name' => __('Intensité', __FILE__),
                'unit' => __('A', __FILE__),
                'decimals' => 1
            ),
            'VA' => array(
                'name' => __('Puissance active', __FILE__),
                'unit' => __('VA', __FILE__),
                'decimals' => 1
            ),
            'Hz' => array(
                'name' => __('Fréquence', __FILE__),
                'unit' => __('Hz', __FILE__),
                'decimals' => 2
            ),
            'PF' => array(
                'name' => __('Facteur de puissance', __FILE__),
                'unit' => '%',
                'decimals' => 1,
                'minValue' => 0,
                'minValue' => 100
            ),
            'VAR' => array(
                'name' => __('Puissance réactive', __FILE__),
                'unit' => __('VAr', __FILE__),
                'decimals' => 1
            ),
            'VARh' => array(
                'name' => __('Énergie réactive', __FILE__),
                'unit' => __('VAhr', __FILE__),
                'decimals' => 0
            )
        );
        return isset($array[$_unit]) ? ($_param == 'all' ? $array[$_unit] : $array[$_unit][$_param]) : null;
    }

    /**
     * Renvoie la requete en une url
     *
     * @return	 		string		URL contenant la requête
     */
    protected static function buildQueryString(array $params)
    {
        return http_build_query($params, null, '&', PHP_QUERY_RFC3986);
    }

    public static function convertCrontabToMinutes($_crontab)
    {
        return str_replace('*/', '', explode(' ', $_crontab)[0]);
    }

    public static function update()
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début', __FILE__));
        $autorefresh = config::byKey('autorefresh', 'iotawatt', '');
        if ($autorefresh != '') {
            try {
                $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                if ($c->isDue()) {
                    try {
                        foreach (eqLogic::byType('iotawatt', true) as $iotawatt) {
                            if (count($iotawatt->getCmd('info')) == 0) {
                                $iotawatt->setStatus('lastValueUpdate', 0);
                                //return;
                            }
                            $iotawatt->getSeries();
                            $iotawatt->getSensors();
                        }
                    } catch (Exception $exc) {
                        log::add('iotawatt', 'error', __('Erreur : ', __FILE__) . $exc->getMessage());
                    }
                }
            } catch (Exception $exc) {
                log::add('iotawatt', 'error', __('Expression cron non valide : ', __FILE__) . $autorefresh);
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin', __FILE__));
    }

    public static function cronDayly()
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début', __FILE__));
        $autorefresh = config::byKey('autorefresh', 'iotawatt', '');
        if ($autorefresh != '') {
            try {
                $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                if ($c->isDue()) {
                    try {
                        foreach (eqLogic::byType('iotawatt', true) as $iotawatt) {
                            $iotawatt->getSeries();
                            $iotawatt->updateStatus($iotawatt->getIotaWattStatus(array('passwords' => true, 'stats' => true, 'wifi' => true, 'device' => true)));
                            $iotawatt->save();
                        }
                    } catch (Exception $exc) {
                        log::add('iotawatt', 'error', __('Erreur : ', __FILE__) . $exc->getMessage());
                    }
                }
            } catch (Exception $exc) {
                log::add('iotawatt', 'error', __('Expression cron non valide : ', __FILE__) . $autorefresh);
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin', __FILE__));
    }

    public static function deamon_info() {
        $return = array();
        $return['log'] = 'iotawatt';
        $return['state'] = 'nok';
        $pid = trim(shell_exec('ps ax | grep "/iotawattd.php" | grep -v "grep" | wc -l'));
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';

        return $return;
    }

    public static function deamon_start($_debug = false) {
        log::add(__CLASS__, 'info', __('Lancement du service iotawatt', __FILE__));
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        if ($deamon_info['state'] == 'ok') {
            self::deamon_stop();
            sleep(2);
        }
        log::add('iotawatt', 'info', __('Lancement du démon iotawatt', __FILE__));
        $cmd = substr(dirname(__FILE__),0,strpos (dirname(__FILE__),'/core/class')).'/resources/iotawattd.php';
        log::add('iotawatt', 'debug', __('Commande du Deamon : ', __FILE__) . $cmd);
        $result = exec('sudo php ' . $cmd . ' >> ' . log::getPathToLog('iotawattd') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('iotawatt', 'error', 'Deamon error : ' . $result);
            return false;
        }
        sleep(1);
        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('iotawatt', 'error', 'Impossible de lancer le démon iotawattd', 'unableStartDeamon');
            return false;
        }
        log::add('iotawatt', 'info', __('Démon iotawattd lancé', __FILE__));
        return true;
    }

    public static function deamon_stop() {
        log::add('iotawatt', 'info', __('Arrêt du service iotawatt', __FILE__));
        $cmd = '/iotawattd.php';
        exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
        sleep(1);
        exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
        sleep(1);
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
            sleep(1);
        } else {
            return true;
        }
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
            sleep(1);
            return true;
        }
    }

    public static function health() {
        $return = array();

        $mem = exec("ps aux | grep iotawatt | grep -v sudo | head -n1 | awk '/[0-9]/{print $5}'");
        $advice = __('Mémoire allouée par le processus', __FILE__);
        $state = false;
        if ($mem < 300000) {
            $state = true;
        }

        $credentials = array(
            'test' => __('Mémoire utilisée', __FILE__),
            'result' => $mem . ' Ko',
            'advice' => $advice,
            'state' => $state
        );
        array_unshift($return, $credentials);
        return $return;
    }

	public function getUrl() {
        $id = $this->getConfiguration('id', false);
        $password = $this->getConfiguration('password', false);
        $url = 'http://' . ($id && $password ? "$id:$password@" : '') . ($this->getConfiguration('ip') ?: 'iotawatt.local') . '/';
        return $url;
	}

    /**
     * Méthode appellée avant la création de l'objet
     * Active et affiche l'objet
     */
    public function preInsert()
    {
        $this->setIsEnable(1);
        $this->setIsVisible(1);
    }

    public function preUpdate()
    {
        $this->getSeries();
        $this->updateStatus($this->getIotaWattStatus(array('passwords' => true, 'stats' => true, 'wifi' => true, 'inputs' => true, 'outputs' => true, 'device' => true, 'stats' => true)));
        $rebootCmd = $this->getCmd('action', 'reboot');
        if (!is_object($rebootCmd)) {
            $rebootCmd = new iotawattCmd();
            $rebootCmd->setName(__('Redémarrer', __FILE__));
            $rebootCmd->setLogicalId('reboot');
            $rebootCmd->setOrder(9998);
            $rebootCmd->setEqLogic_id($this->getId());
            $rebootCmd->setType('action');
            $rebootCmd->setSubType('other');
            $rebootCmd->save();
        }

        $refreshCmd = $this->getCmd('action', 'refresh');
        if (!is_object($refreshCmd)) {
            $refreshCmd = new iotawattCmd();
            $refreshCmd->setName(__('Rafraîchir', __FILE__));
            $refreshCmd->setLogicalId('refresh');
            $refreshCmd->setOrder(9999);
            $refreshCmd->setEqLogic_id($this->getId());
            $refreshCmd->setType('action');
            $refreshCmd->setSubType('other');
            $refreshCmd->save();
        }
    }

    public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

    public function getIotaWattStatus($_param)
    {
        if (is_array($_param)) {
            $_param = self::buildQueryString($_param);
        }
        $status = $this->request('status?' . $_param);
        if (is_array($status)) {
            return $status;
        }
        return false;
    }

    public function updateStatus($_status)
    {
        if (!is_array($_status))  return false;
        if (isset($_status['device'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' device=' . json_encode($_status['device']));
            //{"device":{"name":"IotaWatt","timediff":60,"allowdst":false,"update":"ALPHA"}}
            $this->setConfiguration('name', $_status['device']['name']);
            $this->setConfiguration('timediff', $_status['device']['timediff']);
            $this->setConfiguration('update', $_status['device']['update']);
        }
        if (isset($_status['stats'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' stats=' . json_encode($_status['stats']));
            //{"stats":{"cyclerate":771.7698,"chanrate":33.36037,"starttime":1677953821,"currenttime":1678092109,"runseconds":138288,"stack":25048,"version":"02_08_02","frequency":50.0487,"lowbat":false}}
            $this->setConfiguration('lastUpdateTime', date('Y-m-d H:i:s', $_status['stats']['currenttime']));
            $this->setConfiguration('startTime', date('Y-m-d H:i:s', $_status['stats']['starttime']));
            $this->setConfiguration('runSeconds', $_status['stats']['runseconds']);
            $this->setConfiguration('firmwareVersion', $_status['stats']['version']);
            $this->setStatus('lowbat', $_status['stats']['lowbat']);
        }
        if (isset($_status['wifi'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' wifi=' . json_encode($_status['wifi']));
            //{"wifi": { "connecttime": 3, "SSID": "Livebox-3756", "IP": "192.168.0.91", "channel": 6, "RSSI": -44, "mac": "3C:61:05:FA:C6:F7" }}
            $this->setConfiguration('mac', $_status['wifi']['mac']);
            $this->setConfiguration('SSID', $_status['wifi']['SSID']);
            $this->setStatus('RSSI', $_status['wifi']['RSSI']);
            $this->setStatus('connecttime', $_status['wifi']['connecttime']);
        }
        if (isset($_status['passwords'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' passwords=' . json_encode($_status['passwords']));
            //{"passwords": { "admin": false, "user": false, "localAccess": false}}
            $this->setConfiguration('admin', $_status['passwords']['admin']);
            $this->setConfiguration('user', $_status['passwords']['user']);
            $this->setConfiguration('localAccess', $_status['passwords']['localAccess']);
        }
        if (isset($_status['state'])) {
            //???
        }
        if (isset($_status['datalogs'])) {
            //{"datalogs":[{"id":"Current","firstkey":1677770475,"lastkey":1678092105,"size":16396544,"interval":5},{"id":"History","firstkey":1677770520,"lastkey":1678092060,"size":1372160,"interval":60}]}
        }
        if (isset($_status['influx1'])) {
            //{"influx1":{"state":"not running"}}
        }
        if (isset($_status['influx2'])) {
            //{"influx2":{"state":"not running"}}
        }
        if (isset($_status['emoncms'])) {
            //{"emoncms":{"state":"not running"}}
        }
        if (isset($_status['pvoutput'])) {
            //{"pvoutput":{"state":"not running"}}
        }
        if (isset($_status['inputs'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' inputs=' . json_encode($_status['inputs']));
            //{"inputs":[{"channel":0,"Vrms":239.4988,"Hz":50.04351,"phase":2.53},{"channel":1,"Watts":" 6","Pf":0.44705,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":2,"Watts":"58","Pf":0.557318,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":3,"Watts":" 0","Pf":0,"phase":3.8,"lastphase":1.27},{"channel":4,"Watts":" 0","Pf":0,"phase":3.8,"lastphase":1.27},{"channel":5,"Watts":"24","Pf":0.400131,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":6,"Watts":" 5","Pf":0.474841,"phase":3.8,"lastphase":1.27},{"channel":7,"Watts":" 0","Pf":0,"phase":3.8,"lastphase":1.27},{"channel":8,"Watts":"52","Pf":0.969458,"phase":3.8,"lastphase":1.27},{"channel":9,"Watts":"18","Pf":0.382351,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":10,"Watts":" 6","Pf":0.544188,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":11,"Watts":"677","Pf":0.747267,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":12,"Watts":"26","Pf":0.5329,"reversed":true,"phase":3.8,"lastphase":1.27},{"channel":13,"Watts":" 0","Pf":0,"phase":3.8,"lastphase":1.27},{"channel":14,"Watts":" 3","Pf":0.404697,"phase":3.8,"lastphase":1.27}]}
            $series = $this->getSeries();
            $this->setConfiguration('nbInputs', count($_status['inputs']));
            for ($i = 0; $i < count($_status['inputs']); $i++) {
                $cmdInput = $this->createCmdInfo($_status['inputs'][$i], 'input', $series[$i]);
                if (is_object($cmdInput)) {
                    $unit = $cmdInput->getConfiguration('valueType') === 'Volts' ? 'Vrms' : $cmdInput->getConfiguration('valueType');
                    if ($cmdInput->execCmd() !== $cmdInput->formatValue(floatval($_status['inputs'][$i][$unit]))) {
                        $cmdInput->event(round(floatval($_status['inputs'][$i][$unit]),2), date('Y-m-d H:i:s', $_status['stats']['currenttime']));
                    }
                }
            }
        }
        if (isset($_status['outputs'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' outputs=' . json_encode($_status['outputs']) . $this->getConfiguration('nbOutputs'));
            //{"outputs":[{"name":"PuissanceTotale","units":"Watts","value":874.2493},{"name":"Tension","units":"Volts","value":239.4988}]}
            $this->setConfiguration('nbOutputs', count($_status['outputs']));
            for ($i = 0; $i < count($_status['outputs']); $i++) {
                $cmdOutput = $this->createCmdInfo($_status['outputs'][$i], 'output');
                //{"name":"tutu","units":"Watts","value":0}
                if (is_object($cmdOutput)) {
                    $unit = $cmdOutput->getConfiguration('valueType') === 'Volts' ? 'Vrms' : $cmdOutput->getConfiguration('valueType');
                    if ($_status['outputs'][$i]['units'] == $unit) {
                        if ($cmdOutput->execCmd() !== $cmdOutput->formatValue(floatval($_status['outputs'][$i]['value']))) {
                            $cmdOutput->event(round(floatval($_status['outputs'][$i]['value']),2), date('Y-m-d H:i:s', $_status['stats']['currenttime']));
                        }
                    }
                }
            }
        }
        //$this->save();
    }

    public function createCmdInfo($_IO, $_type, $_serie = array())
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' type=' . $_type . ' IO=' . json_encode($_IO) . ' serie=' . json_encode($_serie));

        if ($_type == 'input') {
            $serie = $_serie['name'];
            $unit = $_serie['unit'];
            $name = self::getParamUnits($unit, 'name') . ' I' . sprintf("%02d", $_IO['channel']) . ' ' . $serie;
            $logicalId = $_type . '_' . $_IO['channel'] . '_' . $unit;
        } else {
            $serie = $_IO['name'];
            $unit = $_IO['units'];
            $name = self::getParamUnits($unit, 'name') . ' ' . $serie;
            $logicalId = $_type . '_' . $_IO['name'] . '_' . $unit;
        }
        $order = isset($_IO['channel']) ? $_IO['channel'] : (count($this->getCmd('info')));

        $cmd = $this->getCmd('info', $logicalId);
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Création de la commande', __FILE__));
            $cmd = new iotawattCmd();
            $cmd->setOrder($order);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId($logicalId);
            $cmd->setName($name);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite(self::getParamUnits($unit, 'unit'));
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(1);
            $cmd->setConfiguration('channel', $_IO['channel'] ?? 'N/A');
            $cmd->setConfiguration('type', $_type);
            $cmd->setConfiguration('totalConsumption', $_IO['manual']?true:null);
            $cmd->setConfiguration('serie', $serie);
            $cmd->setConfiguration('valueType', $unit);
            $cmd->setConfiguration('round', self::getParamUnits($unit, 'decimals'));
            $cmd->setConfiguration('minValue', self::getParamUnits($unit, 'minValue'));
            $cmd->setConfiguration('maxValue', self::getParamUnits($unit, 'maxValue'));
            $cmd->setConfiguration('manualGroup', array('value' => '5', 'unit' => 'm'));
            $cmd->setConfiguration('group', 'auto');
            $cmd->setConfiguration('historizeRound', 2);
            $cmd->setTemplate('dashboard', 'core::tile');
            $cmd->setTemplate('mobile', 'core::tile');
            $cmd->setGeneric_type(self::getParamUnits($unit, 'generic'));
            $cmd->save();

            log::add(__CLASS__, 'debug', 'CREATEINFO IO7: ' .json_encode(utils::o2a($cmd)));
            if ($unit == 'Watts' /*ajouter condition via config plugin*/) { // création d'une commande de consommation
                if ($_type == 'input') {
                    $_IO['manual'] = true;
                    $_serie['unit'] = 'Wh';
                } else {
                    $_IO['manual'] = true;
                    $_IO['units'] = 'Wh';
                }
                $this->createCmdInfo($_IO, $_type, $_serie);
            }
        } else {
            //log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Commande déjà existante ', __FILE__) . $cmd->getLogicalId());
            if ($cmd->getConfiguration('serie') != $serie) {
                $cmd->setConfiguration('serie', $serie);
            }
            if ($cmd->getConfiguration('minValue') != self::getParamUnits($unit, 'minValue')) {
                $cmd->setConfiguration('minValue', self::getParamUnits($unit, 'minValue'));
            }
            if ($cmd->getConfiguration('maxValue') != self::getParamUnits($unit, 'maxValue')) {
                $cmd->setConfiguration('maxValue', self::getParamUnits($unit, 'maxValue'));
            }
            if ($cmd->getUnite() != self::getParamUnits($unit, 'unit')) {
                $cmd->setUnite(self::getParamUnits($unit, 'unit'));
            }
            if ($cmd->getLogicalId() != $logicalId) {
                $cmd->setLogicalId($logicalId);
            }
        }
        return $cmd;
    }

    public function getSeries()
    {
        $allCmds = $this->getCmd('info');
        $series = $this->request('query?show=series');
        if (is_array($series) && isset($series['series'])) {
            foreach ($allCmds as $cmd){
                $flag = false;
                foreach ($series['series'] as $serie) {
                    if ($serie['name'] == $cmd->getConfiguration('serie')) {
                        $flag = true;
                    }
                }
                if (!$flag) {
                    log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Suppression de la commande retirée de IotaWatt ', __FILE__) . $cmd->getLogicalId() . ' - ' . __('nom ', __FILE__) . $cmd->getName());
                    $cmd->remove();
                }
            }
            //$this->setConfiguration('series', $server['series']);
            return $series['series'];
        }
        return false;
    }

    public function getSensors()
    {
        $resultat = array();
        $allCmds = $this->getCmd('info');
        //make an array with $logicalId=>url_to_send to
        $cmds = array_filter(array_map(function($cmd) {
            return array($cmd->getLogicalId() => $cmd->getConfiguration('serie') . '.' . strtolower($cmd->getConfiguration('valueType')) . '.d' . $cmd->getConfiguration('round'));
        }, $allCmds));

        $url = implode(',', array_map(function($item) {
            return current($item);
        }, $cmds));

        //$value = $this->getStatus('lastValueUpdate', '') != '' ? $this->getStatus('lastValueUpdate') : 's-' . self::convertCrontabToMinutes(config::byKey('autorefresh', 'iotawatt', '*/1 * * * *')) . 'm';
        $group = $this->getConfiguration('group', 'auto');
        if ($group == 'manual') {
          // $group = implode('', array_map(fn($item) => $item ?? '', $this->getConfiguration('manualGroup', '5m'))); PHP >= 7.4 only
            $group = implode('', array_map(function($item) {
                return $item !== null ? $item : '';
            }, $this->getConfiguration('manualGroup', '5m')));
        }
        $resolution = $this->getConfiguration('resolution', 'high');
        if ($this->getStatus('lastValueUpdate', 0)) {
            $begin = $this->getStatus('lastValueUpdate');
            $timeout = 6;
            $missing = 'zero';
        } else {
            $begin = 'y-4y';
            $timeout = 20;
            $resolution = 'low';
            $group = 'auto';
            $missing = 'skip';
        }

        $params = array(
            'select' => '[time.local.iso,' . $url . ']',
            'begin'  => $begin,
            'end'    => 's',
            'group'  => $group, //{ *auto | all | <n> {s | m | h | d | w | M | y}}
            'format' => 'json', //{ *json | csv}
            'header' => 'yes', //{ *no | yes }
            'missing' => $missing, //{ null | *skip | zero}'
            'resolution' => $resolution, //{ low | high }
            'limit' => 'none' //{n | none | *1000}
        );
        $seriesValues = $this->request('query?' . self::buildQueryString($params), array(), 'GET', $timeout);

        if (is_array($seriesValues) && isset($seriesValues['data'])) {
            foreach ($seriesValues['data'] as $datas){
                $nb = 0;
                $nbUpdated = 0;
                $resultat = array_map(function($elem) use ($datas, &$nb, &$nbUpdated) {
                    $key = key($elem);
                    $value = current($elem);
                    $cmdInfo = $this->getCmd('info', $key);
                    if (is_object($cmdInfo)) {
                        if ($cmdInfo->getConfiguration('valueType') == 'PF') {
                            $cmdInfo->event(floatval($datas[$nb+1]) * 100, str_replace('T', ' ', $datas[0]));
                        } elseif ($cmdInfo->getConfiguration('valueType') == 'Wh') {
                            if ($cmdInfo->getUnite() == 'kWh') {
                                $cmdInfo->event($cmdInfo->execCmd()+($datas[$nb+1]/1000), str_replace('T', ' ', $datas[0]));
                            } else {
                                $cmdInfo->event($cmdInfo->execCmd()+$datas[$nb+1], str_replace('T', ' ', $datas[0])); // penser à demander l'historique depuis le tout début ?
                            }
                        } else {
                            $cmdInfo->event($datas[$nb+1], str_replace('T', ' ', $datas[0]));
                        }
                        $nbUpdated++;
                    }
                    $nb++;
                    return $nbUpdated;
                }, $cmds);
            }
            if (count($resultat) > 0) {
                $this->setStatus('lastValueUpdate', $seriesValues['range'][1]);
            }
        }
    }

    public function request($_path, $_payload = array(), $_method = 'GET', $_timeout = 6)
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début requête url=[', __FILE__) . $this->getUrl() . $_path . '] ; payload=[' . json_encode($_payload) . '] ; method=[' . $_method . ']');
        try {
            $id =  $this->getConfiguration('id', false);
            $password =  $this->getConfiguration('password', false);
            $http = new com_http($this->getUrl() . $_path, $id, $password);
            if ($id && $password) {
                $http->setCURLOPT_HTTPAUTH(CURLAUTH_DIGEST);
            }
            if ($_method == 'POST') {
                $http->setPost(json_encode($_payload));
            }
            if ($_method == 'PUT') {
                $http->setPut($_payload);
            }
            $header = array(
                'Accept: application/json, text/plain, */*',
                'Content-Type: application/json',
            );

            log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début header ', __FILE__) . json_encode($header));
            $http->setHeader($header);

        } catch (Exception $e) {
            log::add(__CLASS__, 'debug', "L." . __LINE__ . " F." . __FUNCTION__ . __(" Erreur d'authentification : ", __FILE__) . $http);
        }
        try {
            $response = $http->exec($_timeout);
            $response = json_decode($response, true);
            if (!isset($response['error'])) {
                log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin (true)', __FILE__) . json_encode($response));
                if ($response == 'IoTaWatt-Login') { // à revoir
                    log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('Vérifiez vos identifiants de connexion ', __FILE__) . json_encode($response));
                    return false;
                }
                return $response;
            }
            preg_match("/Invalid series:\s*(\w+)/", $response['error'], $matches);
            if (count($matches) > 1) {
                //echo $matches[1]; // affiche "inconnu2A"
                //supprimer commande ?
            }
        } catch (Exception $e) {
            log::add(__CLASS__, 'debug', "L." . __LINE__ . " F." . __FUNCTION__ . __(" Erreur de connexion : ", __FILE__) . json_encode(utils::o2a($e)));
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($response));
        return false;
    }

    public static function reloadHistory($_id) {
        $cmd = iotawattCmd::byId($_id);
        if (is_object($cmd)) {
            $result = false;
            $cmd->emptyHistory();
            $eqLogic = $cmd->getEqLogic();
            $cmd->event(0);
            $params = array(
                'select' => '[time.local.iso,' . $cmd->getConfiguration('serie') . '.' . strtolower($cmd->getConfiguration('valueType')) . '.d' . $cmd->getConfiguration('round') . ']',
                'begin'  => 'y-4y',
                'end'    =>  $eqLogic->getStatus('lastValueUpdate', 's'), //si 's', il y aura des valeurs en trop
                'group'  => '2h', //{ *auto | all | <n> {s | m | h | d | w | M | y}}
                'format' => 'json', //{ *json | csv}
                'header' => 'yes', //{ *no | yes }
                'missing' => 'skip', //{ null | *skip | zero}'
                'resolution' => 'high', //{ low | high }
                'limit' => 'none' //{n | none | *1000}
            );
            $seriesValues = $eqLogic->request('query?' . self::buildQueryString($params), array(), 'GET', 20);

            if (is_array($seriesValues) && isset($seriesValues['data'])) {
                foreach ($seriesValues['data'] as $datas) {
                    $oldValue = $cmd->execCmd();
                    $value = floatval($datas[1]);
                    switch ($cmd->getConfiguration('valueType')) {
                        case 'PF':
                            $value = $oldValue + $value * 100;
                            break;
                        case 'Wh':
                            if ($cmd->getUnite() == 'kWh') {
                                $value = $oldValue + $value / 1000;
                            } else {
                                $value = $oldValue + $value;
                            }
                            break;
                        default:
                            $value = $oldValue + $value;
                    }
                    $cmd->event($value, str_replace('T', ' ', $datas[0]));
                    $result = true;
                }
            }
            return $result;
        }
    }

    public function getImage()
    {
        return 'plugins/iotawatt/plugin_info/iotawatt_icon.png';
    }

    public function toHtml($_version = 'dashboard')
    {
        if ($this->getConfiguration('widgetTemplate') != 1) {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $_version = jeedom::versionAlias($_version);

        $replace['#lastDbm_value#'] = $this->getStatus('rssi');
        $replace['#lastCommunication_value#'] = $this->getStatus('lastCommunication');
        $replace['#lastAlive_value#'] = $this->getStatus('lastAlive');
        $replace['#createdAt_value#'] = $this->getStatus('createdAt');

        foreach ($this->getCmd('info', null) as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#cmd_' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
            $replace['#cmd_' . $cmd->getLogicalId() . '_value#'] = $cmd->execCmd();
            $replace['#cmd_' . $cmd->getLogicalId() . '_icon#'] = $cmd->getDisplay('icon', '');
            if ($cmd->getConfiguration('maxValue', '') != '') {
                $replace['#cmd_' . $cmd->getLogicalId() . '_maxValue#'] = $cmd->getConfiguration('maxValue');
            }
            $replace['#cmd_' . $cmd->getLogicalId() . '_unit#'] = $cmd->getUnite();
            $replace['#cmd_' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();
            $replace['#cmd_' . $cmd->getLogicalId() . '_valueDate#'] = $cmd->getValueDate();
        }
        foreach ($this->getCmd('action', null) as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        }
        $html = template_replace($replace, getTemplate('core', $_version, 'iotawatt.template',__CLASS__));
        $html = translate::exec($html, 'plugins/iotawatt/core/template/' . $version . '/iotawatt.tempate.html');
        return $html;
    }
}

class iotawattCmd extends cmd
{
    public static $_widgetPossibility = array('custom' => true);

    public function execute($_options = array())
    {
        $eqLogic = $this->getEqLogic();
        log::add('iotawatt', 'debug', __("Action sur ", __FILE__) . $this->getLogicalId() . __(" avec options ", __FILE__) . json_encode($_options));
        switch ($this->getSubType()) {
            case 'slider':
                $replace['#slider#'] = floatval($_options['slider']);
                break;
            case 'color':
                $replace['#color#'] = $_options['color'];
                break;
            case 'select':
                $replace['#select#'] = $_options['select'];
                break;
            case 'message':
                $replace['#title#'] = $_options['title'];
                $replace['#message#'] = $_options['message'];
                if ($_options['message'] == '' && $_options['title'] == '') {
                  throw new Exception(__('Le message et le sujet ne peuvent pas être vide', __FILE__));
                }
                break;
        }
        $value = str_replace(array_keys($replace),$replace,$this->getConfiguration('updateCmdToValue', ''));

        switch ($this->getLogicalId()) {
            case 'refresh':
                if (count($eqLogic->getCmd('info')) == 0) {
                    $eqLogic->setStatus('lastValueUpdate', 0);
                    //return;
                }
                $eqLogic->getSeries();
                $eqLogic->getSensors();
                break;
            case 'reboot':
                $reboot = $eqLogic->request('command?restart=yes');

                log::add('iotawatt', 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($reboot));
                break;
        }

        ///command?restart=yes
    }
}
