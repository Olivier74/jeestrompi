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

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
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
  public function randomVdm() {
    $url = "http://www.viedemerde.fr/aleatoire";
    $data = file_get_contents($url);
    @$dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($data);
    libxml_use_internal_errors(false);
    $xpath = new DOMXPath($dom);
    $divs = $xpath->query('//a[@class="block text-blue-500 dark:text-white my-4 "][1]');
    /*return $xpath;*/
    return $divs[0]->nodeValue ;
  }
  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */
  public static function cronHourly () {
    foreach (self::byType('jeestrompi', true) as $jeestrompi) { //parcours tous les équipements actifs du plugin vdm
      $cmd = $jeestrompi->getCmd(null, 'refresh'); //retourne la commande "refresh" si elle existe
      if (!is_object($cmd)) { //Si la commande n'existe pas
      continue; //continue la boucle
    }
    $cmd->execCmd(); //la commande existe on la lance
    }
  }

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
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
  $strompimode = $this->getCmd(null, 'strompimode');
  if (!is_object($strompimode)) {
    $strompimode = new jeestrompiCmd();
    $strompimode->setName(__('Strompi Mode', __FILE__));
  }
  $strompimode->setLogicalId('strompimode');
  $strompimode->setEqLogic_id($this->getId());
  $strompimode->setType('info');
  $strompimode->setTemplate('dashboard','tile');//template pour le dashboard
  $strompimode->setSubType('numeric');
  $strompimode->save();
  
  $StromPiLifePo4 = $this->getCmd(null, 'StromPiLifePo4');
  if (!is_object($StromPiLifePo4)) {
    $StromPiLifePo4 = new jeestrompiCmd();
    $StromPiLifePo4->setName(__('Strompi LifePo4', __FILE__));
  }
  $StromPiLifePo4->setLogicalId('StromPiLifePo4');
  $StromPiLifePo4->setEqLogic_id($this->getId());
  $StromPiLifePo4->setType('info');
  $StromPiLifePo4->setTemplate('dashboard','tile');//template pour le dashboard
  $StromPiLifePo4->setSubType('numeric');
  $StromPiLifePo4->save();

  $StromPiWide = $this->getCmd(null, 'StromPiWide');
  if (!is_object($StromPiWide)) {
    $StromPiWide = new jeestrompiCmd();
    $StromPiWide->setName(__('Strompi Wide', __FILE__));
  }
  $StromPiWide->setLogicalId('StromPiWide');
  $StromPiWide->setEqLogic_id($this->getId());
  $StromPiWide->setType('info');
  $StromPiWide->setTemplate('dashboard','tile');//template pour le dashboard
  $StromPiWide->setSubType('numeric');
  $StromPiWide->save();

  $StromPiUSB = $this->getCmd(null, 'StromPiUSB');
  if (!is_object($StromPiUSB)) {
    $StromPiUSB = new jeestrompiCmd();
    $StromPiUSB->setName(__('Strompi USB', __FILE__));
  }
  $StromPiUSB->setLogicalId('StromPiUSB');
  $StromPiUSB->setEqLogic_id($this->getId());
  $StromPiUSB->setType('info');
  $StromPiUSB->setTemplate('dashboard','tile');//template pour le dashboard
  $StromPiUSB->setSubType('numeric');
  $StromPiUSB->save();
  
  $StromPiOutput = $this->getCmd(null, 'StromPiOutput');
  if (!is_object($StromPiOutput)) {
    $StromPiOutput = new jeestrompiCmd();
    $StromPiOutput->setName(__('Strompi Output', __FILE__));
  }
  $StromPiOutput->setLogicalId('StromPiOutput');
  $StromPiOutput->setEqLogic_id($this->getId());
  $StromPiOutput->setType('info');
  $StromPiOutput->setTemplate('dashboard','tile');//template pour le dashboard
  $StromPiOutput->setSubType('numeric');
  $StromPiOutput->save();

  $StromPiLifePo4Charge = $this->getCmd(null, 'StromPiLifePo4Charge');
  if (!is_object($StromPiLifePo4Charge)) {
    $StromPiLifePo4Charge = new jeestrompiCmd();
    $StromPiLifePo4Charge->setName(__('Strompi LifePo4Charge', __FILE__));
  }
  $StromPiLifePo4Charge->setLogicalId('StromPiLifePo4Charge');
  $StromPiLifePo4Charge->setEqLogic_id($this->getId());
  $StromPiLifePo4Charge->setType('info');
  $StromPiLifePo4Charge->setTemplate('dashboard','tile');//template pour le dashboard
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
    log::add('jeestrompi', 'info', 'Lancement update');
    $eqlogic = $this->getEqLogic(); //récupère l'éqlogic de la commande $this
    switch ($this->getLogicalId()) { //vérifie le logicalid de la commande
    case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave de la classe vdm .
     log::add('jeestrompi', 'info', 'mise a jour story');
     /*$info = $eqlogic->randomVdm(); //On lance la fonction randomVdm() pour récupérer une vdm et on la stocke dans la variable $info*/
     $info = random_int(1, 6);
     $eqlogic->checkAndUpdateCmd('strompimode', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
	 $info = random_int(0, 3.5);
     $eqlogic->checkAndUpdateCmd('StromPiLifePo4', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
	 $info = random_int(1, 24);
     $eqlogic->checkAndUpdateCmd('StromPiWide', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
	 $info = random_int(1, 5);
     $eqlogic->checkAndUpdateCmd('StromPiUSB', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
	 $info = random_int(1, 5);
     $eqlogic->checkAndUpdateCmd('StromPiOuput', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
	 $info = random_int(1, 5);
     $eqlogic->checkAndUpdateCmd('StromPiLifePo4Charge', $info); //on met à jour la commande avec le LogicalId "story"  de l'eqlogic
    break;
    }
}

  /*     * **********************Getteur Setteur*************************** */
}
