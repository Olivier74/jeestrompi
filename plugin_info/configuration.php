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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Paramètre du port série}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le port de communication de la carte StromPi}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="strompiserialport"/>
		<option value=""></option>
          <option value="/dev/serial0" selected>/dev/serial0</option>
          <option value="/dev/serial1">/dev/serial1</option>
         </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Vitesse port serie}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez la vitesse du port de la carte StromPi}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="strompiserialbaud"/>
      </div>
    </div>
	<div class="form-group">
      <label class="col-md-4 control-label">{{port d\'écoute}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le port d\'écoute du daemon}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="strompidsocketport"/>
      </div>
    </div>
  </fieldset>
</form>
