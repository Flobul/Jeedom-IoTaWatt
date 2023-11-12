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
    /**
     * Tableau de possibilités de widgets pour les commandes.
     *
     * @var array
     */
    public static $_widgetPossibility = array('custom' => true, 'custom::layout' => true);
    /**
     * Version du plugin.
     * @var string
     */
    public static $_pluginVersion = '0.92';

    public const HANDLESTATUS          = '/status';
    public const HANDLEVCAL            = '/vcal';
    public const HANDLECOMMAND         = '/command';
    public const PRINTDIRECTORY        = '/list';
    public const HANDLEDELETE          = '/edit';
    public const HANDLECREATE          = '/edit';
    public const HANDLEGETFEEDLIST     = '/feed/list.json';
    public const HANDLEGETFEEDDATA     = '/feed/data.json';
    public const HANDLEGRAPHCREATE     = '/graph/create';
    public const HANDLEGRAPHUPDATE     = '/graph/update';
    public const HANDLEGRAPHDELETE     = '/graph/delete';
    public const HANDLEGRAPHGETALL     = '/graph/getall';
    public const HANDLEGRAPHGETALLPLUS = '/graph/getallplusraphGetallplus';
    public const HANDLEPASSWORDS       = '/auth';
    public const RETURNOK              = '/nullreq';
    public const HANDLEQUERY           = '/query';
    public const HANDLEDSTTEST         = '/DSTtest';
    public const HANDLEUPDATE          = '/update';

    /*     * ***********************Methode statique*************************** */

    /**
     *
     * Compare deux commandes en fonction de leur configuration et de leur type.
     * @param Cmd $cmd1 La première commande à comparer.
     * @param Cmd $cmd2 La deuxième commande à comparer.
     * @return int Retourne -1 si la première commande doit être placée avant la deuxième, 0 si elles sont équivalentes, et 1 si la deuxième commande doit être placée avant la première.
     */
    private static function compareCmds($cmd1, $cmd2) {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : Début');
        $channel1 = str_replace('\/', '/', $cmd1->getConfiguration('channel', 'N/A'));
        $channel2 = str_replace('\/', '/', $cmd2->getConfiguration('channel', 'N/A'));
        $type1 = $cmd1->getConfiguration('type');
        $type2 = $cmd2->getConfiguration('type');
        if ($channel1 !== '' && $channel2 !== '') {
            if ($type1 == 'input' && $type2 == 'input') {
                return $channel1 - $channel2;
            } elseif ($type1 == 'output' && $type2 == 'output') {
                return $channel1 - $channel2;
            } elseif ($type1 == 'input') {
                return -1;
            } elseif ($type2 == 'input') {
                return 1;
            } else {
                return 0;
            }
        } elseif ($cmd1->getType() === 'info' && $cmd2->getType() !== 'info') {
            return -1;
        } elseif ($cmd1->getType() !== 'info' && $cmd2->getType() === 'info') {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Recharge l'historique d'une commande iotawatt en récupérant les valeurs depuis l'IoTaWatt.
     *
     * @param string $_id L'identifiant de la commande iotawatt à recharger.
     * @param string $_begin (Optionnel) La date de début de la période à recharger. Format : 's', 'd', 'w', 'M', 'y' ou une date au format 'Y-m-d H:i:s'. Par défaut : 'y-4y'.
     * @return bool Vrai en cas de succès, faux sinon.
     */
    public static function reloadHistory($_id, $_begin = 'y-4y') {
        $cmd = iotawattCmd::byId($_id);
        if (is_object($cmd)) {
            $result = false;
            $cmd->emptyHistory();
            $eqLogic = $cmd->getEqLogic();
            $i = 0;
            switch ($_begin) {
                case 's':
                  $group = '5s';
                  break;
                case 'd':
                  $group = '30s';
                  break;
                case 'w':
                  $group = '5m';
                  break;
                case 'M':
                  $group = '30m';
                  break;
                case 'y':
                  $group = '6h';
                  break;
                default:
                  $group = '12h';
                  break;
            }
            $params = array(
                'select' => '[time.local.iso,' . $cmd->getConfiguration('serie') . '.' . strtolower($cmd->getConfiguration('valueType')) . '.d' . $cmd->getConfiguration('round') . ']',
                'begin'  => $_begin,
                'end'    => $eqLogic->getStatus('lastValueUpdate', 's'), //si 's', il y aura des valeurs en trop
                'group'  => $group, //{ *auto | all | <n> {s | m | h | d | w | M | y}}
                'format' => 'json', //{ *json | csv}
                'header' => 'yes', //{ *no | yes }
                'missing' => 'skip', //{ null | *skip | zero}'
                'resolution' => 'high', //{ low | high }
                'limit' => 'none' //{n | none | *1000}
            );
            $seriesValues = $eqLogic->request(self::HANDLEQUERY . '?' . self::buildQueryString($params), array(), 'GET', 20);

            if (is_array($seriesValues) && isset($seriesValues['data'])) {
                foreach ($seriesValues['data'] as $datas) {
                    $oldValue = $i==0?0:$cmd->execCmd();
                    $i++;
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

    /**
     * Construit une chaîne de requête en encodant les paramètres avec la RFC 3986.
     *
     * @param array $params Un tableau associatif contenant les paramètres à inclure dans la chaîne de requête.
     * @return string La chaîne de requête générée.
     */
    protected static function buildQueryString(array $params)
    {
        return http_build_query($params, null, '&', PHP_QUERY_RFC3986);
    }

    /**
     * Convertit une expression cron en minutes.
     *
     * @param string $_crontab L'expression cron à convertir.
     * @return int Le nombre de minutes correspondant à l'expression cron donnée.
     */
    public static function convertCrontabToMinutes($_crontab)
    {
        return str_replace('*/', '', explode(' ', $_crontab)[0]);
    }

    /**
     * Met à jour les valeurs et les capteurs des équipements de type "iotawatt".
     *
     * Cette fonction met à jour les valeurs et les capteurs de tous les équipements de type "iotawatt" en utilisant
     * l'expression cron stockée dans la configuration sous la clé "autorefresh". Si la clé "autorefresh" est vide,
     * cette fonction ne fait rien. Si l'expression cron n'est pas valide, cette fonction logge une erreur.
     */
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

    /**
     * Méthode exécutée quotidiennement par le système de cron pour récupérer les données de la journée des équipements de type iotawatt.
     * Les équipements doivent avoir l'attribut "autorefresh" défini avec une expression cron valide pour être pris en compte.
     * Cette méthode récupère les données de consommation de la journée pour chaque équipement iotawatt et met à jour leur statut en conséquence.
     * @return void
     */
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

    /**
     * Renvoie les informations sur le démon iotawattd.
     *
     * @return array Tableau associatif contenant les informations sur le démon (log, state, launchable)
     */
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

    /**
     * Lance le service iotawatt
     *
     * @param bool $_debug Active le mode débogage
     * @throws Exception si la configuration est incorrecte
     * @return bool Vrai si le démon a été lancé, faux sinon
     */
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

    /**
     * Arrête le service iotawatt.
     *
     * @return bool Retourne vrai si le démon a été arrêté avec succès, faux sinon.
     * @throws Exception Lève une exception si la configuration est invalide.
     */
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

    /**
     * Récupère l'état de santé du processus iotawatt.
     *
     * @return array Tableau associatif contenant les informations de santé :
     *               - 'test' : Chaîne de caractères décrivant le test effectué.
     *               - 'result' : Résultat du test.
     *               - 'advice' : Conseil pour corriger le test.
     *               - 'state' : État de santé du processus (true = en bonne santé, false = en mauvaise santé).
     */
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

    /*     * ***********************Methode d'instance*************************** */

    /**
     * Retourne l'URL de l'objet iotawatt courant, en fonction de ses paramètres de configuration.
     *
     * @return string L'URL de l'objet iotawatt.
     */
	public function getUrl() {
        $id = $this->getConfiguration('id', false);
        $password = $this->getConfiguration('password', false);
        $url = 'http://' . ($id && $password ? "$id:$password@" : '') . ($this->getConfiguration('ip') ?: 'iotawatt.local');
        return $url;
	}

    /**
     * Cette méthode est appelée juste avant l'insertion d'un nouvel objet dans la base de données.
     * Elle permet de définir les valeurs par défaut pour certaines propriétés.
     *
     * @return void
     */
    public function preInsert()
    {
        $this->setIsEnable(1);
        $this->setIsVisible(1);
    }

    /**
     * Exécute les opérations nécessaires avant la mise à jour de l'objet iotawatt.
     *
     * Cette méthode met à jour l'état de l'objet en récupérant les dernières informations de l'iotawatt, et
     * ajoute deux commandes si elles n'existent pas encore : une commande de redémarrage et une commande de rafraîchissement.
     * Ces commandes permettent de redémarrer l'iotawatt et de rafraîchir ses informations, respectivement.
     */
    public function preUpdate()
    {
        $this->getSeries();
        $this->updateStatus($this->getIotaWattStatus(array('passwords' => true, 'stats' => true, 'wifi' => true, 'inputs' => true, 'outputs' => true, 'device' => true, 'stats' => true)));
        $rebootCmd = $this->getCmd('action', 'reboot');
        if (!is_object($rebootCmd)) {
            $rebootCmd = new iotawattCmd();
            $rebootCmd->setName(__('Redémarrer', __FILE__));
            $rebootCmd->setLogicalId('reboot');
            $rebootCmd->setOrder(9999997);
            $rebootCmd->setEqLogic_id($this->getId());
            $rebootCmd->setType('action');
            $rebootCmd->setSubType('other');
            $rebootCmd->setDisplay('showOnPanel', 0);
            $rebootCmd->save();
        }

        $refreshCmd = $this->getCmd('action', 'refresh');
        if (!is_object($refreshCmd)) {
            $refreshCmd = new iotawattCmd();
            $refreshCmd->setName(__('Rafraîchir', __FILE__));
            $refreshCmd->setLogicalId('refresh');
            $refreshCmd->setOrder(9999998);
            $refreshCmd->setEqLogic_id($this->getId());
            $refreshCmd->setType('action');
            $refreshCmd->setSubType('other');
            $refreshCmd->setDisplay('showOnPanel', 0);
            $refreshCmd->save();
        }

        $deletelogCmd = $this->getCmd('action', 'deletelog');
        if (!is_object($deletelogCmd)) {
            $deletelogCmd = new iotawattCmd();
            $deletelogCmd->setName(__('Supprimer les journaux', __FILE__));
            $deletelogCmd->setLogicalId('deletelog');
            $deletelogCmd->setOrder(9999999);
            $deletelogCmd->setEqLogic_id($this->getId());
            $deletelogCmd->setType('action');
            $deletelogCmd->setSubType('select');
            $deletelogCmd->setConfiguration('listValue', 'current|Temps réel;history|Historique;both|Les deux');
            $deletelogCmd->setConfiguration('updateCmdToValue', '#select#');
            $deletelogCmd->setConfiguration('actionConfirm', 1);
            $deletelogCmd->setDisplay('showOnPanel', 0);
            $deletelogCmd->save();
        }
    }

    /**
     * Effectue une action après une requête Ajax.
     *
     * Si la configuration 'reOrderCmd' est définie à 'true', réorganise les commandes en fonction de leur ordre.
     *
     * @return void
     */
    public function postAjax() {
        if ($this->getConfiguration('reOrderCmd', false)) {
            $this->setOrderCmd();
        }
    }

    /**
     * Déchiffre le mot de passe stocké dans la configuration de l'objet en utilisant l'outil de chiffrement 'utils::decrypt'.
     *
     * Met à jour la configuration avec le mot de passe déchiffré.
     *
     * @return void
     */
    public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

    /**
     * Chiffre le mot de passe stocké dans la configuration de l'objet en utilisant l'outil de chiffrement 'utils::encrypt'.
     *
     * Met à jour la configuration avec le mot de passe chiffré.
     *
     * @return void
     */
	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

    /**
     * Récupère les informations de l'unité de mesure d'un paramètre donné.
     *
     * @param string $_unit L'unité de mesure recherchée.
     * @param string $_param (optionnel) Le paramètre de l'unité de mesure à récupérer. Par défaut, retourne tous les paramètres.
     * @return array|null Retourne un tableau contenant les informations de l'unité de mesure si elle est trouvée, sinon null.
     *                     Si le paramètre $_param est omis, retourne un tableau contenant tous les paramètres de l'unité de mesure.
     *                     Le tableau retourné est structuré comme suit :
     *                         'name'     => Le nom de l'unité de mesure.
     *                         'unit'     => Le symbole de l'unité de mesure.
     *                         'decimals' => Le nombre de décimales à afficher pour cette unité de mesure.
     *                         'minValue' => La valeur minimale autorisée pour cette unité de mesure.
     *                         'maxValue' => La valeur maximale autorisée pour cette unité de mesure.
     *                         'generic'  => Le nom générique de l'unité de mesure.
     */
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
        return isset($array[$_unit]) ? ($_param == 'all' ? $array[$_unit] : (isset($array[$_unit][$_param]) ? $array[$_unit][$_param] : null)) : null;
    }

    /**
     * Récupère le statut d'IoTaWatt pour les paramètres spécifiés dans le tableau $_param.
     *
     * Si le paramètre $_param est un tableau, il sera converti en chaîne de requête et ajouté à l'URL de requête.
     *
     * Retourne le tableau du statut si la requête a réussi, false sinon.
     *
     * @param mixed $_param Le tableau des paramètres ou une chaîne de requête.
     * @return mixed Le tableau du statut si la requête a réussi, false sinon.
     */
    public function getIotaWattStatus($_param)
    {
        if (is_array($_param)) {
            $_param = self::buildQueryString($_param);
        }
        $status = $this->request(self::HANDLESTATUS . '?' . $_param);
        if (is_array($status)) {
            return $status;
        }
        return false;
    }

    /**
     * Met à jour le statut de l'appareil.
     *
     * @param array $_status Le tableau associatif contenant les informations de statut à mettre à jour.
     *
     * @return bool False si $_status n'est pas un tableau, sinon rien.
     */
    public function updateStatus($_status)
    {
        if (!is_array($_status))  return false;
        if (isset($_status['device'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' device=' . json_encode($_status['device']));
            $this->setConfiguration('name', $_status['device']['name']);
            $this->setConfiguration('timediff', $_status['device']['timediff']);
            $this->setConfiguration('update', $_status['device']['update']);
        }
        if (isset($_status['stats'])) {
            $dataDate = date('Y-m-d H:i:s', $_status['stats']['currenttime']);
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' stats=' . json_encode($_status['stats']));
            $this->setConfiguration('lastUpdateTime', date('Y-m-d H:i:s', $_status['stats']['currenttime']));
            $this->setConfiguration('startTime', date('Y-m-d H:i:s', $_status['stats']['starttime']));
            $this->setConfiguration('runSeconds', $_status['stats']['runseconds']);
            $this->setConfiguration('firmwareVersion', $_status['stats']['version']);
            $this->setStatus('lowbat', $_status['stats']['lowbat']);
        }
        if (isset($_status['wifi'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' wifi=' . json_encode($_status['wifi']));
            $this->setConfiguration('mac', $_status['wifi']['mac']);
            $this->setConfiguration('SSID', $_status['wifi']['SSID']);
            $this->setStatus('RSSI', $_status['wifi']['RSSI']);
            $this->setStatus('connecttime', $_status['wifi']['connecttime']);
        }
        if (isset($_status['passwords'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' passwords=' . json_encode($_status['passwords']));
            $this->setConfiguration('admin', $_status['passwords']['admin']);
            $this->setConfiguration('user', $_status['passwords']['user']);
            $this->setConfiguration('localAccess', $_status['passwords']['localAccess']);
        }
        if (isset($_status['state'])) {
        }
        if (isset($_status['datalogs'])) {
        }
        if (isset($_status['influx1'])) {
        }
        if (isset($_status['influx2'])) {
        }
        if (isset($_status['emoncms'])) {
        }
        if (isset($_status['pvoutput'])) {
        }
        if (isset($_status['inputs'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' time= ' . $dataDate . ' inputs=' . json_encode($_status['inputs']));
            $dataDate = $dataDate ?? date('Y-m-d H:i:s');
            $series = $this->getSeries();
            $this->setConfiguration('nbInputs', count($_status['inputs']));
            for ($i = 0; $i < count($_status['inputs']); $i++) {
                $cmdInput = $this->createCmdInfo($_status['inputs'][$i], 'input', $series[$i]);//originaly for Watts
                if (is_object($cmdInput)) {
                    $unit = $cmdInput->getConfiguration('valueType') === 'Volts' ? 'Vrms' : $cmdInput->getConfiguration('valueType');
                    if ($cmdInput->execCmd() !== $cmdInput->formatValue(floatval($_status['inputs'][$i][$unit]))) {
                        $cmdInput->event(round(floatval($_status['inputs'][$i][$unit]),2), $dataDate);
                    }
                }
                if (isset($_status['inputs'][$i]['Hz'])) {
                    $cmdHz = $this->getCmd('info', 'input_' . $_status['inputs'][$i]['channel'] . '_Hz');
                    if (is_object($cmdHz)) {
                        $cmdHz->event(round(floatval($_status['inputs'][$i]['Hz']),2), $dataDate);
                    }
                }
                if (isset($_status['inputs'][$i]['Pf'])) {
                    $cmdPf = $this->getCmd('info', 'input_' . $_status['inputs'][$i]['channel'] . '_PF');
                    if (is_object($cmdPf)) {
                        $cmdPf->event(round(floatval($_status['inputs'][$i]['Pf']),2), $dataDate);
                    }
                }
            }
        }
        if (isset($_status['outputs'])) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' time= ' . $dataDate . ' outputs=' . json_encode($_status['outputs']) . $this->getConfiguration('nbOutputs'));
            $this->setConfiguration('nbOutputs', count($_status['outputs']));
            for ($i = 0; $i < count($_status['outputs']); $i++) {
                $cmdOutput = $this->createCmdInfo($_status['outputs'][$i], 'output');
                if (is_object($cmdOutput)) {
                    $unit = $cmdOutput->getConfiguration('valueType') === 'Volts' ? 'Vrms' : $cmdOutput->getConfiguration('valueType');
                    if ($_status['outputs'][$i]['units'] == $unit) {
                        if ($cmdOutput->execCmd() !== $cmdOutput->formatValue(floatval($_status['outputs'][$i]['value']))) {
                            $cmdOutput->event(round(floatval($_status['outputs'][$i]['value']),2), $dataDate);
                        }
                    }
                }
            }
        }
        //$this->save();
    }

    /**
     *
     * Créer une commande d'information pour un équipement iotawatt
     * @param array $_IO Informations sur l'entrée/sortie de l'équipement
     * @param string $_type Type d'entrée/sortie ('input' ou 'output')
     * @param array $_serie Informations sur la série de données associée à l'entrée/sortie (optionnel)
     * @return iotawattCmd La commande d'information créée ou mise à jour
     */
    public function createCmdInfo($_IO, $_type, $_serie = array())
    {

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
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Création de la commande ', __FILE__) . ' type=' . $_type . ' IO=' . json_encode($_IO) . ' serie=' . json_encode($_serie));
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
            $showOnPanel = 0;
            if ($_type == 'input') {
                $showOnPanel = 1;
            }
            $cmd->setDisplay('showOnPanel', $showOnPanel);
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
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Commande déjà existante ', __FILE__) . $cmd->getLogicalId() . ' type=' . $_type . ' IO=' . json_encode($_IO) . ' serie=' . json_encode($_serie));
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

    /**
     *
     * Récupère la liste des séries depuis le serveur IotaWatt.
     * @return array|false la liste des séries si la requête a réussi, false sinon.
     */
    public function getSeries()
    {
        $allCmds = $this->getCmd('info');
        $series = $this->request(self::HANDLEQUERY . '?show=series');
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
            return $series['series'];
        }
        return false;
    }

    /**
     *
     * Récupère les informations sur les capteurs.
     * @return array|false Tableau contenant les informations sur les capteurs, ou false en cas d'erreur.
     */
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

        $group = $this->getConfiguration('group', 'auto');
        if ($group == 'manual') {
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
            'select' => '[time.utc.unix,' . $url . ']',
            'begin'  => $begin,
            'end'    => 's',
            'group'  => $group, //{ *auto | all | <n> {s | m | h | d | w | M | y}}
            'format' => 'json', //{ *json | csv}
            'header' => 'yes', //{ *no | yes }
            'missing' => $missing, //{ null | *skip | zero}'
            'resolution' => $resolution, //{ low | high }
            'limit' => 'none' //{n | none | *1000}
        );
        $seriesValues = $this->request(self::HANDLEQUERY . '?' . self::buildQueryString($params), array(), 'GET', $timeout);

        if (is_array($seriesValues) && isset($seriesValues['data'])) {
            foreach ($seriesValues['data'] as $datas){
                $nb = 0;
                $nbUpdated = 0;
                $datasDate = date('Y-m-d H:i:s', $datas[0]);
                $resultat = array_map(function($elem) use ($datas, &$nb, &$nbUpdated, $datasDate) {
                    $key = key($elem);
                    $value = current($elem);
                    $cmdInfo = $this->getCmd('info', $key);
                    if (is_object($cmdInfo)) {
                        if ($cmdInfo->getConfiguration('valueType') == 'PF') {
                            $cmdInfo->event(floatval($datas[$nb+1]) * 100, $datasDate);
                        } elseif ($cmdInfo->getConfiguration('valueType') == 'Wh') {
                            if ($cmdInfo->getUnite() == 'kWh') {
                                $cmdInfo->event($cmdInfo->execCmd()+($datas[$nb+1]/1000), $datasDate);
                            } else {
                                $cmdInfo->event($cmdInfo->execCmd()+$datas[$nb+1], $datasDate);
                            }
                        } else {
                            $cmdInfo->event($datas[$nb+1], $datasDate);
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

    /**
     * Effectue une requête HTTP vers l'URL spécifiée avec les paramètres donnés.
     *
     * @param string $_path le chemin de l'URL.
     * @param array $_payload un tableau contenant les données à envoyer (par défaut vide).
     * @param string $_method la méthode HTTP à utiliser (par défaut 'GET').
     * @param int $_timeout le temps d'attente maximal en secondes pour la réponse (par défaut 6).
     *
     * @return mixed les données retournées par la requête si elle a réussi, ou false sinon.
     */
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
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($response));
        } catch (Exception $e) {
            log::add(__CLASS__, 'debug', "L." . __LINE__ . " F." . __FUNCTION__ . __(" Erreur de connexion : ", __FILE__) . json_encode(utils::o2a($e)));
        }
        return false;
    }


    /**
     *
     * Trie les commandes et leur assigne un ordre en fonction de leur type (info ou action)
     * Les commandes non triées sont renommées à la fin
     * @return void
     */
    public function setOrderCmd() {

        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : Début');
        $cmdsI = $this->getCmd('info');
        $cmdsA = $this->getCmd('action');

        usort($cmdsI, array($this, 'compareCmds'));

        // Renommer les commandes dans l'ordre trié
        $i = 0;
        foreach ($cmdsI as $cmdInfo) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' : CommandeI1 : ' . $i . ' logicalId ' . $cmdInfo->getLogicalId());
            $cmdInfo->setOrder($i);
            $cmdInfo->save();
            $i++;
        }
        foreach ($cmdsI as $cmdInfo) {
            if ($cmdInfo->getOrder() === null) { // Les commandes non triées sont renommées à la fin
                log::add(__CLASS__, 'debug', __FUNCTION__ . ' : CommandeI2 : ' . $i . ' logicalId ' . $cmdInfo->getLogicalId());
                $cmdInfo->setOrder($i);
                $cmdInfo->save();
                $i++;
            }
        }
        foreach ($cmdsA as $cmdAction) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' : CommandeA : ' . $i . ' logicalId ' . $cmdAction->getLogicalId());
            $cmdAction->setOrder($i);
            $cmdAction->save();
            $i++;
        }
    }

    /**
     * Renvoie le chemin de l'image de l'icône du plugin.
     *
     * @return string Le chemin de l'image de l'icône du plugin.
     */
    public function getImage()
    {
        return 'plugins/iotawatt/plugin_info/iotawatt_icon.png';
    }

}

class iotawattCmd extends cmd
{
    /**
     * Tableau de possibilités de widgets pour les commandes.
     *
     * @var array
     */
    public static $_widgetPossibility = array('custom' => true);

    /**
     * Exécute la commande en fonction de son sous-type et de ses options.
     * @param array $_options Tableau associatif d'options pour la commande.
     * @throws Exception si le message et le sujet sont vides pour le sous-type 'message'.
     */
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
                }
                $eqLogic->getSeries();
                $eqLogic->getSensors();
                break;
            case 'reboot':
                $reboot = $eqLogic->request(iotawatt::HANDLECOMMAND . '?restart=yes');

                log::add('iotawatt', 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($reboot));
                break;
            case 'deletelog':
                if (!in_array(array('history','current','both'))) return false;
                $deletelog = $eqLogic->request(iotawatt::HANDLECOMMAND . '?deletelog=' . $value);
                log::add('iotawatt', 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($deletelog));
                break;
        }
        return true;
    }
}
