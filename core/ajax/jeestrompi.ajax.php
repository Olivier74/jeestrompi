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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class jeestrompi extends eqLogic {
  /*     * *************************Attributs****************************** */
  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  /*     * ***********************Methode static*************************** */
	 public static function deamon_info() {
        $return = array();
        $return['log'] = __CLASS__;
        $return['state'] = 'nok';
        $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
        if (file_exists($pid_file)) {
            if (@posix_getsid(trim(file_get_contents($pid_file)))) {
                $return['state'] = 'ok';
            } else {
                shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
            }
        }
        $return['launchable'] = 'ok';
        $strompiserialport = config::byKey('strompiserialport', __CLASS__); // exemple si votre démon à besoin de la config serial,
        $strompiserialbaud = config::byKey('strompiserialbaud', __CLASS__); // vitesse,
        $strompidsocketport = config::byKey('strompidsocketport', __CLASS__); // socket
		$strompidcycle = config::byKey('jeestrompicycle', __CLASS__); // et cycle
        if ($strompiserialport == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le port serie n\'est pas configuré', __FILE__);
        } elseif ($strompiserialbaud == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('La vitesse n\'est pas configuré', __FILE__);
        } elseif ($strompidsocketport == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le port d\'ecoute n\'est pas configurée', __FILE__);
        } elseif ($strompidcycle == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le cycle n\'est pas configurée', __FILE__);
        }
        return $return;
    }

    public static function dependancy_install() {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('jeestrompi') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    
	public static function deamon_start() {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }

        $path = realpath(dirname(__FILE__) . '/../../resources/demond'); // répertoire du démon à modifier
        $cmd = 'python3 ' . $path . '/jeestrompyd.py'; // nom du démon à modifier
        $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
        $cmd .= ' --socketport ' . config::byKey('strompidsocketport', __CLASS__, '55009'); // port par défaut à modifier
        $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'http:127.0.0.1:port:comp') . '/plugins/jeestrompi/core/php/jeestrompi.php'; // chemin de la callback url à modifier (voir ci-dessous)
        $cmd .= ' --serialport ' . config::byKey('strompiserialport', __CLASS__, '/dev/serial0');
        $cmd .= ' --serialbaud ' . config::byKey('strompiserialbaud', __CLASS__, '38400');
		$cmd .= ' --cycle ' . config::byKey('jeestrompicycle', __CLASS__, '0.3');
        $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__); // l'apikey pour authentifier les échanges suivants
        $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // et on précise le chemin vers le pid file (ne pas modifier)
        log::add(__CLASS__, 'info', 'Lancement démon');
      	exec(system::getCmdSudo() . $cmd . ' >> ' . log::getPathToLog('jeestrompid') . ' 2>&1 &');
        /*$result = exec($cmd . ' >> ' . log::getPathToLog('jeestrompy') . ' 2>&1 &'); // 'template_daemon' est le nom du log pour votre démon, vous devez nommer votre log en commençant par le pluginid pour que le fichier apparaisse dans la page de config*/
        $i = 0;
        while ($i < 20) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
            return false;
        }
        message::removeAll(__CLASS__, 'unableStartDeamon');
        return true;
    }

	
	public static function deamon_stop() {
        $pid_file = jeedom::getTmpFolder('jeestrompi') . '/deamon.pid';
        if (file_exists($pid_file)) {
            $pid = intval(trim(file_get_contents($pid_file)));
            system::kill($pid);
        }
        system::kill('jeestrompyd.py');
        system::fuserk(config::byKey('strompidsocketport', 'jeestrompi'));
        sleep(1);
    }
  
  public static function sendToDaemon($params) {
        $deamon_info = self::deamon_info();
    	log::add(__CLASS__, 'debug', 'sendToDaemon info:'.$deamon_info);
        if ($deamon_info['state'] != 'ok') {
            throw new Exception("Le démon n'est pas démarré");
          	return false;
        }
        $params['apikey'] = jeedom::getApiKey(__CLASS__);
        $payLoad = json_encode($params);
        $socket = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_connect($socket, '127.0.0.1', config::byKey('strompidsocketport', __CLASS__, '55009')); //port par défaut de votre plugin à modifier
        socket_write($socket, $payLoad, strlen($payLoad));
    	log::add(__CLASS__, 'debug', 'sendToDaemon send:'.$socket.' '.$payLoad);
        socket_close($socket);
    	return true;
    }
  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */
   public static function cron() {
		$dateRun = new DateTime();
        log::add('jeestrompi', 'info', 'start autorefresh ');
		foreach (eqLogic::byType('jeestrompi') as $eqLogic) {
			$autorefresh = $eqLogic->getConfiguration('autorefresh');
            log::add('jeestrompi', 'info', 'autorefresh value = '.$autorefresh);
			if ($autorefresh != '') {
				try {
					$c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
					if ($c->isDue()) {
						$eqLogic->refresh();
					}
				} catch (Exception $exc) {
					log::add('jeestrompi', 'error', __('Expression cron non valide pour', __FILE__) . ' ' . $eqLogic->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}
  
  
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  /*public static function cron5() {}*/
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */
  
  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */
  
  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
    
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
   /*self::cronHourly($this->getId()); //lance la fonction cronHourly avec l’id de l’eqLogic*/
  }


  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
    if($this->getDisplay("width")=='') {$this->setDisplay("width","300px");}
    if($this->getDisplay("height")=='') {$this->setDisplay("height","300px");}
    //log::add('jeestrompi', 'info', 'preSave width : '.$hauteur);

  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
 /* public function postSave() {
      $eqLogic->createCommandsFromConfigFile(__DIR__ . '/../config/params.json', 'strompi');
  }*/


 public function postSave() {
	 
  $StrompiDateTime = $this->getCmd(null, 'StrompiDateTime');
  if (!is_object($StrompiDateTime)) {
    $StrompiDateTime = new jeestrompiCmd();
    $StrompiDateTime->setName(__('Date: ', __FILE__));
	$StrompiDateTime->setOrder(-50);
  }
  $StrompiDateTime->setLogicalId('StrompiDateTime');
  $StrompiDateTime->setEqLogic_id($this->getId());
  $StrompiDateTime->setType('info');
  $StrompiDateTime->setSubType('string');
  $StrompiDateTime->save();
   
   
  $strompistatus = $this->getCmd(null, 'strompistatus');
  if (!is_object($strompistatus)) {
    $strompistatus = new jeestrompiCmd();
    $strompistatus->setName(__('status', __FILE__));
	$strompistatus->setOrder(-43);
  //$strompistatus->setTemplate('dashboard','default');//template pour le dashboard
  //$strompistatus->setTemplate('mobile','default');//template pour le dashboard
  }
  $strompistatus->setLogicalId('strompistatus');
  $strompistatus->setEqLogic_id($this->getId());
  $strompistatus->setType('info');
  $strompistatus->setSubType('binary');
  $strompistatus->save();
 
  $strompimodecfg = $this->getCmd(null, 'strompimodecfg');
  if (!is_object($strompimodecfg)) {
    $strompimodecfg = new jeestrompiCmd();
    $strompimodecfg->setName(__('Mode Configuré: ', __FILE__));
	//$strompimodecfg->setTemplate('dashboard','tile');//template pour le dashboard
	//$strompimodecfg->setTemplate('mobile','tile');//template pour le dashboard
    //$strompimodecfg->setIsVisible(0);
  $strompimodecfg->setOrder(-47);
  }
  $strompimodecfg->setLogicalId('strompimodecfg');
  $strompimodecfg->setEqLogic_id($this->getId());
  $strompimodecfg->setType('info');
  $strompimodecfg->setSubType('string');
  $strompimodecfg->save();
     
  $StromPiModeState = $this->getCmd(null, 'StromPiModeState');
  if (!is_object($StromPiModeState)) {
    $StromPiModeState = new jeestrompiCmd();
    $StromPiModeState->setName(__('Mode Status', __FILE__));
	$StromPiModeState->setOrder(-40);
	$StromPiModeState->setTemplate('dashboard','strompimode');//template pour le dashboard
	$StromPiModeState->setTemplate('mobile','strompimode');//template pour le dashboard
  }
  $StromPiModeState->setLogicalId('StromPiModeState');
  $StromPiModeState->setEqLogic_id($this->getId());
  $StromPiModeState->setType('info');
  $StromPiModeState->setSubType('string');
  $StromPiModeState->save();
	
  $StromPiOutputVoltage = $this->getCmd(null, 'StromPiOutputVoltage');
  if (!is_object($StromPiOutputVoltage)) {
    $StromPiOutputVoltage = new jeestrompiCmd();
    $StromPiOutputVoltage->setName(__('Tension Alim.: ', __FILE__));
	$StromPiOutputVoltage->setOrder(-37);
	$StromPiOutputVoltage->setDisplay('generic_type', 'VOLTAGE');
	$StromPiOutputVoltage->setConfiguration('minValue' , '0');
	$StromPiOutputVoltage->setConfiguration('maxValue' , '5.1');
  }
  $StromPiOutputVoltage->setLogicalId('StromPiOutputVoltage');
  $StromPiOutputVoltage->setEqLogic_id($this->getId());
  $StromPiOutputVoltage->setType('info');
  $StromPiOutputVoltage->setSubType('numeric');
  $StromPiOutputVoltage->setUnite('V');
  $StromPiOutputVoltage->save();
  
  $StromPiUSB = $this->getCmd(null, 'StromPiUSB');
  if (!is_object($StromPiUSB)) {
    $StromPiUSB = new jeestrompiCmd();
    $StromPiUSB->setName(__('--> USB: ', __FILE__));
	$StromPiUSB->setOrder(-35);
	$StromPiUSB->setDisplay('generic_type', 'VOLTAGE');
	$StromPiUSB->setConfiguration('minValue' , '0');
	$StromPiUSB->setConfiguration('maxValue' , '5');
  }
  $StromPiUSB->setLogicalId('StromPiUSB');
  $StromPiUSB->setEqLogic_id($this->getId());
  $StromPiUSB->setType('info');
  $StromPiUSB->setSubType('numeric');
  $StromPiUSB->setUnite('V');
  $StromPiUSB->save();
  
  $StromPiWide = $this->getCmd(null, 'StromPiWide');
  if (!is_object($StromPiWide)) {
    $StromPiWide = new jeestrompiCmd();
    $StromPiWide->setName(__('--> Wide: ', __FILE__));
	$StromPiWide->setOrder(-32);
	$StromPiWide->setDisplay('generic_type', 'VOLTAGE');
	$StromPiWide->setConfiguration('minValue' , '0');
	$StromPiWide->setConfiguration('maxValue' , '61');
  }
  $StromPiWide->setLogicalId('StromPiWide');
  $StromPiWide->setEqLogic_id($this->getId());
  $StromPiWide->setType('info');
  $StromPiWide->setSubType('numeric');
  $StromPiWide->setUnite('V');
  $StromPiWide->save();
  
  $StromPiLifePo4 = $this->getCmd(null, 'StromPiLifePo4');
  if (!is_object($StromPiLifePo4)) {
    $StromPiLifePo4 = new jeestrompiCmd();
    $StromPiLifePo4->setName(__('--> Batterie:', __FILE__));
	$StromPiLifePo4->setOrder(-30);
	$StromPiLifePo4->setDisplay('generic_type', 'VOLTAGE');
	$StromPiLifePo4->setConfiguration('minValue' , '0');
	$StromPiLifePo4->setConfiguration('maxValue' , '3.5');
  }
  $StromPiLifePo4->setLogicalId('StromPiLifePo4');
  $StromPiLifePo4->setEqLogic_id($this->getId());
  $StromPiLifePo4->setType('info');
  $StromPiLifePo4->setSubType('numeric');
  $StromPiLifePo4->setUnite('V');
  $StromPiLifePo4->save();
 
 $StromPiLifePo4Charge = $this->getCmd(null, 'StromPiLifePo4Charge');
  if (!is_object($StromPiLifePo4Charge)) {
    $StromPiLifePo4Charge = new jeestrompiCmd();
    $StromPiLifePo4Charge->setName(__('Charge Bat. LifePo4: ', __FILE__));
	$StromPiLifePo4Charge->setOrder(-27);
  }
  $StromPiLifePo4Charge->setLogicalId('StromPiLifePo4Charge');
  $StromPiLifePo4Charge->setEqLogic_id($this->getId());
  $StromPiLifePo4Charge->setType('info');
  $StromPiLifePo4Charge->setSubType('string');
  $StromPiLifePo4Charge->save();

  $refresh = $this->getCmd(null, 'refresh');
  if (!is_object($refresh)) {
    $refresh = new jeestrompiCmd();
    $refresh->setName(__('Rafraichir', __FILE__));
  }
  $refresh->setEqLogic_id($this->getId());
  $refresh->setLogicalId('refresh');
  $refresh->setType('action');
  $refresh->setSubType('other');
  $refresh->setOrder(0);
  $refresh->save();	  
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */
  
  /*     * **********************Getteur Setteur*************************** */
}

class jeestrompiCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */
  
  // Exécution d'une commande
  public function execute($_options = array()) {
    $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
    log::add('jeestrompi', 'info', 'execute : Lancement update par '.$this->getLogicalId());
    $StrompiModeConfig = $eqlogic->getConfiguration('StrompiModeConfig');
    log::add('jeestrompi', 'debug', 'execute : getConfiguration Mode: '.$StrompiModeConfig);
    //log::add('jeestrompi', 'debug', 'execute : jeestrompiModecfg: '.$eqLogic->getStatus('strompimodecfg'));
    //log::add('jeestrompi', 'debug', 'execute getHumanName: '.$eqLogic->getHumanName());
    switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
     case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .
       log::add('jeestrompi', 'info', 'mise a jour des valeurs de la carte strompi');
       $object_name = $this->getEqLogic()->getHumanName();
       $object_id = $this->getEqLogic_id();
       $parameter = array('eqlogic' => $object_id,'action' => 'status-rpi');
       /*$parameter = 'action';*/
       $eqlogic->sendToDaemon($parameter);
       $replace['#LogoStation#'] = is_object($LogoStation) ? '<img src="/data/img/electrical_power_on.png" style="max-width: '.$PicSize.'px; max-height: '.$PicSize.'px;">' : '';
       log::add('jeestrompi', 'info', 'envoi d un message a la  carte strompi');
      break;
    }
  }

  /*     * **********************Getteur Setteur*************************** */
}