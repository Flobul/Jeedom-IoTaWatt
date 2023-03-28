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

class iotawatt_display extends eqLogic
{
    /**
     * Affiche une carte d'action personnalisée avec une icône et un texte.
     *
     * @param string $action_name Le nom de l'action à afficher.
     * @param string $fa_icon L'icône Font Awesome à afficher.
     * @param string $attr (Optionnel) Les attributs HTML supplémentaires à ajouter à la carte d'action.
     * @param string $class (Optionnel) La classe CSS supplémentaire à ajouter à la carte d'action.
     *
     * @return void
     */
    public static function displayActionCard($action_name, $fa_icon, $attr = '', $class = '') {
        $actionCard = '<div class="eqLogicDisplayAction eqLogicAction cursor ' . $class . '" ';
        if ($attr != '') $actionCard .= $attr;
        $actionCard .= '>';
        $actionCard .= '    <i class="fas ' . $fa_icon . '"></i><br>';
        $actionCard .= '    <span>' . $action_name . '</span>';
        $actionCard .= '</div>';
        echo $actionCard;
    }

    /**
     * Affiche un bouton d'action avec un logo, un texte et un attribut data-action.
     *
     * @param string $class La classe CSS à ajouter au bouton d'action.
     * @param string $action L'attribut data-action à ajouter au bouton d'action.
     * @param string $title Le titre à afficher lorsque l'utilisateur survole le bouton d'action.
     * @param string $logo L'icône Font Awesome à afficher à gauche du texte.
     * @param string $text Le texte à afficher à droite de l'icône.
     * @param bool $display (Optionnel) Définit si le bouton doit être affiché ou masqué par défaut.
     *
     * @return void
     */
    public static function displayBtnAction($class, $action, $title, $logo, $text, $display = FALSE) {
        $btn = '<a class="eqLogicAction btn btn-sm ' . $class . '"';
        $btn .= '    data-action="' . $action . '"';
        $btn .= '    title="' . $title . '"';
        if ($display) $btn .= '    style="display:none"';
        $btn .= '>';
        $btn .= '  <i class="fas ' . $logo . '"></i> ';
        $btn .= $text;
        $btn .= '</a>';
        echo $btn;
    }

    /**
     * Affiche une section d'aperçu de la liste des équipements
     *
     * @param array $eqLogics Liste des équipements à afficher
     * @return void
     */
    public static function displayEqLogicThumbnailContainer($eqLogics) {
        echo '<div class="panel panel-default">';
        echo '    <h3 class="panel-title">';
        echo '        <a class="accordion-toggle" data-toggle="collapse" data-parent="" href="#iotawattBox"><i class=""></i> </a>';
        echo '    </h3>';
        echo '    <div id="iotawatti_'.$_type.'" class="panel-collapse collapse in">';
        echo '        <div class="eqLogicThumbnailContainer">';
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
            echo '            <div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
            echo '                <img src="' . $eqLogic->getImage() . '"/>';
            echo '                <br>';
            echo '                <span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '            </div>';
        }
        echo '            </div>';
        echo '        </div>';
        echo '    </div>';
    }

    /**
     * Affiche un groupe de formulaire pour une configuration d'équipement
     *
     * @param string $datal2key Clé de la configuration dans le deuxième niveau de l'objet JSON
     * @param string|null $type Texte à afficher en tant que titre du groupe de formulaire
     * @param string $l1key Clé de la configuration dans le premier niveau de l'objet JSON
     * @param string $unit Unité de mesure, si applicable
     * @return void
     */
    public static function displayFormGroupEqLogic($datal2key, $type = null, $l1key = 'configuration', $unit = '')
    {
        $div = '<div class="form-group">';
        $div .= '	<div class="col-sm-12">';
        $div .= '		<label class="col-sm-4 control-label">' . $type . '</label>';
        $div .= '		<span class="eqLogicAttr label label-info" data-l1key="' . $l1key . '" data-l2key="' . $datal2key . '" data-unit="' . $unit . '">';
        $div .= '		</span>';
        $div .= '	</div>';
        $div .= '</div>';
        echo $div;
    }
}
