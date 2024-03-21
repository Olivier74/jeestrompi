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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
  */
    ajax::init();
	
	$action = init('action');
  
	if ($action == 'syncDateTime') {
      $eqId = init('id');
      $eqLogic = jeestrompi::byId(init('id'));
      log::add('jeestrompi', 'debug', 'Ajax::syncDateTime eqId : '.$eqId);
      $infosyn = init('infosyn');
      log::add('jeestrompi', 'debug', 'infosyn: '.$infosyn);
      if (!is_object($eqLogic)) {
          throw new Exception(__('Equipement strompi non trouvé : ', __FILE__) . init('eqLogic_id'));
      }

      if($infosyn =='syn'){
      //$return = $eqLogic->syncPlugin($eqId);
      //$object_name = $this->getEqLogic()->getHumanName();     /*$object_id = cmd::byEqLogicId('#[Exterieur][strompi]#')->getId();*/
      //$object_id = $this->getEqLogic_id();
      $parameter = array('eqlogic' => $eqId,'action' => 'date-rpi');
      $eqLogic->sendToDaemon($parameter);
      log::add('jeestrompi', 'debug', 'syncDateTime return: '.$return);
      }
      if($infosyn =='refresh'){  
      $parameter = array('eqlogic' => $eqId,'action' => 'status-rpi');
      $eqLogic->sendToDaemon($parameter);
      log::add('jeestrompi', 'debug', 'syncDateTime return: '.$return);
      }  
      ajax::success($return);
    }elseif($action =='ChangeMode'){
      $eqId = init('id');
      $eqLogic = jeestrompi::byId(init('id'));
      log::add('jeestrompi', 'debug', 'Ajax::ChangeMode eqId : '.$eqId);
      $infosyn = init('infosyn');
      log::add('jeestrompi', 'debug', 'Nouveau mode : '.$infosyn);
      if (!is_object($eqLogic)) {
          throw new Exception(__('Equipement strompi non trouvé : ', __FILE__) . init('eqLogic_id'));
      }
	  
     ajax::success($return);
   	}
    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}