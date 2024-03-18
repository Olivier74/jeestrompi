<?php
/*log::add('jeestrompi', 'debug', 'message received from daemon');*/
/*try {*/
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

    if (!jeedom::apiAccess(init('apikey'), 'jeestrompi')) { //remplacez template par l'id de votre plugin
        echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
        die();
    }
    if (init('test') != '') {
        echo 'OK';
        die();
    }
    $result = json_decode(file_get_contents("php://input"), true);
    if (!is_array($result)) {
        die();
    }

/*	$plugin = plugin::byId('jeestrompi');
	sendVarToJS('eqType', $plugin->getId());
	$eqLogics = eqLogic::byType($plugin->getId());
  
	/*$list_equipements = eqLogic::byType('jeestrompi');
	$i = 1;
    foreach ( $list_equipements as $eq ) {
      $eq_name = $eq->getHumanName();
      log::add('jeestrompi', 'debug', 'equ '.$eq_name);
      $i++ ;
    } */

  	/*$ListeCommandes = cmd::byEqLogicId('[Exterieur][strompi]'->getId());*/
	/*$ListeCommandes = eqLogic::byString('[Exterieur][strompi]')->getCmd();
    foreach($ListeCommandes as $commandes)
    {
       /*$commandes->event($pluie[$commandes->getName()-1]);*/
/*       log::add('jeestrompi', 'debug', 'commande : '.$commandes);
    }
*/
	
	$idEqLogic=$result['eqlogic'];
	$eq=eqLogic::byid($idEqLogic);
    if (is_object($eq)) {
     log::add('jeestrompi', 'debug', 'Equipment : ' . $eq->getName() . '('.$eq->getHumanName().')');
      $cmds=$eq->getCmd('info',null, true,true);
      if (sizeof($cmds) > 0) {
        foreach($cmds as $cmd) {
            /*log::add('jeestrompi', 'debug', ' - cmd : ' . $cmd->getName() . ', value -> '. $cmd->execCmd().'  eqlogic : '.$cmd->getId());*/
          	if ($cmd->getName() == 'Strompi Mode') {
              $Strompi_mode_object_id = $cmd->getId();
			} elseif  ($cmd->getName() == 'Heure') {
              $Strompi_DateTime_object_id = $cmd->getId();
			} elseif  ($cmd->getName() == 'Strompi Output Mode') {
              $Strompi_Output__Mode_object_id = $cmd->getId();
            } elseif  ($cmd->getName() == 'Strompi Output Voltage') {
              $Strompi_Output_Voltage_object_id = $cmd->getId();
            } elseif  ($cmd->getName() == 'Strompi Wide') {
              $Strompi_Wide_Input_object_id = $cmd->getId();
            } elseif  ($cmd->getName() == 'Strompi USB') {
              $Strompi_USB_Input_object_id = $cmd->getId();
            } elseif  ($cmd->getName() == 'Strompi LifePo4') {
              $Strompi_LifePo4_Input_object_id = $cmd->getId();
            } elseif  ($cmd->getName() == 'Strompi LifePo4Charge') {
              $Strompi_LifePo4Charge_Input_object_id = $cmd->getId();
            } else {
              
            }
        }
      }
    }
	/*log::add('jeestrompi', 'debug', ' - cmd : ' $eq->searchByString*/
    if (isset($result['StromPi-DateTimeOutput'])) {
      /*$eqLogic = eqLogic::byType('strompimode');*/
      log::add('jeestrompi', 'debug', 'receive daemon StromPi-DateTimeOutput=' .$result['StromPi-DateTimeOutput']);
      log::add('jeestrompi', 'debug', 'receive daemon eqlogic ='.$eqLogic[0]);
      cmd::byId($Strompi_DateTime_object_id)->event($result['StromPi-DateTimeOutput']);
    } elseif (isset($result['StromPi-StrompiStatusOutput'])) {
      log::add('jeestrompi', 'debug', 'receive daemon StromPi-StrompiStatusOutput =' .$result['StromPi-StrompiStatusOutput']);
	  list($StromPiMode, $StromPiModeOutput, $StromPiOutputVoltage, $StromPiWide, $StromPiUSB, $StromPiLifePo4V, $StromPiLifePo4Charge) = explode("|", $result['StromPi-StrompiStatusOutput']);
	  if ($StromPiWide == '') $StromPiWide=0;
	  if ($StromPiMode == '') $StromPiMode=-1;
	  if ($StromPiLifePo4V == '') $StromPiLifePo4V=-1;
	  if ($StromPiLifePo4Charge == '') $StromPiLifePo4Charge=-1;
	  if ($StromPiLifePo4Charge == '') $StromPiLifePo4Charge=-1;
	  if ($StromPiWide == '') $StromPiWide=-1;
	  if ($StromPiUSB == '') $StromPiUSB=-1;
	  cmd::byId($Strompi_mode_object_id)->event($StromPiMode);
	  cmd::byId($Strompi_Output__Mode_object_id)->event($StromPiModeOutput);
	  cmd::byId($Strompi_Output_Voltage_object_id)->event($StromPiOutputVoltage);
	  cmd::byId($Strompi_Wide_Input_object_id)->event($StromPiWide);
	  cmd::byId($Strompi_USB_Input_object_id)->event($StromPiUSB);
	  cmd::byId($Strompi_LifePo4_Input_object_id)->event($StromPiLifePo4V);
	  cmd::byId($Strompi_LifePo4Charge_Input_object_id)->event($StromPiLifePo4Charge);
    } elseif (isset($result['StromPi-Output-Voltage'])) {
      log::add('jeestrompi', 'debug', 'receive daemon StromPi-Output-Voltage =' .$result['StromPi-Output-Voltage']);
      cmd::byId($Strompi_Output_Voltage_object_id)->event($result['StromPi-Output-Voltage']);
      /*log::add('jeestrompi', 'debug', 'receive daemon StromPi-Wide-Inputvoltage =' .$result['StromPi-Wide-Inputvoltage']);
      $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
      log::add('jeestrompi', 'debug', 'getLogicalId='.$this->getLogicalId());
      $eqlogic->checkAndUpdateCmd('StromPiOutput', $result['StromPi-Output-Voltage']);*/
    } else {
        log::add('jeestrompi', 'error', 'unknown message received from daemon'); //remplacez template par l'id de votre plugin
    }

/*} catch (Exception $e) {
    log::add('jeestrompi', 'error', displayException($e)); //remplacez template par l'id de votre plugin
}*/