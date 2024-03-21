<?php
try {
    require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
  	log::add('jeestrompi', 'debug', '******** Start receive daemon *********');
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
	
//$plugin = plugin::byId('jeestrompi');
//log::add('jeestrompi', 'debug', '$plugin encode : '.$plugin[0]);
//sendVarToJS('eqType', $plugin->getId());
//$eqLogics = eqLogic::byType($plugin->getId());
//log::add('jeestrompi', 'debug', 'eqLogics : '.json_encode($eqLogics));
/*foreach ($eqLogics as $result) {
 //  log::add('jeestrompi', 'debug', 'eqLogics : '.$result);
}*/


//foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {  // pour tous les équipements actifs de la classe agendad
//      $eqLogic->collect_agenda();
//}	
	$idEqLogic=$result['eqlogic'];
	$eq=eqLogic::byid($idEqLogic);

	$eqLogics = eqLogic::byType('jeestrompi');
	$i = 0 ;
	foreach($eqLogics as $eqLogic) {
	  //$scenario->setLog($eqLogic->getLogicalId() . $eqLogic->getHumanName() . ' ' .$eqLogic->getConfiguration()['product_name']);
	  log::add('jeestrompi', 'debug', '****** receive daemon -----> LogicalID:'.$eqLogic->getLogicalId() . ' GetId:'.$eqLogic->getId().' Name:'. $eqLogic->getHumanName() . ' config' .$eqLogic->getConfiguration()['StrompiModeConfig']);
	  $Tabeqlogic[$i]=$eqLogic->getId();
	  if( $eqLogic->getConfiguration()['product_name'] == 'Eurotronic') {
		// équipements du type voulu
	  }
	  $i++ ;
	}
	//$idEqLogicint=$Tabeqlogic[0];
	//$eq=eqLogic::byid(intval($eqlogicint));
	log::add('jeestrompi', 'debug', 'receive daemon : Equipments plugin jeestrompi : ' . $eq->getName() . ' ('.$eq->getHumanName().')');
	$StrompiModeConfig = $eq->getConfiguration('StrompiModeConfig');
	log::add('jeestrompi', 'debug', 'receive daemon : return getConfiguration Mode: '.$StrompiModeConfig);
  
	/*log::add('jeestrompi', 'debug', ' - cmd : ' $eq->searchByString*/
	if (isset($result['StromPi-DateTimeOutput'])) {
	  /*$eqLogic = eqLogic::byType('StromPiModecfg');*/
	  log::add('jeestrompi', 'debug', 'receive daemon : StromPi-DateTimeOutput=' .$result['StromPi-DateTimeOutput']);
	  log::add('jeestrompi', 'debug', 'receive daemon : eqlogic ='.$eqLogic[0]);
	  cmd::byId($Strompi_DateTime_object_id)->event($result['StromPi-DateTimeOutput']);
	} elseif (isset($result['StromPi-StrompiStatusOutput'])) {
	  log::add('jeestrompi', 'debug', 'receive daemon : StromPi-StrompiStatusOutput =' .$result['StromPi-StrompiStatusOutput']);
	  list($StromPiDateTimeOutput, $StromPiModecfg, $StromPiModeOutput, $StromPiOutputVoltage, $StromPiWide, $StromPiUSB, $StromPiLifePo4V, $StromPiLifePo4Charge) = explode("|", $result['StromPi-StrompiStatusOutput']);
	  if ($StromPiWide == '') $StromPiWide=0;
	  if ($StromPiModecfg == '') $StromPiModecfg=-1;
	  if ($StromPiLifePo4V == '') $StromPiLifePo4V=-1;
	  if ($StromPiLifePo4Charge == '') $StromPiLifePo4Charge=-1;
	  if ($StromPiLifePo4Charge == '') $StromPiLifePo4Charge=-1;
	  if ($StromPiWide == '') $StromPiWide=-1;
	  if ($StromPiUSB == '') $StromPiUSB=-1;
	  list($StromPiModecfgtmp) = explode("=",$StromPiModecfg);
	  $StromPiModecfgtmp = trim($StromPiModecfgtmp);
	  log::add('jeestrompi', 'debug', 'receive daemon : mise à jour StrompiModeConfig ='.intval($StromPiModecfgtmp));
	  $eq->setConfiguration('StrompiModeConfig', intval($StromPiModecfgtmp));
	  $eq->save(true);
	  
	  if (is_object($eq)) {
	  //$eq->setConfiguration('StrompiModeConfig', 5);
	  //$eq->save(true);
		  $cmds=$eq->getCmd('info',null, true,true);
		  if (sizeof($cmds) > 0) {
			foreach($cmds as $cmd) {
				//log::add('jeestrompi', 'debug', ' - cmd : ' . $cmd->getName() . ', value -> '. $cmd->execCmd().'  eqlogic : '.$cmd->getId());*/
				if ($cmd->getLogicalId() == 'strompimodecfg') {
				  //$cmd->event("2");
					cmd::byId($cmd->getId())->event($StromPiModecfg);
				  log::add('jeestrompi', 'debug', 'receive daemon : getId : '.$cmd->getLogicalId());
				} elseif  ($cmd->getLogicalId() == 'StrompiDateTime') {
					cmd::byId($cmd->getId())->event($StromPiDateTimeOutput);
				} elseif  ($cmd->getLogicalId() == 'StromPiOutputMode') {
					cmd::byId($cmd->getId())->event($StromPiModeOutput);
				} elseif  ($cmd->getLogicalId() == 'StromPiOutputVoltage') {
					cmd::byId($cmd->getId())->event($StromPiOutputVoltage);
				  log::add('jeestrompi', 'debug', 'receive daemon : getId : '.$cmd->getLogicalId());
				} elseif  ($cmd->getLogicalId() == 'StromPiWide') {
					cmd::byId($cmd->getId())->event($StromPiWide);
				} elseif  ($cmd->getLogicalId() == 'StromPiUSB') {
					cmd::byId($cmd->getId())->event($StromPiUSB);
				} elseif  ($cmd->getLogicalId() == 'StromPiLifePo4') {
					cmd::byId($cmd->getId())->event($StromPiLifePo4V);
				  $Strompi_LifePo4_Input_object_id = $cmd->getId();
				} elseif  ($cmd->getLogicalId() == 'StromPiLifePo4Charge') {
					cmd::byId($cmd->getId())->event($StromPiLifePo4Charge);
				} else {
				}
			}
		  } 
	  }
	} else {
		log::add('jeestrompi', 'error', 'receive daemon : unknown message received from daemon'); //remplacez template par l'id de votre plugin
	}
		log::add('jeestrompi', 'debug', '******** end receive daemon *********');
} catch (Exception $e) {
    log::add('jeestrompi', 'error', displayException($e)); //remplacez template par l'id de votre plugin
}