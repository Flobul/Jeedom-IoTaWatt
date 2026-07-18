<?php

/**
 * Classe pour gérer les données de puissance et consommation des IoTaWatt
 */
class IotawattPowerData
{
    private $eqLogics;
    private $historyCalculTendanceThresholddMax;
    private $historyCalculTendanceThresholddMin;
    private $startHist;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->eqLogics = iotawatt::byType('iotawatt');
        $this->historyCalculTendanceThresholddMax = config::byKey('historyCalculTendanceThresholddMax');
        $this->historyCalculTendanceThresholddMin = config::byKey('historyCalculTendanceThresholddMin');
        $this->startHist = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' -' . config::byKey('historyCalculTendance') . ' hour'));
    }

    /**
     * Récupère les logos de tendance
     * @return array
     */
    public static function getTendanceLogos()
    {
        return [
            'arrowUp'   => '<i class="fas fa-arrow-up icon_red"></i>',
            'arrowDown' => '<i class="fas fa-arrow-down icon_green"></i>',
            'minus'     => '<i class="fas fa-minus icon_blue"></i>'
        ];
    }

    /**
     * Calcule la couleur basée sur le pourcentage
     * @param float $pourcent
     * @return string
     */
    public static function getColorForPourcentage($pourcent)
    {
        if (is_nan($pourcent)) {
            return "hsl(0, 0%, 50%)";
        }

        $light = 30;
        $currentTimestamp = time();
        $startOfDayTimestamp = strtotime('today', $currentTimestamp);
        $endOfDayTimestamp = strtotime('tomorrow', $startOfDayTimestamp) - 1;
        $percentOfDay = (($currentTimestamp - $startOfDayTimestamp) / ($endOfDayTimestamp - $startOfDayTimestamp)) * 100;
        $percentOfDay = max(0, min(100, $percentOfDay));
        $pourcent = max(-200, min(400, $pourcent));

        if ($pourcent < 0) {
            $hue = 120 - $percentOfDay * 1.2;
        } else {
            if ($pourcent > 200) {
                $hue = 0;
                $light -= $pourcent / 10;
            } else {
                $hue = 0 + $percentOfDay * 1.2;
            }
        }
        $hue = max(0, min(120, 120 - $hue));
        return "hsl(" . $hue . ", 100%, " . $light . "%)";
    }

    /**
     * Détermine le logo de tendance à afficher
     * @param float $tendance
     * @return string
     */
    private function getTendanceLogo($tendance)
    {
        $logos = self::getTendanceLogos();
        if ($tendance > $this->historyCalculTendanceThresholddMax) {
            return $logos['arrowUp'];
        } elseif ($tendance < $this->historyCalculTendanceThresholddMin) {
            return $logos['arrowDown'];
        }
        return $logos['minus'];
    }

    /**
     * Traite les données d'une commande
     * @param cmd $cmd
     * @param string $type 'conso' ou 'power'
     * @return array
     */
    private function processCmdData($cmd, $type)
    {
        $valueInfo = cmd::autoValueArray($cmd->execCmd(), 2, $cmd->getUnite());
        $tendance = $cmd->getTendance($this->startHist, date('Y-m-d H:i:s'));

        $data = [
            $type => $valueInfo[0],
            $type . 'Unit' => $valueInfo[1],
            $type . 'OldUnit' => $cmd->getUnite(),
            $type . 'Id' => $cmd->getId(),
            $type . 'Name' => $cmd->getName(),
            $type . 'CollectDate' => $cmd->getCollectDate(),
            $type . 'ValueDate' => $cmd->getValueDate(),
            $type . 'Tendance' => $tendance,
            'logoTendance' . ucfirst($type) => $this->getTendanceLogo($tendance),
            $type . 'Icon' => $cmd->getDisplay('icon', '')
        ];

        if ($type === 'conso') {
            $data['consoStats'] = $cmd->getStatistique(
                date('Y-m-d 00:00:00', strtotime('- 1 day')),
                date('Y-m-d 00:00:00')
            );
        }

        return $data;
    }

    /**
     * Collecte toutes les données des IoTaWatt
     * @return array
     */
    public function collectData($_addLinky = true)
    {
        $cmdArray = [];
        $totalPower = 0;

        foreach ($this->eqLogics as $eqLogic) {
            if (!$eqLogic->getIsEnable()) {
                continue;
            }

            $eqData = $this->processEqLogic($eqLogic, $totalPower);
            $cmdArray = array_merge($cmdArray, $eqData);
        }

        if ($_addLinky) {
            // Ajouter les données Linky
            $linkyData = $this->processLinkyData();
            if (!empty($linkyData)) {
                $cmdArray['000000::Linky'] = $linkyData;
            }
        }

        // Ajouter le total
        if (config::byKey('sumTotal', 'iotawatt', true)) {
            $cmdArray['000000::Somme'] = [
                'consoName' => '<strong>{{TOTAL IoTaWatt}}</strong>',
                'power' => round($totalPower, 2),
                'powerUnit' => 'W',
                'id' => '',
                'isTotal' => true
            ];
        }

        return $cmdArray;
    }

    /**
     * Traite un équipement IoTaWatt
     * @param eqLogic $eqLogic
     * @param float &$totalPower
     * @return array
     */
    private function processEqLogic($eqLogic, &$totalPower)
    {
        $result = [];

        foreach ($eqLogic->getCmd('info') as $cmd) {
            if ($cmd->getConfiguration('type') !== 'input' || !$cmd->getDisplay('showOnPanel')) {
                continue;
            }

            $key = $eqLogic->getId() . '::' . $cmd->getConfiguration('serie');
            
            if (!isset($result[$key])) {
                $result[$key] = [
                    'id' => $eqLogic->getId(),
                    'eqName' => $eqLogic->getConfiguration('name'),
                    'channel' => $cmd->getConfiguration('channel'),
                    'eqLink' => '<a href="' . $eqLogic->getLinkToConfiguration() . 
                               '" class="btn btn-xs btn-primary">' . 
                               $eqLogic->getHumanName(true, false, true) . '</a><br/>',
                    'isLinky' => 0
                ];
            }

            if ($cmd->getConfiguration('totalConsumption', false)) {
                $result[$key] = array_merge($result[$key], $this->processCmdData($cmd, 'conso'));
            } else {
                $result[$key] = array_merge($result[$key], $this->processCmdData($cmd, 'power'));
                $totalPower += $result[$key]['power'];
            }
        }

        return $result;
    }

    /**
     * Traite les données du compteur Linky
     * @return array
     */
    private function processLinkyData()
    {
        $idPowerLinky = str_replace('#', '', config::byKey('powerLinky', 'iotawatt'));
        $idLinky = str_replace('#', '', config::byKey('linky', 'iotawatt'));

        if (empty($idPowerLinky) && empty($idLinky)) {
            return [];
        }

        $data = ['isLinky' => 1];

        // Traiter la puissance Linky
        if (!empty($idPowerLinky)) {
            $cmdPowerLinky = cmd::byId($idPowerLinky);
            if (is_object($cmdPowerLinky)) {
                $data = array_merge($data, $this->processCmdData($cmdPowerLinky, 'power'));
                
                if (is_object($eqLinky = $cmdPowerLinky->getEqLogic())) {
                    $data['id'] = $eqLinky->getId();
                    $data['eqName'] = $eqLinky->getConfiguration('name');
                    $data['eqLink'] = '<a href="' . $eqLinky->getLinkToConfiguration() . 
                                     '" class="btn btn-xs btn-primary">' . 
                                     $eqLinky->getHumanName(true, false, true) . '</a><br/>';
                }
            }
        }

        // Traiter la consommation Linky
        if (!empty($idLinky)) {
            $cmdConsoLinky = cmd::byId($idLinky);
            if (is_object($cmdConsoLinky)) {
                $data = array_merge($data, $this->processCmdData($cmdConsoLinky, 'conso'));
                $data['consoName'] = '<strong>{{Compteur Linky}}</strong>';
            }
        }

        return $data;
    }

    /**
     * Calcule les statistiques de consommation
     * @param array $data
     * @return array
     */
    public static function calculateConsoStats($data)
    {
        if (empty($data['consoStats']) || !isset($data['conso'])) {
            return [
                'yesterday' => 0,
                'today' => 0,
                'percentage' => 0
            ];
        }

        // Consommation d'hier (de 0h à 24h hier)
        $consoYesterday = $data['consoStats']['max'] - $data['consoStats']['min'];
        
        // Récupérer les statistiques du jour en cours (de 0h aujourd'hui à maintenant)
        $cmd = cmd::byId($data['consoId']);
        if (!is_object($cmd)) {
            return [
                'yesterday' => $consoYesterday,
                'today' => 0,
                'percentage' => 0
            ];
        }
        
        $todayStats = $cmd->getStatistique(
            date('Y-m-d 00:00:00'),
            date('Y-m-d H:i:s')
        );
        
        // Consommation du jour = max aujourd'hui - min aujourd'hui
        $consoToday = 0;
        if (!empty($todayStats) && isset($todayStats['max']) && isset($todayStats['min'])) {
            $consoToday = $todayStats['max'] - $todayStats['min'];
        }
        
        // Calcul du pourcentage par rapport à hier
        $consoPourcent = 0;
        if ($consoYesterday > 0) {
            $consoPourcent = round((100 * $consoToday / $consoYesterday) - 100, 2);
        }

        return [
            'yesterday' => $consoYesterday,
            'today' => $consoToday,
            'percentage' => $consoPourcent
        ];
    }
}
